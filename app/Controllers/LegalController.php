<?php
declare(strict_types=1);

final class LegalController
{
    public function privacy(): void
    {
        view('legal/privacy', [
            'seo' => [
                'title' => setting('legal', 'privacy_meta_title', 'Privacy Policy | ' . setting('general', 'site_name', 'Big Kahuna Car Hire')),
                'description' => setting('legal', 'privacy_meta_description', 'Privacy Policy for ' . setting('general', 'site_name', 'Big Kahuna Car Hire') . '.'),
                'keywords' => setting('seo', 'default_meta_keywords'),
                'og_image' => setting('seo', 'og_image'),
                'robots' => 'index, follow',
            ],
            'title' => setting('legal', 'privacy_title', 'Privacy Policy'),
            'content' => setting('legal', 'privacy_policy'),
            'lastUpdated' => setting('legal', 'privacy_last_updated'),
            'siteName' => setting('general', 'site_name', 'Big Kahuna Car Hire'),
            'email' => setting('general', 'email'),
            'phone' => setting('general', 'phone_primary'),
            'address' => setting('general', 'address'),
        ]);
    }

    public function terms(): void
    {
        view('legal/terms', [
            'seo' => [
                'title' => setting('legal', 'terms_meta_title', 'Terms of Service | ' . setting('general', 'site_name', 'Big Kahuna Car Hire')),
                'description' => setting('legal', 'terms_meta_description', 'Terms of Service for ' . setting('general', 'site_name', 'Big Kahuna Car Hire') . '.'),
                'keywords' => setting('seo', 'default_meta_keywords'),
                'og_image' => setting('seo', 'og_image'),
                'robots' => 'index, follow',
            ],
            'title' => setting('legal', 'terms_title', 'Terms of Service'),
            'content' => setting('legal', 'terms_of_service'),
            'lastUpdated' => setting('legal', 'terms_last_updated'),
            'siteName' => setting('general', 'site_name', 'Big Kahuna Car Hire'),
            'email' => setting('general', 'email'),
            'phone' => setting('general', 'phone_primary'),
            'address' => setting('general', 'address'),
        ]);
    }
}
