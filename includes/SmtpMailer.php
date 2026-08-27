<?php
declare(strict_types=1);

/**
 * SmtpMailer — minimal SMTP client (no Composer/PHPMailer dependency).
 * Supports AUTH LOGIN and STARTTLS, which covers Gmail, SendGrid, Mailgun,
 * and most transactional SMTP providers.
 *
 * Only used when MAIL_DRIVER=smtp. The default MAIL_DRIVER=mail uses PHP's
 * built-in mail() instead, which needs no configuration on most cPanel hosts.
 */
final class SmtpMailer
{
    private $socket;

    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $encryption = 'tls'
    ) {}

    /**
     * @throws RuntimeException on any SMTP transport/auth failure
     */
    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): void
    {
        $transport = $this->encryption === 'ssl' ? 'ssl://' : '';
        $this->socket = @fsockopen($transport . $this->host, $this->port, $errno, $errstr, 15);

        if (!$this->socket) {
            throw new RuntimeException("Could not connect to SMTP server: {$errstr} ({$errno})");
        }

        $this->read(); // greeting
        $this->command("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        if ($this->encryption === 'tls') {
            $this->command("STARTTLS");
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $this->command("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        if ($this->username !== '') {
            $this->command("AUTH LOGIN");
            $this->command(base64_encode($this->username));
            $this->command(base64_encode($this->password));
        }

        $this->command("MAIL FROM:<{$fromEmail}>");
        $this->command("RCPT TO:<{$to}>");
        $this->command("DATA");

        $headers = [
            "From: {$fromName} <{$fromEmail}>",
            "To: <{$to}>",
            "Subject: {$this->encodeHeader($subject)}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Date: " . date('r'),
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        $this->command($message);
        $this->command("QUIT");

        fclose($this->socket);
    }

    private function command(string $cmd): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->read();
    }

    private function read(): string
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new RuntimeException("SMTP error: " . trim($response));
        }

        return $response;
    }

    private function encodeHeader(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
}
