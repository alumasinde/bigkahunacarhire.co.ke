<?php
declare(strict_types=1);

final class MailService
{
    /**
     * @throws RuntimeException on failure (caller decides whether to swallow it)
     */
    public function send(string $to, string $subject, string $htmlBody): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid recipient email address: {$to}");
        }

        if (MAIL_DRIVER === 'smtp') {
            $this->sendViaSmtp($to, $subject, $htmlBody);
        } else {
            $this->sendViaPhpMail($to, $subject, $htmlBody);
        }
    }

    private function sendViaPhpMail(string $to, string $subject, string $htmlBody): void
    {
        $headers = implode("\r\n", [
            "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">",
            "Reply-To: " . MAIL_FROM_ADDRESS,
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
        ]);

        $ok = @mail($to, $subject, $htmlBody, $headers);

        if (!$ok) {
            throw new RuntimeException('PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.');
        }
    }

    private function sendViaSmtp(string $to, string $subject, string $htmlBody): void
    {
        if (SMTP_HOST === '') {
            throw new RuntimeException('MAIL_DRIVER=smtp but SMTP_HOST is not configured in .env.');
        }

        $mailer = new SmtpMailer(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_ENCRYPTION);
        $mailer->send(MAIL_FROM_ADDRESS, MAIL_FROM_NAME, $to, $subject, $htmlBody);
    }
}
