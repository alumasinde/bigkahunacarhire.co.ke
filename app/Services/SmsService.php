<?php
declare(strict_types=1);

final class SmsService
{
    /**
     * @throws RuntimeException on failure (caller decides whether to swallow it)
     */
    public function send(string $to, string $message): void
    {
        if (AT_USERNAME === '' || AT_API_KEY === '') {
            throw new RuntimeException('SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).');
        }

        $msisdn = (new MpesaService())->normalizePhone($to);
        $msisdn = '+' . $msisdn;

        $payload = [
            'username' => AT_USERNAME,
            'to'       => $msisdn,
            'message'  => $message,
        ];
        if (AT_SENDER_ID !== '') {
            $payload['from'] = AT_SENDER_ID;
        }

        $ch = curl_init(AT_BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => [
                'apiKey: ' . AT_API_KEY,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Could not reach Africa's Talking (network error): {$error}");
        }

        $data = json_decode($response, true);
        $recipients = $data['SMSMessageData']['Recipients'] ?? [];
        $status = $recipients[0]['status'] ?? null;

        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new RuntimeException('SMS gateway returned HTTP ' . $httpCode . ': ' . $response);
        }
        if ($status !== null && $status !== 'Success') {
            throw new RuntimeException('SMS not sent: ' . $status);
        }
    }
}
