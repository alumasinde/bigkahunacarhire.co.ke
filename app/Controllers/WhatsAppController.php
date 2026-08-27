<?php
declare(strict_types=1);

final class WhatsAppController
{
    public function webhook(): void
    {
        $service = new WhatsAppService();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $challenge=$service->verifyWebhook($_GET);
            if($challenge===null){http_response_code(403);echo 'Forbidden';return;}
            header('Content-Type:text/plain'); echo $challenge; return;
        }
        $raw=file_get_contents('php://input') ?: '';
        if(!$service->verifySignature($raw,$_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '')){http_response_code(401);echo 'Invalid signature';return;}
        try {
            $payload=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
            $inbox=WhatsAppInboxService::make();
            foreach(($payload['entry'] ?? []) as $entry){
                foreach(($entry['changes'] ?? []) as $change){
                    $value=$change['value'] ?? [];
                    foreach(($value['messages'] ?? []) as $message){
                        $from=(string)($message['from'] ?? '');
                        $type=(string)($message['type'] ?? 'unknown');
                        $body=null; $media=null;
                        if($type==='text') $body=(string)($message['text']['body'] ?? '');
                        elseif(isset($message[$type]['caption'])) $body=(string)$message[$type]['caption'];
                        $contactName=null;
                        foreach(($value['contacts'] ?? []) as $contact){ if(($contact['wa_id'] ?? '')===$from){$contactName=$contact['profile']['name'] ?? null;break;} }
                        $inbox->receive($from,$contactName,$type,$body,$message['id'] ?? null,$message,$media);
                    }
                    foreach(($value['statuses'] ?? []) as $status){ $inbox->applyStatus((string)($status['id'] ?? ''),(string)($status['status'] ?? '')); }
                }
            }
            AuditService::make()->log('whatsapp.webhook.received','WhatsApp webhook processed.',null,'whatsapp_webhook',null,['object'=>$payload['object'] ?? null]);
        } catch(Throwable $e){
            error_log('[WHATSAPP WEBHOOK] '.$e->getMessage());
            // Return a non-2xx response so Meta can retry transient failures.
            // Inbound provider message IDs are deduplicated before insertion,
            // so retries do not create duplicate conversations/messages.
            http_response_code(500);
            echo 'Webhook processing failed';
            return;
        }
        http_response_code(200); echo 'EVENT_RECEIVED';
    }
}
