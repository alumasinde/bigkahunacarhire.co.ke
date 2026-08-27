<?php
declare(strict_types=1);

/**
 * WhatsApp delivery abstraction.
 *
 * Phase 3 supports two providers:
 * - cloud_api: official WhatsApp Business Platform Cloud API
 * - callmebot: temporary admin-only fallback
 *
 * Customer messages are only enabled when the Cloud API provider is selected
 * and approved template names are configured. This prevents accidental use
 * of a personal-number API for customer notifications.
 */
final class WhatsAppService
{
    public function provider(): string
    {
        return strtolower((string)setting('notifications', 'whatsapp_provider', 'callmebot'));
    }

    public function isCloudConfigured(): bool
    {
        return WHATSAPP_CLOUD_ACCESS_TOKEN !== '' && WHATSAPP_CLOUD_PHONE_NUMBER_ID !== '';
    }

    public function send(string $toPhone, string $message): void
    {
        $provider = $this->provider();
        if ($provider === 'cloud_api') {
            $this->sendTextCloud($toPhone, $message);
            return;
        }
        $this->sendCallMeBot($toPhone, $message);
    }

    /** Send a normal text message through the Cloud API. Use only inside an active customer conversation window. */
    public function sendText(string $toPhone, string $message): ?string
    {
        if ($this->provider() !== 'cloud_api') {
            throw new RuntimeException('Customer conversation replies require the WhatsApp Cloud API provider.');
        }
        if (!$this->isCloudConfigured()) {
            throw new RuntimeException('WhatsApp Cloud API is not configured.');
        }
        $msisdn = (new MpesaService())->normalizePhone($toPhone);
        if ($msisdn === '') {
            throw new RuntimeException("Invalid WhatsApp recipient phone number: {$toPhone}");
        }
        return $this->cloudRequest([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $msisdn,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ]);
    }

    /** Send an approved WhatsApp template using the Cloud API. */
    public function sendTemplate(string $toPhone, string $templateName, array $bodyParameters = [], string $language = 'en_US'): ?string
    {
        if (!$this->isCloudConfigured()) {
            throw new RuntimeException('WhatsApp Cloud API is not configured. Set WHATSAPP_CLOUD_ACCESS_TOKEN and WHATSAPP_CLOUD_PHONE_NUMBER_ID.');
        }
        $msisdn = (new MpesaService())->normalizePhone($toPhone);
        if ($msisdn === '') {
            throw new RuntimeException("Invalid WhatsApp recipient phone number: {$toPhone}");
        }
        $parameters = [];
        foreach ($bodyParameters as $value) {
            $parameters[] = ['type' => 'text', 'text' => (string)$value];
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $msisdn,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];
        if ($parameters) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => $parameters,
            ]];
        }
        return $this->cloudRequest($payload);
    }

    /** Verify the GET challenge sent by Meta when configuring webhooks. */
    public function verifyWebhook(array $query): ?string
    {
        $mode = (string)($query['hub_mode'] ?? $query['hub.mode'] ?? '');
        $token = (string)($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? '');
        $challenge = (string)($query['hub_challenge'] ?? $query['hub.challenge'] ?? '');
        if ($mode === 'subscribe' && $token !== '' && hash_equals(WHATSAPP_CLOUD_VERIFY_TOKEN, $token)) {
            return $challenge;
        }
        return null;
    }

    /** Validate Meta's X-Hub-Signature-256 header for POST webhooks. */
    public function verifySignature(string $rawBody, string $signature): bool
    {
        if (WHATSAPP_CLOUD_APP_SECRET === '' || !str_starts_with($signature, 'sha256=')) {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, WHATSAPP_CLOUD_APP_SECRET);
        return hash_equals($expected, $signature);
    }

    private function sendTextCloud(string $toPhone, string $message): void
    {
        $msisdn = (new MpesaService())->normalizePhone($toPhone);
        if ($msisdn === '') {
            throw new RuntimeException("Invalid WhatsApp recipient phone number: {$toPhone}");
        }
        $this->cloudRequest([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $msisdn,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ]);
    }

    private function cloudRequest(array $payload): ?string
    {
        $url = 'https://graph.facebook.com/' . rawurlencode(WHATSAPP_CLOUD_API_VERSION) . '/' . rawurlencode(WHATSAPP_CLOUD_PHONE_NUMBER_ID) . '/messages';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . WHATSAPP_CLOUD_ACCESS_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            throw new RuntimeException('WhatsApp Cloud API network error: ' . $error);
        }
        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_SLASHES) : $response;
            throw new RuntimeException('WhatsApp Cloud API HTTP ' . $httpCode . ': ' . $detail);
        }
        return $decoded['messages'][0]['id'] ?? null;
    }

    private function sendCallMeBot(string $toPhone, string $message): void
    {
        if (CALLMEBOT_APIKEY === '') {
            throw new RuntimeException('WhatsApp is not configured. Set CALLMEBOT_APIKEY or switch to the Cloud API provider.');
        }
        $msisdn = (new MpesaService())->normalizePhone($toPhone);
        if ($msisdn === '') {
            throw new RuntimeException("Invalid WhatsApp recipient phone number: {$toPhone}");
        }
        $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
            'phone' => $msisdn, 'text' => $message, 'apikey' => CALLMEBOT_APIKEY,
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) throw new RuntimeException('Could not reach CallMeBot: ' . $error);
        if ($httpCode !== 200) throw new RuntimeException('CallMeBot returned HTTP ' . $httpCode . ': ' . $response);
        if (stripos($response, 'queued') === false && stripos($response, 'success') === false) {
            throw new RuntimeException('CallMeBot did not confirm delivery: ' . strip_tags($response));
        }
    }
}
