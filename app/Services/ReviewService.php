<?php
declare(strict_types=1);

final class ReviewService
{
    public function __construct(private PDO $db) {}
    public static function make(): self { return new self(Database::connection()); }

    public function configuration(): array {
        return [
            'google'=>[
                'enabled'=>setting('reviews','google_enabled','1')==='1',
                'credentials'=>GOOGLE_REVIEW_CLIENT_ID!=='' && GOOGLE_REVIEW_CLIENT_SECRET!=='',
                'oauth_ready'=>GOOGLE_REVIEW_REFRESH_TOKEN!=='',
                'location_ready'=>GOOGLE_REVIEW_ACCOUNT_ID!=='' && GOOGLE_REVIEW_LOCATION_ID!=='',
            ],
            'tripadvisor'=>[
                'enabled'=>setting('reviews','tripadvisor_enabled','1')==='1',
                'credentials'=>TRIPADVISOR_API_KEY!=='',
                'location_ready'=>TRIPADVISOR_LOCATION_ID!=='',
            ],
            'display'=>[
                'enabled'=>setting('reviews','enabled','1')==='1',
                'home_limit'=>max(1,min(12,(int)setting('reviews','home_limit','6'))),
                'request_enabled'=>setting('reviews','request_enabled','1')==='1',
            ],
        ];
    }

    public function visible(int $limit=12): array {
        try {
            $s=$this->db->prepare('SELECT * FROM reviews WHERE is_visible=1 ORDER BY review_date DESC,id DESC LIMIT :limit');
            $s->bindValue(':limit',max(1,min(50,$limit)),PDO::PARAM_INT); $s->execute(); return $s->fetchAll();
        } catch(Throwable $e){ error_log('[REVIEWS] visible: '.$e->getMessage()); return []; }
    }
    public function all(): array { try{return $this->db->query('SELECT * FROM reviews ORDER BY review_date DESC,id DESC')->fetchAll();}catch(Throwable $e){return [];} }
    public function summary(): array {
        try{$rows=$this->db->query("SELECT source,COUNT(*) review_count,ROUND(AVG(rating),1) average_rating FROM reviews WHERE is_visible=1 GROUP BY source")->fetchAll();}
        catch(Throwable $e){return ['overall'=>['count'=>0,'rating'=>0.0]];}
        $out=[];$total=0;$weighted=0.0;
        foreach($rows as $r){$n=(int)$r['review_count'];$a=(float)$r['average_rating'];$out[$r['source']]=['count'=>$n,'rating'=>$a];$total+=$n;$weighted+=$a*$n;}
        $out['overall']=['count'=>$total,'rating'=>$total?round($weighted/$total,1):0.0]; return $out;
    }
    public function reviewLinks(): array {
        $g=setting('reviews','google_review_url',''); $p=setting('reviews','tripadvisor_review_url',''); $place=setting('reviews','google_place_id','');
        if($g==='' && $place!=='') $g='https://search.google.com/local/writereview?placeid='.rawurlencode($place);
        return ['google'=>$g,'tripadvisor'=>$p];
    }
    public function homepage(int $limit=6): array {
        $cfg=$this->configuration();
        return ['enabled'=>$cfg['display']['enabled'],'reviews'=>$this->visible($limit),'summary'=>$this->summary(),'links'=>$this->reviewLinks()];
    }
    public function syncAll(): array {
        $r=['google'=>['count'=>0,'message'=>'Not configured.'],'tripadvisor'=>['count'=>0,'message'=>'Not configured.']]; $c=$this->configuration();
        if($c['google']['enabled']){try{$r['google']=['count'=>$this->syncGoogle(),'message'=>'Google reviews synced.'];}catch(Throwable $e){$r['google']=['count'=>0,'message'=>'Google: '.$e->getMessage()];error_log('[REVIEWS][GOOGLE] '.$e->getMessage());}}
        if($c['tripadvisor']['enabled']){try{$r['tripadvisor']=['count'=>$this->syncTripadvisor(),'message'=>'Tripadvisor reviews synced.'];}catch(Throwable $e){$r['tripadvisor']=['count'=>0,'message'=>'Tripadvisor: '.$e->getMessage()];error_log('[REVIEWS][TRIPADVISOR] '.$e->getMessage());}}
        return $r;
    }
    public function syncGoogle(): int {
        if(GOOGLE_REVIEW_CLIENT_ID===''||GOOGLE_REVIEW_CLIENT_SECRET===''||GOOGLE_REVIEW_REFRESH_TOKEN==='') throw new RuntimeException('Google OAuth credentials are incomplete.');
        $accountId=setting('reviews','google_account_id',GOOGLE_REVIEW_ACCOUNT_ID); $accountId=preg_replace('#^accounts/#','',(string)$accountId); $locationId=setting('reviews','google_location_id',GOOGLE_REVIEW_LOCATION_ID); $locationId=preg_replace('#^locations/#','',(string)$locationId);
        if($accountId===''||$locationId==='') throw new RuntimeException('Google Business account/location IDs are not configured.');
        $tok=$this->googleAccessToken(); $base='https://mybusiness.googleapis.com/v4/accounts/'.rawurlencode($accountId).'/locations/'.rawurlencode($locationId).'/reviews';
        $next='';$saved=0;$pages=0;
        do{
            $url=$base.'?pageSize=50&orderBy=updateTime%20desc'.($next!==''?'&pageToken='.rawurlencode($next):'');
            $data=$this->httpJson('GET',$url,null,['Authorization: Bearer '.$tok,'Accept: application/json']);
            foreach(($data['reviews']??[]) as $v){
                $id=(string)($v['name']??$v['reviewId']??''); if($id==='')continue; $u=$v['reviewer']??[];
                $this->upsert(['source'=>'google','external_id'=>$id,'reviewer_name'=>(string)($u['displayName']??'Google customer'),'reviewer_photo'=>(string)($u['profilePhotoUrl']??''),'rating'=>$this->googleRating($v['starRating']??$v['rating']??''),'title'=>'','comment'=>(string)($v['comment']??''),'review_date'=>$this->dateValue($v['createTime']??$v['updateTime']??null),'review_url'=>setting('reviews','google_review_url',''),'owner_reply'=>(string)($v['reviewReply']['comment']??''),'raw_json'=>json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$saved++;
            }
            $next=(string)($data['nextPageToken']??'');$pages++;
        }while($next!==''&&$pages<20); return $saved;
    }
    public function syncTripadvisor(): int {
        $locationId=setting('reviews','tripadvisor_location_id',TRIPADVISOR_LOCATION_ID);
        if(TRIPADVISOR_API_KEY===''||$locationId==='') throw new RuntimeException('Tripadvisor API key and location ID are not configured.');
        $url=TRIPADVISOR_BASE_URL.'/'.rawurlencode($locationId).'/reviews?sort_by=MOST_RECENT&page=1&size=50&language=primary';
        $data=$this->httpJson('GET',$url,null,['X-API-Key: '.TRIPADVISOR_API_KEY,'Accept: application/json','User-Agent: Big Kahuna Car Hire Reviews/1.0']);
        $reviews=$data['reviews']??$data['data']['reviews']??$data['data']??[]; if(!is_array($reviews))$reviews=[];$saved=0;
        foreach($reviews as $v){if(!is_array($v))continue;$id=(string)($v['id']??$v['review_id']??'');if($id==='')continue;$u=$v['user']??$v['reviewer']??[];$reply=$v['management_response']??$v['owner_response']??'';if(is_array($reply))$reply=(string)($reply['text']??$reply['body']??'');
            $this->upsert(['source'=>'tripadvisor','external_id'=>$id,'reviewer_name'=>(string)($u['display_name']??$u['username']??$u['name']??'Tripadvisor traveler'),'reviewer_photo'=>(string)($u['avatar']??$u['photo']??''),'rating'=>max(1,min(5,(int)round((float)($v['rating']??$v['overall_rating']??5)))),'title'=>(string)($v['title']??''),'comment'=>(string)($v['text']??$v['body']??$v['review_text']??''),'review_date'=>$this->dateValue($v['published_date']??$v['published_at']??$v['date']??null),'review_url'=>(string)($v['url']??$v['review_url']??$v['link']??setting('reviews','tripadvisor_review_url','')),'owner_reply'=>(string)$reply,'raw_json'=>json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$saved++;
        } return $saved;
    }
    public function setVisibility(int $id,bool $visible): bool { $s=$this->db->prepare('UPDATE reviews SET is_visible=:v WHERE id=:id');return $s->execute([':v'=>$visible?1:0,':id'=>$id]); }
    public function googleAuthorizationUrl(string $state): string {
        if(GOOGLE_REVIEW_CLIENT_ID===''||GOOGLE_REVIEW_CLIENT_SECRET==='')throw new RuntimeException('Google client ID and client secret are not configured.');
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id'=>GOOGLE_REVIEW_CLIENT_ID,'redirect_uri'=>GOOGLE_REVIEW_REDIRECT_URI,'response_type'=>'code','scope'=>'https://www.googleapis.com/auth/business.manage','access_type'=>'offline','prompt'=>'consent','state'=>$state]);
    }
    public function exchangeGoogleCode(string $code): array
    {
        if ($code === '') {
            throw new RuntimeException('Google did not return an authorization code.');
        }

        $d = $this->httpJson(
            'POST',
            'https://oauth2.googleapis.com/token',
            [
                'code' => $code,
                'client_id' => GOOGLE_REVIEW_CLIENT_ID,
                'client_secret' => GOOGLE_REVIEW_CLIENT_SECRET,
                'redirect_uri' => GOOGLE_REVIEW_REDIRECT_URI,
                'grant_type' => 'authorization_code',
            ],
            ['Accept: application/json']
        );

        $refresh = (string)($d['refresh_token'] ?? '');
        $access = (string)($d['access_token'] ?? '');

        if ($refresh === '' || $access === '') {
            throw new RuntimeException('Google did not return the tokens required for offline Business Profile access.');
        }

        /*
         * Do not make the administrator find account/location IDs manually.
         * Discover them immediately after OAuth using the current Account
         * Management + Business Information APIs.
         */
        $accounts = $this->googleAccounts($access);

        if ($accounts === []) {
            throw new RuntimeException(
                'Google authorization succeeded, but no Business Profile accounts are accessible to this Google account.'
            );
        }

        $locations = [];
        foreach ($accounts as $account) {
            $accountName = (string)($account['name'] ?? '');
            if (!preg_match('#^accounts/([^/]+)$#', $accountName, $m)) {
                continue;
            }

            $accountId = $m[1];
            foreach ($this->googleLocations($access, $accountName) as $location) {
                $location['account_id'] = $accountId;
                $location['account_name'] = (string)($account['accountName'] ?? $account['name'] ?? '');
                $locations[] = $location;
            }
        }

        if ($locations === []) {
            throw new RuntimeException(
                'Google authorization succeeded, but no Business Profile locations were found for the authorized account.'
            );
        }

        /*
         * Prefer a location whose title/name contains "Big Kahuna".
         * If there is only one location, select it automatically.
         * If several remain, present the choices in the admin UI.
         */
        $selected = null;
        foreach ($locations as $location) {
            $haystack = strtolower(
                (string)($location['title'] ?? '') . ' ' .
                (string)($location['storeCode'] ?? '')
            );
            if (str_contains($haystack, 'big kahuna')) {
                $selected = $location;
                break;
            }
        }
        if ($selected === null && count($locations) === 1) {
            $selected = $locations[0];
        }

        return [
            'refresh_token' => $refresh,
            'accounts' => $accounts,
            'locations' => $locations,
            'selected' => $selected,
        ];
    }

    private function googleAccounts(string $accessToken): array
    {
        $url = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts?pageSize=100';
        $data = $this->httpJson(
            'GET',
            $url,
            null,
            ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
        );

        return is_array($data['accounts'] ?? null) ? $data['accounts'] : [];
    }

    private function googleLocations(string $accessToken, string $accountName): array
    {
        $url = 'https://mybusinessbusinessinformation.googleapis.com/v1/' .
            trim($accountName, '/') . '/locations?pageSize=100&readMask=name,title,storeCode';

        $data = $this->httpJson(
            'GET',
            $url,
            null,
            ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
        );

        return is_array($data['locations'] ?? null) ? $data['locations'] : [];
    }

    private function googleAccessToken(): string {$d=$this->httpJson('POST','https://oauth2.googleapis.com/token',['client_id'=>GOOGLE_REVIEW_CLIENT_ID,'client_secret'=>GOOGLE_REVIEW_CLIENT_SECRET,'refresh_token'=>GOOGLE_REVIEW_REFRESH_TOKEN,'grant_type'=>'refresh_token'],['Accept: application/json']);$t=(string)($d['access_token']??'');if($t==='')throw new RuntimeException('Google access token refresh failed.');return $t;}
    private function googleRating(mixed $v): int {if(is_numeric($v))return max(1,min(5,(int)$v));return match(strtoupper((string)$v)){'ONE'=>1,'TWO'=>2,'THREE'=>3,'FOUR'=>4,'FIVE'=>5,default=>5};}
    private function dateValue(mixed $v): string {$t=$v?strtotime((string)$v):false;return $t?date('Y-m-d H:i:s',$t):date('Y-m-d H:i:s');}
    private function upsert(array $r): void {
        $s=$this->db->prepare('INSERT INTO reviews (source,external_id,reviewer_name,reviewer_photo,rating,title,comment,review_date,review_url,owner_reply,raw_json) VALUES (:source,:external_id,:reviewer_name,:reviewer_photo,:rating,:title,:comment,:review_date,:review_url,:owner_reply,:raw_json) ON DUPLICATE KEY UPDATE reviewer_name=VALUES(reviewer_name),reviewer_photo=VALUES(reviewer_photo),rating=VALUES(rating),title=VALUES(title),comment=VALUES(comment),review_date=VALUES(review_date),review_url=VALUES(review_url),owner_reply=VALUES(owner_reply),raw_json=VALUES(raw_json),synced_at=CURRENT_TIMESTAMP');
        $s->execute([':source'=>$r['source'],':external_id'=>$r['external_id'],':reviewer_name'=>$r['reviewer_name'],':reviewer_photo'=>$r['reviewer_photo'],':rating'=>$r['rating'],':title'=>$r['title'],':comment'=>$r['comment'],':review_date'=>$r['review_date'],':review_url'=>$r['review_url'],':owner_reply'=>$r['owner_reply'],':raw_json'=>$r['raw_json']]);
    }
    private function httpJson(string $method,string $url,?array $form=null,array $headers=[]): array {
        $ch=curl_init($url);if($ch===false)throw new RuntimeException('Could not initialize review API connection.');$opt=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>7,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_2TLS];
        if($form!==null){$opt[CURLOPT_POSTFIELDS]=http_build_query($form);$has=false;foreach($headers as $h){if(stripos($h,'Content-Type:')===0)$has=true;}if(!$has)$opt[CURLOPT_HTTPHEADER]=array_merge($headers,['Content-Type: application/x-www-form-urlencoded']);}
        curl_setopt_array($ch,$opt);$body=curl_exec($ch);$err=curl_error($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($body===false)throw new RuntimeException('Review API request failed: '.$err);$d=json_decode($body,true);if(!is_array($d))throw new RuntimeException('Review API returned invalid JSON (HTTP '.$code.').');if($code<200||$code>=300)throw new RuntimeException((string)($d['error']['message']??$d['message']??'Review API request failed').' (HTTP '.$code.')');return $d;
    }
}
