<?php
declare(strict_types=1);

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('admin/dashboard');
        }
        view('admin/login', ['seo' => ['title' => 'Admin Login | ' . setting('general', 'site_name'), 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]);
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Session expired, please try again.');
            redirect('admin/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::attempt($email, $password)) {
            redirect('admin/dashboard');
        }

        flash('error', 'Invalid email or password.');
        redirect('admin/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('admin/login');
    }
}
