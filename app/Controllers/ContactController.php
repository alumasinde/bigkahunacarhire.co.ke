<?php
declare(strict_types=1);

final class ContactController
{
    public function index(): void
    {
        view('contact', ['seo' => seo_for('contact')]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            flash('error', 'Your session expired, please try again.');
            redirect('contact');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please fill in your name, a valid email, and a message.');
            redirect('contact');
        }

        ContactService::make()->create([
            'name'    => $name,
            'email'   => $email,
            'phone'   => trim($_POST['phone'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => $message,
        ]);

        flash('success', 'Thanks for reaching out! We will get back to you shortly.');
        redirect('contact');
    }
}
