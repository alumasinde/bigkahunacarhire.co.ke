<?php
declare(strict_types=1);

/**
 * Paystack API client.
 *
 * This class only talks to Paystack. It contains no booking/business logic.
 * PAYSTACK_BASE_URL defaults to https://api.paystack.co in config/config.php.
 */
final class PaystackClient
{
    public function __construct(
        private string $secretKey = PAYSTACK_SECRET_KEY,
        private string $baseUrl = PAYSTACK_BASE_URL
    ) {}

    public function initialize(
        string $email,
        int $amountSubunit,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
        ?string $phone = null,
        array $channels = []
    ): array {
        $payload = [
            'email' => $email,
            'amount' => $amountSubunit,
            'currency' => PAYSTACK_CURRENCY,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        // Supplying the phone number as metadata keeps the transaction
        // compatible with all Paystack channels without forcing a channel.
        if ($phone !== null && $phone !== '') {
            $payload['metadata']['customer_phone'] = $phone;
        }

        // Channels are optional. When configured in Admin > Paystack,
        // Paystack will only present the selected supported channels.
        if ($channels !== []) {
            $payload['channels'] = array_values(array_unique($channels));
        }

        return $this->request('POST', '/transaction/initialize', $payload);
    }

    public function verify(string $reference): array
    {
        return $this->request(
            'GET',
            '/transaction/verify/' . rawurlencode($reference)
        );
    }

    /**
     * Paystack signs webhook requests with HMAC-SHA512 using the secret key.
     * Compare against the raw request body before decoding JSON.
     */
    public function isValidWebhookSignature(string $rawBody, string $signature): bool
    {
        if ($signature === '' || $this->secretKey === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey);
        return hash_equals($expected, $signature);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $ch = curl_init(rtrim($this->baseUrl, '/') . $path);
        if ($ch === false) {
            throw new RuntimeException('Could not initialize Paystack connection.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
        ];

        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Could not reach Paystack: ' . $curlError);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Paystack returned an invalid response.');
        }

        if ($httpCode < 200 || $httpCode >= 300 || ($decoded['status'] ?? false) !== true) {
            $message = (string)($decoded['message'] ?? 'Paystack request failed.');
            throw new RuntimeException($message);
        }

        return $decoded['data'] ?? [];
    }
}
