<?php
declare(strict_types=1);

/**
 * MpesaService — thin wrapper around Safaricom Daraja's OAuth + STK Push
 * (Lipa Na M-Pesa Online) endpoints. Credentials are read from constants
 * defined in config/config.php, which pull from .env — never from the DB.
 */
final class MpesaService
{
    public function __construct(private ?string $overrideCallbackUrl = null) {}

    /**
     * @throws RuntimeException on any transport/auth failure
     */
    public function getAccessToken(): string
    {
        if (MPESA_CONSUMER_KEY === '' || MPESA_CONSUMER_SECRET === '') {
            throw new RuntimeException('M-Pesa credentials are not configured. Set MPESA_CONSUMER_KEY / MPESA_CONSUMER_SECRET in .env.');
        }

        $url = MPESA_BASE_URL . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Could not reach M-Pesa (network error): {$error}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['access_token'])) {
            throw new RuntimeException('M-Pesa auth failed: ' . ($data['error_description'] ?? $response));
        }

        return $data['access_token'];
    }

    /**
     * Initiate an STK Push (Lipa Na M-Pesa Online) prompt on the customer's phone.
     *
     * @param string $phone      Customer phone, any of 07xx / 01xx / 2547xx / +2547xx
     * @param float  $amount     Amount to charge, in KES (whole numbers only per Daraja)
     * @param string $reference  Account reference shown to the customer (e.g. booking ref)
     * @param string $description Short transaction description
     * @return array{merchant_request_id:string, checkout_request_id:string, customer_message:string}
     * @throws RuntimeException on failure
     */
    public function stkPush(string $phone, float $amount, string $reference, string $description): array
    {
        $token = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
        $callbackUrl = $this->overrideCallbackUrl ?: (MPESA_CALLBACK_URL ?: base_url('mpesa/callback'));
        $msisdn = $this->normalizePhone($phone);

        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) round($amount),
            'PartyA'            => $msisdn,
            'PartyB'            => MPESA_SHORTCODE,
            'PhoneNumber'       => $msisdn,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => substr($reference, 0, 12),
            'TransactionDesc'   => substr($description, 0, 13),
        ];

        $ch = curl_init(MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Could not reach M-Pesa (network error): {$error}");
        }

        $data = json_decode($response, true);

        if (empty($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
            $message = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Unknown error from M-Pesa.';
            throw new RuntimeException($message);
        }

        return [
            'merchant_request_id' => $data['MerchantRequestID'],
            'checkout_request_id' => $data['CheckoutRequestID'],
            'customer_message'    => $data['CustomerMessage'] ?? 'Check your phone to complete payment.',
        ];
    }

    /**
     * Normalize a Kenyan phone number to the 2547XXXXXXXX format Daraja expects.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            $digits = '254' . $digits;
        } elseif (str_starts_with($digits, '254')) {
            // already correct
        }

        return $digits;
    }
}
