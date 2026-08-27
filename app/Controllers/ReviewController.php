<?php
declare(strict_types=1);

final class ReviewController
{
    public function index(): void
    {
        $s = ReviewService::make();
        view('reviews', [
            'seo' => [
                'title' => 'Customer Reviews | Big Kahuna Car Hire',
                'description' => 'Read recent Big Kahuna Car Hire reviews from Google and Tripadvisor.',
                'keywords' => 'Big Kahuna Car Hire reviews, Google reviews, Tripadvisor reviews',
                'og_image' => setting('seo','og_image'),
                'robots' => 'index, follow'
            ],
            'reviews' => $s->visible(50),
            'summary' => $s->summary(),
            'links' => $s->reviewLinks()
        ]);
    }

    public function admin(): void
    {
        Auth::requirePermission('settings.manage');
        $s = ReviewService::make();

        view('admin/reviews', [
            'seo' => [
                'title' => 'Reviews | Admin',
                'description' => '',
                'keywords' => '',
                'og_image' => '',
                'robots' => 'noindex, nofollow'
            ],
            'reviews' => $s->all(),
            'summary' => $s->summary(),
            'config' => $s->configuration(),
            'links' => $s->reviewLinks()
        ]);
    }

    public function sync(): void
    {
        Auth::requirePermission('settings.manage');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/reviews');
        }

        $result = ReviewService::make()->syncAll();
        $parts = [];

        foreach ($result as $source => $data) {
            $message = (string)($data['message'] ?? '');
            $parts[] = ucfirst($source) . ': ' . (int)($data['count'] ?? 0) . ' synced'
                . (str_contains($message, 'HTTP') ? ' — ' . $message : '');
        }

        flash('success', implode(' · ', $parts));
        redirect('admin/reviews');
    }

    public function visibility(int $id): void
    {
        Auth::requirePermission('settings.manage');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/reviews');
        }

        $visible = ($_POST['visible'] ?? '0') === '1';
        ReviewService::make()->setVisibility($id, $visible);

        flash(
            'success',
            $visible ? 'Review is visible on the website.' : 'Review hidden from the website.'
        );

        redirect('admin/reviews');
    }

    public function googleConnect(): void
    {
        Auth::requirePermission('settings.manage');

        $state = bin2hex(random_bytes(24));
        $_SESSION['google_review_oauth_state'] = $state;

        try {
            header('Location: ' . ReviewService::make()->googleAuthorizationUrl($state));
            exit;
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('admin/reviews');
        }
    }

    public function googleCallback(): void
    {
        Auth::requirePermission('settings.manage');

        $state = (string)($_GET['state'] ?? '');
        $expected = (string)($_SESSION['google_review_oauth_state'] ?? '');
        unset($_SESSION['google_review_oauth_state']);

        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            flash('error', 'Google authorization state was invalid.');
            redirect('admin/reviews');
        }

        if (!empty($_GET['error'])) {
            flash('error', 'Google authorization was cancelled.');
            redirect('admin/reviews');
        }

        try {
            $result = ReviewService::make()->exchangeGoogleCode((string)($_GET['code'] ?? ''));

            /*
             * Account/location IDs are not secrets. Save them in the existing
             * settings table so the administrator does not need to find them.
             *
             * The OAuth refresh token is intentionally NOT written to MySQL.
             * It is shown once and should be copied into .env.
             */
            if (!empty($result['selected'])) {
                $selected = $result['selected'];
                $locationName = (string)($selected['name'] ?? '');

                if (!preg_match('#^accounts/([^/]+)/locations/([^/]+)$#', $locationName, $m)) {
                    throw new RuntimeException('Google returned an invalid Business Profile location.');
                }

                $this->saveGoogleLocation($m[1], $m[2]);

                $_SESSION['google_review_location_selected'] = [
                    'account_id' => $m[1],
                    'location_id' => $m[2],
                    'title' => (string)($selected['title'] ?? 'Big Kahuna Car Hire')
                ];
            } else {
                $_SESSION['google_review_location_choices'] = $result['locations'] ?? [];
            }

            $_SESSION['google_refresh_token_result'] = $result['refresh_token'];

            if (!empty($result['selected'])) {
                flash(
                    'success',
                    "Google connected. Big Kahuna's Business Profile location was detected automatically. Copy the refresh token below into .env, then sync reviews."
                );
            } else {
                flash(
                    'success',
                    "Google connected. Multiple Business Profile locations were found. Select the Big Kahuna location below, then copy the refresh token into .env."
                );
            }
        } catch (Throwable $e) {
            flash('error', 'Google connection failed: ' . $e->getMessage());
        }

        redirect('admin/reviews');
    }

    public function googleLocation(): void
    {
        Auth::requirePermission('settings.manage');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/reviews');
        }

        $locationName = (string)($_POST['location_name'] ?? '');
        $locations = $_SESSION['google_review_location_choices'] ?? [];
        $selected = null;

        foreach ($locations as $location) {
            if ((string)($location['name'] ?? '') === $locationName) {
                $selected = $location;
                break;
            }
        }

        if (!$selected || !preg_match('#^accounts/([^/]+)/locations/([^/]+)$#', $locationName, $m)) {
            flash('error', 'Invalid Google Business Profile location selected.');
            redirect('admin/reviews');
        }

        $this->saveGoogleLocation($m[1], $m[2]);

        $_SESSION['google_review_location_selected'] = [
            'account_id' => $m[1],
            'location_id' => $m[2],
            'title' => (string)($selected['title'] ?? 'Google Business Profile')
        ];

        unset($_SESSION['google_review_location_choices']);

        flash(
            'success',
            "Google Business Profile location saved. Copy the refresh token below into .env, then sync reviews."
        );

        redirect('admin/reviews');
    }

    private function saveGoogleLocation(string $accountId, string $locationId): void
    {
        $db = Database::connection();

        foreach ([
            'google_account_id' => $accountId,
            'google_location_id' => $locationId
        ] as $key => $value) {
            $stmt = $db->prepare(
                "INSERT INTO settings(setting_group,setting_key,setting_value)
                 VALUES('reviews',:key,:value)
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
            );

            $stmt->execute([
                ':key' => $key,
                ':value' => $value
            ]);
        }
    }
}
