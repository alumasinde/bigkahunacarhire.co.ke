<?php
declare(strict_types=1);

final class PaymentController
{
    private function bookingOwnedOrToken(array $booking, ?string $token = null): bool
    {
        if (CustomerAuth::check() && (int)$booking['customer_id'] === (int)CustomerAuth::id()) return true;
        if (isset($_SESSION['last_booking_id']) && (int)$_SESSION['last_booking_id'] === (int)$booking['id']) return true;
        return $token !== null && $token !== '' && BookingService::make()->verifyPublicTokenForBooking((int)$booking['id'], $token);
    }

    private function stkAvailable(): bool
    {
        return setting('mpesa', 'enabled', '1') === '1'
            && setting('mpesa', 'stk_enabled', '1') === '1'
            && MPESA_CONSUMER_KEY !== ''
            && MPESA_CONSUMER_SECRET !== ''
            && MPESA_PASSKEY !== ''
            && MPESA_SHORTCODE !== '';
    }

    /**
     * Determine the next customer payment. The first payment reaches the
     * configured deposit target; once that target is met, the next payment
     * is the remaining rental balance. This keeps Paystack/M-Pesa flows
     * consistent and prevents the UI from getting stuck on "Pay Deposit".
     *
     * @return array{amount:float,purpose:string,paid:float,balance:float,deposit_target:float}
     */
    private function customerPaymentDue(array $booking): array
    {
        $total = max(0.0, (float)$booking['total_price']);
        $paid = PaymentService::make()->completedTotalForBooking((int)$booking['id']);
        $depositPct = max(1, min(100, (float)setting('paystack', 'deposit_percentage', '30')));
        $depositTarget = round($total * ($depositPct / 100), 2);
        $balance = max(0.0, round($total - $paid, 2));

        if ($balance <= 0.009) {
            return [
                'amount' => 0.0,
                'purpose' => 'paid',
                'paid' => $paid,
                'balance' => 0.0,
                'deposit_target' => $depositTarget,
            ];
        }

        $depositRemaining = max(0.0, round($depositTarget - $paid, 2));
        if ($depositRemaining > 0.009) {
            return [
                'amount' => min($balance, $depositRemaining),
                'purpose' => 'deposit',
                'paid' => $paid,
                'balance' => $balance,
                'deposit_target' => $depositTarget,
            ];
        }

        return [
            'amount' => $balance,
            'purpose' => 'balance',
            'paid' => $paid,
            'balance' => $balance,
            'deposit_target' => $depositTarget,
        ];
    }

    public function initiate(int $bookingId): void
    {
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Session expired, please refresh the page.'], 419);
        }

        if (!$this->stkAvailable()) {
            json_response([
                'success' => false,
                'message' => 'STK Push is not configured yet. Use the manual M-Pesa payment option below.'
            ], 503);
        }

        $booking = BookingService::make()->find($bookingId);
        if (!$booking) {
            json_response(['success' => false, 'message' => 'Booking not found.'], 404);
        }
        if (!$this->bookingOwnedOrToken($booking, trim((string)($_POST['public_token'] ?? '')))) {
            json_response(['success'=>false,'message'=>'Please open your booking from your customer account or the booking confirmation page.'],403);
        }
        if (in_array($booking['status'], ['completed','cancelled'], true)) {
            json_response(['success' => false, 'message' => 'This booking cannot receive a new payment.'], 409);
        }

        $phone = trim((string)($_POST['phone'] ?? ''));
        $normalized = (new MpesaService())->normalizePhone($phone);
        if (!preg_match('/^254(?:7|1)\d{8}$/', $normalized)) {
            json_response(['success' => false, 'message' => 'Enter a valid Kenyan M-Pesa number, e.g. 0712 345 678.'], 422);
        }

        $due = $this->customerPaymentDue($booking);
        if ($due['amount'] <= 0.009) {
            json_response(['success' => false, 'message' => 'This booking is fully paid. No further payment is required.'], 409);
        }
        $amount = max(1, (int)round($due['amount']));

        $paymentService = PaymentService::make();
        $paymentId = $paymentService->create($bookingId, $normalized, $amount, 'stk', null, (string)$due['purpose']);

        try {
            $result = (new MpesaService())->stkPush(
                $normalized,
                $amount,
                setting('mpesa', 'account_reference_prefix', 'KAHUNA') . '-' . $booking['booking_ref'],
                ($due['purpose'] === 'balance' ? 'Car hire balance payment' : 'Car hire deposit')
            );
            $paymentService->attachCheckoutIds($paymentId, $result['checkout_request_id'], $result['merchant_request_id']);

            json_response([
                'success' => true,
                'payment_id' => $paymentId,
                'checkout_request_id' => $result['checkout_request_id'],
                'message' => $result['customer_message'],
            ]);
        } catch (Throwable $e) {
            error_log('[MPESA STK PUSH ERROR] ' . $e->getMessage());
            json_response([
                'success' => false,
                'message' => 'We could not start the M-Pesa prompt. Use the manual M-Pesa option below.'
            ], 502);
        }
    }

    /**
     * Manual fallback: customer pays the configured business number and
     * submits the M-Pesa transaction code. This never claims the payment
     * is verified until staff confirms it.
     */
    public function manualPay(int $bookingId): void
    {
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Session expired, please refresh the page.'], 419);
        }

        if (setting('mpesa', 'manual_enabled', '1') !== '1') {
            json_response(['success' => false, 'message' => 'Manual M-Pesa payment is currently unavailable.'], 503);
        }

        $booking = BookingService::make()->find($bookingId);
        if (!$booking) {
            json_response(['success' => false, 'message' => 'Booking not found.'], 404);
        }
        if (!$this->bookingOwnedOrToken($booking, trim((string)($_POST['public_token'] ?? '')))) {
            json_response(['success'=>false,'message'=>'Please open your booking from your customer account or the booking confirmation page.'],403);
        }
        if (in_array($booking['status'], ['completed','cancelled'], true)) {
            json_response(['success' => false, 'message' => 'This booking cannot receive a new payment.'], 409);
        }

        $phone = trim((string)($_POST['phone'] ?? $booking['phone']));
        $receipt = strtoupper(trim((string)($_POST['receipt'] ?? '')));
        $normalized = (new MpesaService())->normalizePhone($phone);

        if (!preg_match('/^254(?:7|1)\d{8}$/', $normalized)) {
            json_response(['success' => false, 'message' => 'Enter a valid Kenyan M-Pesa number.'], 422);
        }

        // Safaricom M-Pesa receipt codes are normally alphanumeric. Keep the
        // validation deliberately conservative and reject overly long input.
        if (!preg_match('/^[A-Z0-9]{8,20}$/', $receipt)) {
            json_response(['success' => false, 'message' => 'Enter the M-Pesa transaction code exactly as shown in your SMS.'], 422);
        }

        $due = $this->customerPaymentDue($booking);
        if ($due['amount'] <= 0.009) {
            json_response(['success' => false, 'message' => 'This booking is fully paid. No further payment is required.'], 409);
        }
        $amount = max(1, (int)round($due['amount']));

        $paymentService = PaymentService::make();
        $payment = $paymentService->latestPendingManualForBooking($bookingId);
        $paymentId = $payment
            ? (int)$payment['id']
            : $paymentService->create(
                $bookingId,
                $normalized,
                $amount,
                'manual',
                setting('mpesa', 'manual_recipient_phone'),
                (string)$due['purpose']
            );

        if (!$paymentService->submitManualReceipt($paymentId, $receipt)) {
            json_response(['success' => false, 'message' => 'Could not submit the transaction code. It may already be under review.'], 409);
        }

        json_response([
            'success' => true,
            'payment_id' => $paymentId,
            'status' => 'pending_verification',
            'message' => 'Payment submitted. Our team will verify the M-Pesa transaction before confirming your booking.'
        ]);
    }

    public function status(string $checkoutRequestId): void
    {
        $payment = PaymentService::make()->findByCheckoutId($checkoutRequestId);
        if (!$payment) {
            json_response(['status' => 'not_found']);
        }
        $booking=BookingService::make()->find((int)$payment['booking_id']);
        if (!$booking || !$this->bookingOwnedOrToken($booking, trim((string)($_GET['public_token'] ?? '')))) {
            json_response(['status'=>'forbidden'],403);
        }
        json_response([
            'status' => $payment['status'],
            'receipt' => $payment['mpesa_receipt_number'],
            'resultDesc' => $payment['result_desc'],
        ]);
    }

    public function callback(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        error_log('[MPESA CALLBACK] ' . $raw);
        $payload = json_decode($raw, true);
        $stkCallback = $payload['Body']['stkCallback'] ?? null;

        if (!$stkCallback) {
            json_response(['ResultCode' => 1, 'ResultDesc' => 'Invalid payload'], 400);
        }

        $checkoutRequestId = (string)($stkCallback['CheckoutRequestID'] ?? '');
        $resultCode = (string)($stkCallback['ResultCode'] ?? '1');
        $resultDesc = (string)($stkCallback['ResultDesc'] ?? 'Unknown result');

        $paymentService = PaymentService::make();
        $payment = $paymentService->findByCheckoutId($checkoutRequestId);
        if (!$payment) {
            json_response(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if ($resultCode === '0') {
            $receipt = '';
            $callbackAmount = null;
            foreach (($stkCallback['CallbackMetadata']['Item'] ?? []) as $item) {
                $name = (string)($item['Name'] ?? '');
                if ($name === 'MpesaReceiptNumber') {
                    $receipt = (string)($item['Value'] ?? '');
                } elseif ($name === 'Amount') {
                    $callbackAmount = (float)($item['Value'] ?? 0);
                }
            }

            if ($receipt === '' || $callbackAmount === null || abs($callbackAmount - (float)$payment['amount']) > 0.009) {
                $paymentService->markFailed($checkoutRequestId, 'AMOUNT_MISMATCH', 'M-Pesa callback amount or receipt could not be validated.', $raw);
                json_response(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            $completedNow = $paymentService->markCompleted($checkoutRequestId, $receipt, $resultDesc, $raw);
            if ($completedNow) {
                $bookingService = BookingService::make();
                $booking = $bookingService->find((int)$payment['booking_id']);
                if ($booking && $booking['status'] === 'pending') {
                    $bookingService->updateStatus((int)$payment['booking_id'], 'confirmed');
                    $booking = $bookingService->find((int)$payment['booking_id']) ?: $booking;
                }
                $completedPayment = $paymentService->findByCheckoutId($checkoutRequestId);
                if ($booking && $completedPayment) {
                    NotificationService::make()->notifyPaymentReceived($booking, $completedPayment);
                    NotificationService::make()->notifyAdminPaymentReceived($booking, $completedPayment);
                }
            }
        } else {
            $paymentService->markFailed($checkoutRequestId, $resultCode, $resultDesc, $raw);
        }

        json_response(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    private function paystackAvailable(): bool
    {
        return PAYSTACK_ENABLED && PAYSTACK_SECRET_KEY !== '' && setting('paystack', 'enabled', '1') === '1';
    }

    public function initiatePaystack(int $bookingId): void
    {
        // This endpoint is consumed by fetch(), so every exit path must return JSON.
        // Unexpected database/configuration errors must not render the HTML 500 page.
        try {
            if (!verify_csrf()) {
                json_response(['success' => false, 'message' => 'Session expired, please refresh the page.'], 419);
            }

            if (!$this->paystackAvailable()) {
                json_response(['success' => false, 'message' => 'Paystack is not configured yet.'], 503);
            }

            $booking = BookingService::make()->find($bookingId);
            if (!$booking) {
                json_response(['success' => false, 'message' => 'Booking not found.'], 404);
            }

            if (!$this->bookingOwnedOrToken($booking, trim((string)($_POST['public_token'] ?? '')))) {
                json_response(['success'=>false,'message'=>'Please open your booking from your customer account or the booking confirmation page.'],403);
            }

            if (in_array($booking['status'], ['completed','cancelled'], true)) {
                json_response(['success' => false, 'message' => 'This booking cannot receive a new payment.'], 409);
            }

            $email = trim((string)$booking['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['success' => false, 'message' => 'A valid email address is required for Paystack.'], 422);
            }

            $phone = trim((string)$booking['phone']);

            $paymentService = PaymentService::make();

            $due = $this->customerPaymentDue($booking);
            $amount = (float)$due['amount'];
            if ($amount <= 0.009) {
                json_response([
                    'success' => false,
                    'status' => 'paid',
                    'message' => 'This booking is fully paid. No further payment is required.'
                ], 409);
            }

            // Do not blindly resume an old Paystack access code. An access code
            // can no longer be usable after the transaction has been completed
            // or abandoned. That produces Paystack's "could not start this
            // transaction" screen. A new checkout attempt gets a fresh reference
            // and access code instead. Completed payments are handled above and
            // remain authoritative.
            $existing = $paymentService->latestPendingPaystackForBooking($bookingId);
            if ($existing) {
                $paymentService->supersedePendingPaystackForBooking($bookingId);
            }

            $reference = 'KAHUNA-' . $booking['booking_ref'] . '-' . strtoupper(bin2hex(random_bytes(4)));
            $paymentId = $paymentService->createPaystack(
                $bookingId,
                $phone,
                $email,
                $amount,
                $reference,
                (string)$due['purpose']
            );

            $channels = array_values(array_filter(array_map(
                'trim',
                explode(',', (string)setting('paystack', 'channels', 'card,mobile_money,bank_transfer'))
            )));
            $allowedChannels = ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer', 'eft'];
            $channels = array_values(array_intersect($channels, $allowedChannels));

            $result = (new PaystackClient())->initialize(
                $email,
                (int)round($amount * 100),
                $reference,
                PAYSTACK_CALLBACK_URL,
                [
                    'booking_id' => $bookingId,
                    'booking_reference' => (string)$booking['booking_ref'],
                    'payment_id' => $paymentId,
                    'custom_filters' => [
                        'recurring' => false,
                    ],
                ],
                $phone,
                $channels
            );

            $accessCode = trim((string)($result['access_code'] ?? ''));
            $authorizationUrl = trim((string)($result['authorization_url'] ?? ''));
            if ($accessCode === '' || $authorizationUrl === '') {
                throw new RuntimeException('Paystack did not return a usable access code.');
            }

            $paymentService->attachPaystackInitialization($reference, $accessCode, $authorizationUrl);

            json_response([
                'success' => true,
                'payment_id' => $paymentId,
                'reference' => $reference,
                'access_code' => $accessCode,
            ]);
        } catch (Throwable $e) {
            error_log('[PAYSTACK INITIALIZE ERROR] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $message = 'We could not start the online payment. Please try again.';
            $raw = strtolower($e->getMessage());
            if (str_contains($raw, 'paystack_base_url') || str_contains($raw, 'undefined constant')) {
                $message = 'Paystack configuration is incomplete. Please add PAYSTACK_BASE_URL=https://api.paystack.co to your .env file.';
            } elseif (str_contains($raw, 'unknown column') || str_contains($raw, 'payments')) {
                $message = 'The Paystack payment database migration has not been completed. Please run migrations 013 and 014.';
            } elseif (APP_ENV !== 'production') {
                $message = 'Payment setup error: ' . $e->getMessage();
            }

            json_response(['success' => false, 'message' => $message], 502);
        }
    }

    /**
     * Browser-side reconciliation endpoint. The Paystack popup can report
     * success before the webhook arrives, so the browser can ask the server
     * to verify the exact reference immediately. This endpoint never trusts
     * the browser's success state; Paystack is queried server-to-server.
     */
    /**
     * Initialize the outstanding rental balance from the admin handover screen.
     * This is deliberately separate from the customer's booking-deposit flow.
     */
    public function initiateHandoverBalance(int $bookingId): void
    {
        Auth::requirePermission('bookings.manage');

        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Session expired. Please refresh and try again.'], 419);
        }

        $booking = BookingService::make()->find($bookingId);
        if (!$booking) {
            json_response(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (!in_array($booking['status'], ['confirmed'], true)) {
            json_response([
                'success' => false,
                'message' => 'Only a confirmed booking can receive the pre-handover balance.'
            ], 409);
        }

        if (!PAYSTACK_ENABLED || PAYSTACK_SECRET_KEY === '' || setting('paystack', 'enabled', '1') !== '1') {
            json_response(['success' => false, 'message' => 'Paystack online payments are currently disabled.'], 503);
        }

        $paymentService = PaymentService::make();
        $paid = $paymentService->completedTotalForBooking($bookingId);
        $remaining = max(0.0, round((float)$booking['total_price'] - $paid, 2));

        if ($remaining <= 0.009) {
            json_response([
                'success' => false,
                'status' => 'paid',
                'message' => 'The rental balance is already fully paid.'
            ], 409);
        }

        $email = trim((string)$booking['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'A valid customer email is required for Paystack.'], 422);
        }

        // Do not leave multiple active attempts for the same handover balance.
        $paymentService->supersedePendingPaystackForBooking($bookingId);

        $reference = 'KAHUNA-BAL-' . $booking['booking_ref'] . '-' . strtoupper(bin2hex(random_bytes(4)));
        $paymentId = $paymentService->createPaystack(
            $bookingId,
            trim((string)$booking['phone']),
            $email,
            $remaining,
            $reference
        ,
            'balance'
        );

        try {
            $channels = array_values(array_filter(array_map(
                'trim',
                explode(',', (string)setting('paystack', 'channels', 'card,mobile_money,bank_transfer'))
            )));
            $allowedChannels = ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer', 'eft'];
            $channels = array_values(array_intersect($channels, $allowedChannels));

            $result = (new PaystackClient())->initialize(
                $email,
                (int)round($remaining * 100),
                $reference,
                PAYSTACK_CALLBACK_URL,
                [
                    'booking_id' => $bookingId,
                    'booking_reference' => (string)$booking['booking_ref'],
                    'payment_id' => $paymentId,
                    'payment_purpose' => 'rental_balance_before_handover',
                ],
                trim((string)$booking['phone']),
                $channels
            );

            $accessCode = trim((string)($result['access_code'] ?? ''));
            $authorizationUrl = trim((string)($result['authorization_url'] ?? ''));
            if ($accessCode === '' || $authorizationUrl === '') {
                throw new RuntimeException('Paystack did not return a usable access code.');
            }

            $paymentService->attachPaystackInitialization($reference, $accessCode, $authorizationUrl);

            json_response([
                'success' => true,
                'payment_id' => $paymentId,
                'reference' => $reference,
                'access_code' => $accessCode,
                'amount' => $remaining,
                'currency' => PAYSTACK_CURRENCY,
            ]);
        } catch (Throwable $e) {
            error_log('[PAYSTACK HANDOVER BALANCE ERROR] ' . $e->getMessage());
            json_response([
                'success' => false,
                'message' => APP_ENV === 'production'
                    ? 'We could not start the balance payment. Please try again.'
                    : 'Payment setup error: ' . $e->getMessage()
            ], 502);
        }
    }

    public function adminPaystackStatus(string $reference): void
    {
        Auth::requirePermission('bookings.manage');

        $reference = trim($reference);
        $paymentService = PaymentService::make();
        $payment = $paymentService->findByReference($reference);

        if (!$payment || ($payment['gateway'] ?? '') !== 'paystack') {
            json_response(['success' => false, 'status' => 'not_found'], 404);
        }

        try {
            if (($payment['status'] ?? '') === 'pending') {
                $verified = (new PaystackClient())->verify($reference);
                $this->processPaystackVerification(
                    $payment,
                    $verified,
                    json_encode(['source' => 'admin_handover_verify', 'data' => $verified], JSON_UNESCAPED_SLASHES)
                );
                $payment = $paymentService->findByReference($reference) ?: $payment;
            }

            $paid = $paymentService->completedTotalForBooking((int)$payment['booking_id']);
            $booking = BookingService::make()->find((int)$payment['booking_id']);
            $remaining = $booking
                ? max(0.0, round((float)$booking['total_price'] - $paid, 2))
                : 0.0;

            json_response([
                'success' => true,
                'status' => (string)$payment['status'],
                'reference' => (string)$payment['reference'],
                'paid_amount' => $paid,
                'remaining_balance' => $remaining,
                'message' => $payment['status'] === 'completed'
                    ? 'Payment confirmed.'
                    : ((string)($payment['result_desc'] ?? 'Payment is still being processed.')),
            ]);
        } catch (Throwable $e) {
            error_log('[ADMIN PAYSTACK VERIFY ERROR] ' . $e->getMessage());
            json_response([
                'success' => false,
                'status' => 'verification_pending',
                'message' => 'We are still confirming the payment. Please wait a moment.'
            ], 202);
        }
    }

    public function paystackStatus(string $reference): void
    {
        $reference = trim($reference);
        $paymentService = PaymentService::make();
        $payment = $paymentService->findByReference($reference);

        if (!$payment || ($payment['gateway'] ?? '') !== 'paystack') {
            json_response(['success' => false, 'status' => 'not_found'], 404);
        }

        $booking = BookingService::make()->find((int)$payment['booking_id']);
        $owned = $booking && $this->bookingOwnedOrToken($booking, trim((string)($_GET['public_token'] ?? '')));
        if (!$owned) {
            json_response(['success' => false, 'status' => 'forbidden'], 403);
        }

        try {
            if (($payment['status'] ?? '') === 'pending') {
                $verified = (new PaystackClient())->verify($reference);
                $this->processPaystackVerification(
                    $payment,
                    $verified,
                    json_encode(['source' => 'browser_verify', 'data' => $verified], JSON_UNESCAPED_SLASHES)
                );
                $payment = $paymentService->findByReference($reference) ?: $payment;
                $booking = BookingService::make()->find((int)$payment['booking_id']) ?: $booking;
            }

            json_response([
                'success' => true,
                'status' => (string)$payment['status'],
                'booking_status' => (string)($booking['status'] ?? 'pending'),
                'reference' => (string)$payment['reference'],
                'channel' => (string)($payment['channel'] ?? ''),
                'message' => $payment['status'] === 'completed'
                    ? 'Payment confirmed.'
                    : ((string)($payment['result_desc'] ?? 'Payment is still being processed.')),
            ]);
        } catch (Throwable $e) {
            error_log('[PAYSTACK BROWSER VERIFY ERROR] ' . $e->getMessage());
            json_response([
                'success' => false,
                'status' => 'verification_pending',
                'message' => 'We are still confirming the payment. Please wait a moment and try again.'
            ], 202);
        }
    }

    public function paystackCallback(): void
    {
        $reference = trim((string)($_GET['reference'] ?? ''));
        if ($reference === '') {
            redirect('book/confirmation');
            return;
        }

        $paymentService = PaymentService::make();
        $payment = $paymentService->findByReference($reference);

        if (!$payment || ($payment['gateway'] ?? '') !== 'paystack') {
            redirect('book/confirmation');
            return;
        }

        try {
            $data = (new PaystackClient())->verify($reference);
            $this->processPaystackVerification($payment, $data, json_encode([
                'source' => 'callback',
                'data' => $data,
            ], JSON_UNESCAPED_SLASHES));

            redirect('book/confirmation?id=' . (int)$payment['booking_id']);
        } catch (Throwable $e) {
            error_log('[PAYSTACK CALLBACK ERROR] ' . $e->getMessage());
            redirect('book/confirmation?id=' . (int)$payment['booking_id']);
        }
    }

    public function paystackWebhook(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $signature = (string)($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');

        if (!(new PaystackClient())->isValidWebhookSignature($raw, $signature)) {
            http_response_code(401);
            echo 'Invalid signature';
            return;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo 'Invalid payload';
            return;
        }

        $event = (string)($payload['event'] ?? '');
        $data = $payload['data'] ?? [];
        $reference = trim((string)($data['reference'] ?? ''));

        if ($event === 'charge.success' && $reference !== '') {
            $payment = PaymentService::make()->findByReference($reference);

            if ($payment && ($payment['gateway'] ?? '') === 'paystack') {
                try {
                    // Verify server-to-server as a second layer before changing
                    // the booking/payment state.
                    $verified = (new PaystackClient())->verify($reference);
                    $this->processPaystackVerification(
                        $payment,
                        $verified,
                        $raw
                    );
                } catch (Throwable $e) {
                    error_log('[PAYSTACK WEBHOOK VERIFY ERROR] ' . $e->getMessage());
                    http_response_code(500);
                    echo 'Verification failed';
                    return;
                }
            }
        }

        http_response_code(200);
        echo 'OK';
    }

    private function processPaystackVerification(
        array $payment,
        array $data,
        string $rawPayload
    ): void {
        $status = strtolower((string)($data['status'] ?? ''));
        $currency = strtoupper((string)($data['currency'] ?? ''));
        $expectedAmount = (int)round((float)$payment['amount'] * 100);
        $paidAmount = (int)($data['amount'] ?? 0);

        if ($currency !== strtoupper(PAYSTACK_CURRENCY)) {
            PaymentService::make()->markPaystackFailed(
                (string)$payment['reference'],
                'currency_mismatch',
                'Payment currency does not match the booking.',
                $rawPayload
            );
            return;
        }

        if ($paidAmount !== $expectedAmount) {
            PaymentService::make()->markPaystackFailed(
                (string)$payment['reference'],
                'amount_mismatch',
                'Payment amount does not match the required booking payment.',
                $rawPayload
            );
            return;
        }

        if ($status !== 'success') {
            // Paystack has non-terminal states such as pending, ongoing and
            // processing. Keep those payments pending. Only terminal states
            // should close an attempt as failed.
            if (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                PaymentService::make()->markPaystackFailed(
                    (string)$payment['reference'],
                    $status,
                    (string)($data['gateway_response'] ?? 'Payment was not successful.'),
                    $rawPayload
                );
            }
            return;
        }

        $paymentService = PaymentService::make();
        $completedNow = $paymentService->markPaystackCompleted(
            (string)$payment['reference'],
            (string)($data['channel'] ?? 'unknown'),
            $data,
            $rawPayload
        );

        // Re-read the payment because a webhook and browser verification can
        // race each other. Either request may perform the transition, but both
        // must converge on the same completed booking state.
        $completedPayment = $paymentService->findByReference((string)$payment['reference']);
        if (!$completedPayment || ($completedPayment['status'] ?? '') !== 'completed') {
            return;
        }

        $bookingService = BookingService::make();
        $booking = $bookingService->find((int)$payment['booking_id']);

        // A deposit payment confirms a pending booking. A balance payment made
        // during handover must never move an already ongoing rental backwards.
        if ($booking && $booking['status'] === 'pending') {
            $bookingService->updateStatus((int)$payment['booking_id'], 'confirmed');
            $booking = $bookingService->find((int)$payment['booking_id']) ?: $booking;
        }

        // Only the request that changed pending -> completed sends notifications.
        if ($completedNow && $booking) {
            NotificationService::make()->notifyPaymentReceived($booking, $completedPayment);
            NotificationService::make()->notifyAdminPaymentReceived($booking, $completedPayment);
        }
    }

}
