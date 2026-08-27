<?php
declare(strict_types=1);

/**
 * NotificationService — sends booking/payment/message notifications by
 * email and/or SMS. Every send is wrapped so a failed notification never
 * breaks the booking/payment flow that triggered it — failures are logged
 * and swallowed.
 */
final class NotificationService
{
    private MailService $mail;
    private SmsService $sms;
    private WhatsAppService $whatsapp;

    public function __construct()
    {
        $this->mail = new MailService();
        $this->sms = new SmsService();
        $this->whatsapp = new WhatsAppService();
    }

    public static function make(): self
    {
        return new self();
    }

    // -----------------------------------------------------------
    // Customer-facing notifications
    // -----------------------------------------------------------

    public function notifyBookingCreated(array $booking): void
    {
        $subject = "Booking Received — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            "Hi {$booking['first_name']},",
            "We've received your booking request for the <strong>{$booking['car_name']}</strong> "
                . "(ref <strong>{$booking['booking_ref']}</strong>), from "
                . date('d M Y, H:i', strtotime($booking['pickup_date'])) . ' to '
                . date('d M Y, H:i', strtotime($booking['return_date'])) . '. '
                . 'Total: ' . money($booking['total_price']) . '.<br><br>'
                . 'Our team will confirm your reservation shortly. Keep your booking reference for follow-up and payment.'
        );
        $sms = "Big Kahuna Car Hire: Booking {$booking['booking_ref']} received for {$booking['car_name']}. "
            . "Total " . money($booking['total_price']) . ". We'll confirm shortly.";

        $this->sendBoth($booking['email'] ?? null, $booking['phone'] ?? null, $subject, $html, $sms, (int)$booking['id'], 'booking_received');
        $this->sendCustomerWhatsAppTemplate($booking, 'booking_received', [
            $booking['first_name'], $booking['booking_ref'], $booking['car_name'],
            date('d M Y, H:i', strtotime($booking['pickup_date'])),
            money($booking['total_price']),
        ]);
    }

    public function notifyPaymentReceived(array $booking, array $payment): void
    {
        [$methodLabel, $refLabel, $refValue] = $this->paymentReceiptFields($payment);

        $subject = "Payment Received — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            "Hi {$booking['first_name']},",
            "We've received your {$methodLabel} payment of <strong>" . money($payment['amount']) . "</strong> "
                . "({$refLabel} <strong>{$refValue}</strong>) for booking "
                . "<strong>{$booking['booking_ref']}</strong>. Your booking is now confirmed!"
        );
        $sms = "Big Kahuna Car Hire: {$methodLabel} payment of " . money($payment['amount']) . " received "
            . "({$refLabel} {$refValue}). Booking {$booking['booking_ref']} is confirmed.";

        $this->sendBoth($booking['email'] ?? null, $booking['phone'] ?? null, $subject, $html, $sms, (int)$booking['id'], 'payment_received');
        $this->sendCustomerWhatsAppTemplate($booking, 'payment_received', [
            $booking['first_name'], $booking['booking_ref'], money($payment['amount']), $methodLabel,
        ]);
    }

    /**
     * Resolves a human-readable method label and reference field for a
     * payment row, since Paystack payments never populate
     * mpesa_receipt_number and shouldn't be described as "M-Pesa".
     *
     * @return array{0:string,1:string,2:string}
     */
    private function paymentReceiptFields(array $payment): array
    {
        if (($payment['gateway'] ?? '') === 'paystack') {
            $channel = !empty($payment['channel']) ? ucwords(str_replace('_', ' ', (string)$payment['channel'])) : 'online';
            return ["Paystack ({$channel})", 'reference', (string)($payment['reference'] ?? '')];
        }

        $label = ($payment['payment_method'] ?? '') === 'manual' ? 'manual M-Pesa' : 'M-Pesa';
        return [$label, 'receipt', (string)($payment['mpesa_receipt_number'] ?? '')];
    }

    public function notifyBookingStatusChanged(array $booking, string $newStatus): void
    {
        $statusLabel = ucfirst($newStatus);
        $subject = "Booking {$statusLabel} — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            "Hi {$booking['first_name']},",
            "Your booking <strong>{$booking['booking_ref']}</strong> for the <strong>{$booking['car_name']}</strong> "
                . "is now <strong>{$statusLabel}</strong>."
        );
        $sms = "Big Kahuna Car Hire: Booking {$booking['booking_ref']} is now {$statusLabel}.";

        $this->sendBoth($booking['email'] ?? null, $booking['phone'] ?? null, $subject, $html, $sms, (int)$booking['id'], 'booking_' . $newStatus);
        if ($newStatus === 'confirmed') {
            $this->sendCustomerWhatsAppTemplate($booking, 'booking_confirmed', [
                $booking['first_name'], $booking['booking_ref'], $booking['car_name'],
                date('d M Y, H:i', strtotime($booking['pickup_date'])),
            ]);
        }

        // Only operationally important status changes reach admin WhatsApp.
        if (in_array($newStatus, ['confirmed', 'cancelled'], true)) {
            $adminUrl = base_url('admin/bookings/' . (int)$booking['id']);
            $emoji = $newStatus === 'confirmed' ? '✅' : '❌';
            $this->notifyAdminWhatsApp(
                $emoji . ' *BOOKING ' . strtoupper($newStatus) . "* — {$booking['booking_ref']}\n"
                . "Customer: {$booking['first_name']} {$booking['last_name']}\n"
                . "Car: {$booking['car_name']}\n"
                . "Total: " . money($booking['total_price']) . "\n\n"
                . "🔎 Open booking: {$adminUrl}",
                'admin_status_changed',
                [$booking['booking_ref'], ucfirst($newStatus), $booking['first_name'].' '.$booking['last_name'], $booking['car_name']]
            );
        }
    }

    /**
     * Sent after staff extend a rental's return date. Tells the customer
     * exactly what changed — old and new return date, and the new total
     * price for the extended period — so an extension is never a surprise
     * on the final bill. $amountPaid is what's already been paid (deposit),
     * used to show the updated balance still owing.
     */
    public function notifyBookingExtended(array $booking, string $oldReturnDate, float $amountPaid): void
    {
        $oldReturn = date('d M Y, H:i', strtotime($oldReturnDate));
        $newReturn = date('d M Y, H:i', strtotime($booking['return_date']));
        $newTotal = (float) $booking['total_price'];
        $balance = max(0, $newTotal - $amountPaid);

        $subject = "Booking Extended — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            "Hi {$booking['first_name']},",
            "Your rental <strong>{$booking['booking_ref']}</strong> for the <strong>{$booking['car_name']}</strong> "
                . "has been extended.<br><br>"
                . "<strong>Previous return:</strong> {$oldReturn}<br>"
                . "<strong>New return:</strong> {$newReturn}<br>"
                . "<strong>Updated total:</strong> " . money($newTotal) . "<br>"
                . ($balance > 0
                    ? "<strong>Balance now due:</strong> " . money($balance)
                    : "No additional balance is due.")
        );
        $sms = "Big Kahuna Car Hire: Booking {$booking['booking_ref']} extended to {$newReturn}. "
            . "Updated total " . money($newTotal) . "."
            . ($balance > 0 ? " Balance due: " . money($balance) . "." : '');

        $this->sendBoth($booking['email'] ?? null, $booking['phone'] ?? null, $subject, $html, $sms, (int)$booking['id'], 'booking_extended');
    }

    public function notifyPickupReminder(array $booking): void
    {
        if (empty($booking['whatsapp_opt_in']) || setting('notifications','whatsapp_enabled','0')!=='1' || setting('notifications','whatsapp_reminders_enabled','0')!=='1' || $this->whatsapp->provider()!=='cloud_api') return;
        $template=trim((string)setting('notifications','whatsapp_template_pickup_reminder','pickup_reminder'));
        if($template==='') return;
        try{
            $id=$this->whatsapp->sendTemplate($booking['phone'],$template,[
                $booking['first_name'],$booking['booking_ref'],$booking['car_name'],date('d M Y, H:i',strtotime($booking['pickup_date'])),$booking['pickup_location']
            ]);
            $this->logDelivery((int)$booking['id'],'whatsapp',$booking['phone'],'pickup_reminder','sent','cloud_api',$id);
        }catch(Throwable $e){$this->logDelivery((int)$booking['id'],'whatsapp',$booking['phone'],'pickup_reminder','failed','cloud_api',null,$e->getMessage());error_log('[PICKUP REMINDER] '.$e->getMessage());}
    }

    public function notifyPaymentDue(array $booking, float $balance): void
    {
        if ($balance <= 0) return;
        $this->sendCustomerWhatsAppTemplate($booking,'payment_due',[
            $booking['first_name'],$booking['booking_ref'],money($balance),date('d M Y, H:i',strtotime($booking['pickup_date']))
        ]);
        $this->notifyAdminWhatsApp(
            "💳 *PAYMENT DUE* — {$booking['booking_ref']}\nCustomer: {$booking['first_name']} {$booking['last_name']}\nBalance: ".money($balance)."\nPickup: ".date('d M Y, H:i',strtotime($booking['pickup_date'])),
            'admin_payment_due',[$booking['booking_ref'],money($balance),$booking['first_name'].' '.$booking['last_name']]
        );
    }

    public function notifyReturnReminder(array $booking): void
    {
        $this->sendCustomerWhatsAppTemplate($booking,'return_reminder',[
            $booking['first_name'],$booking['booking_ref'],$booking['car_name'],date('d M Y, H:i',strtotime($booking['return_date'])),$booking['dropoff_location']
        ]);
    }

    public function notifyRentalCompleted(array $booking): void
    {
        $this->sendCustomerWhatsAppTemplate($booking,'rental_completed',[
            $booking['first_name'],$booking['booking_ref'],$booking['car_name']
        ]);
    }

    public function notifyReviewRequest(array $booking): void
    {
        $this->sendCustomerWhatsAppTemplate($booking,'review_request',[
            $booking['first_name'],$booking['booking_ref'],$booking['car_name']
        ]);
    }

    public function sendMessageReply(array $contactMessage, string $replyText): void
    {
        $subject = 'Re: ' . ($contactMessage['subject'] ?: 'Your message to Big Kahuna Car Hire');
        $html = $this->wrapEmail(
            "Hi {$contactMessage['name']},",
            nl2br(e($replyText)) . '<br><br>'
                . '<em style="color:#8A9A9E;font-size:0.85rem;">In response to your message: '
                . '"' . e(mb_strimwidth($contactMessage['message'], 0, 140, '...')) . '"</em>'
        );

        try {
            $this->mail->send($contactMessage['email'], $subject, $html);
        } catch (Throwable $e) {
            error_log('[NOTIFICATION EMAIL ERROR] ' . $e->getMessage());
            throw $e; // the admin reply action needs to know if this failed
        }
    }

    // -----------------------------------------------------------
    // Admin-facing notifications
    // -----------------------------------------------------------

    public function notifyAdminNewBooking(array $booking): void
    {
        $adminEmail = setting('notifications', 'admin_notification_email');
        $adminPhone = setting('notifications', 'admin_notification_phone');

        $subject = "New Booking — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            'New booking received',
            "{$booking['first_name']} {$booking['last_name']} ({$booking['phone']}) booked the "
                . "<strong>{$booking['car_name']}</strong> for " . money($booking['total_price']) . '. '
                . "Ref: {$booking['booking_ref']}."
        );
        $sms = "New booking {$booking['booking_ref']}: {$booking['first_name']} {$booking['last_name']} — "
            . "{$booking['car_name']}, " . money($booking['total_price']) . '.';

        $this->sendBoth($adminEmail, $adminPhone, $subject, $html, $sms, (int)$booking['id'], 'admin_new_booking');

        $adminUrl = base_url('admin/bookings/' . (int)$booking['id']);
        $this->notifyAdminWhatsApp(
            "🚗 *NEW BOOKING* — {$booking['booking_ref']}\n\n"
                . "👤 {$booking['first_name']} {$booking['last_name']}\n"
                . "📱 {$booking['phone']}\n"
                . "🚙 {$booking['car_name']}\n"
                . "🗓 " . date('d M Y, H:i', strtotime($booking['pickup_date'])) . " → " . date('d M Y, H:i', strtotime($booking['return_date'])) . "\n"
                . "📍 {$booking['pickup_location']} → {$booking['dropoff_location']}\n"
                . "🚘 " . (($booking['driver_option'] ?? '') === 'with_driver' ? 'With chauffeur' : 'Self-drive') . "\n"
                . "💰 Total: " . money($booking['total_price']) . "\n"
                . "\n🔎 Open booking: {$adminUrl}",
            'admin_new_booking',
            [$booking['booking_ref'], $booking['first_name'].' '.$booking['last_name'], $booking['car_name'], money($booking['total_price'])]
        );
    }

    public function notifyAdminPaymentReceived(array $booking, array $payment): void
    {
        [$methodLabel, $refLabel, $refValue] = $this->paymentReceiptFields($payment);

        $adminEmail = setting('notifications', 'admin_notification_email');
        $adminPhone = setting('notifications', 'admin_notification_phone');

        $subject = "Payment Received — {$booking['booking_ref']}";
        $html = $this->wrapEmail(
            'Payment received',
            money($payment['amount']) . " received via <strong>{$methodLabel}</strong> for booking "
                . "<strong>{$booking['booking_ref']}</strong> "
                . "({$booking['first_name']} {$booking['last_name']}). " . ucfirst($refLabel) . ": {$refValue}."
        );
        $sms = "Payment received: " . money($payment['amount']) . " ({$methodLabel}) for {$booking['booking_ref']} "
            . "({$refLabel} {$refValue}).";

        $this->sendBoth($adminEmail, $adminPhone, $subject, $html, $sms, (int)$booking['id'], 'admin_payment_received');

        $adminUrl = base_url('admin/bookings/' . (int)$booking['id']);
        $this->notifyAdminWhatsApp(
            "💰 *PAYMENT RECEIVED* — {$booking['booking_ref']}\n\n"
                . 'Amount: ' . money($payment['amount']) . " via {$methodLabel}\n"
                . "Customer: {$booking['first_name']} {$booking['last_name']}\n"
                . ucfirst($refLabel) . ": {$refValue}\n\n"
                . "🔎 Open booking: {$adminUrl}",
            'admin_payment_received',
            [$booking['booking_ref'], money($payment['amount']), $methodLabel, $refValue]
        );
    }

    // -----------------------------------------------------------
    // Internals
    // -----------------------------------------------------------

    private function sendBoth(?string $email, ?string $phone, string $subject, string $html, string $smsText, ?int $bookingId = null, string $eventKey = 'notification'): void
    {
        if (setting('notifications', 'email_enabled', '1') === '1' && !empty($email)) {
            try {
                $this->mail->send($email, $subject, $html);
                $this->logDelivery($bookingId, 'email', $email, $eventKey, 'sent', 'mail');
            } catch (Throwable $e) {
                $this->logDelivery($bookingId, 'email', $email, $eventKey, 'failed', 'mail', null, $e->getMessage());
                error_log('[NOTIFICATION EMAIL ERROR] ' . $e->getMessage());
            }
        }

        if (setting('notifications', 'sms_enabled', '1') === '1' && !empty($phone)) {
            try {
                $this->sms->send($phone, $smsText);
                $this->logDelivery($bookingId, 'sms', $phone, $eventKey, 'sent', 'africastalking');
            } catch (Throwable $e) {
                $this->logDelivery($bookingId, 'sms', $phone, $eventKey, 'failed', 'africastalking', null, $e->getMessage());
                error_log('[NOTIFICATION SMS ERROR] ' . $e->getMessage());
            }
        }
    }

    private function sendCustomerWhatsAppTemplate(array $booking, string $eventKey, array $parameters): void
    {
        if (empty($booking['whatsapp_opt_in']) || setting('notifications', 'whatsapp_enabled', '0') !== '1' || setting('notifications', 'whatsapp_customer_enabled', '0') !== '1') {
            return;
        }
        if ($this->whatsapp->provider() !== 'cloud_api') {
            return;
        }
        $templateKey = match ($eventKey) {
            'booking_received' => 'whatsapp_template_booking_received',
            'booking_confirmed' => 'whatsapp_template_booking_confirmed',
            'payment_received' => 'whatsapp_template_payment_received',
            'payment_due' => 'whatsapp_template_payment_due',
            'return_reminder' => 'whatsapp_template_return_reminder',
            'rental_completed' => 'whatsapp_template_rental_completed',
            'review_request' => 'whatsapp_template_review_request',
            default => null,
        };
        if (!$templateKey || empty($booking['phone'])) return;
        $template = trim((string)setting('notifications', $templateKey, ''));
        if ($template === '') return;
        $bookingId = (int)$booking['id'];
        if (!$this->claimCustomerNotification($bookingId, 'whatsapp', $eventKey)) {
            return;
        }

        try {
            $messageId = $this->whatsapp->sendTemplate($booking['phone'], $template, $parameters);
            $this->logDelivery($bookingId, 'whatsapp', $booking['phone'], $eventKey, 'sent', 'cloud_api', $messageId);
        } catch (Throwable $e) {
            // A failed provider call must remain retryable. Release the claim
            // so the next scheduled run can try again.
            $this->releaseCustomerNotificationClaim($bookingId, 'whatsapp', $eventKey);
            $this->logDelivery($bookingId, 'whatsapp', $booking['phone'], $eventKey, 'failed', 'cloud_api', null, $e->getMessage());
            error_log('[NOTIFICATION WHATSAPP CUSTOMER ERROR] ' . $e->getMessage());
        }
    }

    /**
     * Atomically claims a customer notification event. The unique key in
     * notification_claims makes this safe even when two web requests/cron
     * workers race. Failed sends release the claim so they remain retryable.
     */
    private function claimCustomerNotification(int $bookingId, string $channel, string $eventKey): bool
    {
        try {
            $db = Database::connection();
            // Recover a claim left behind by a crashed PHP process. The
            // provider call itself has a short timeout, so 15 minutes is a
            // conservative recovery window without creating duplicate sends
            // during normal retries.
            $stale = $db->prepare(
                'DELETE FROM notification_claims
                 WHERE booking_id=:booking_id AND channel=:channel AND event_key=:event_key
                   AND claimed_at < (NOW() - INTERVAL 15 MINUTE)'
            );
            $stale->execute([
                ':booking_id' => $bookingId,
                ':channel' => $channel,
                ':event_key' => $eventKey,
            ]);

            $stmt = $db->prepare(
                'INSERT IGNORE INTO notification_claims (booking_id, channel, event_key) VALUES (:booking_id, :channel, :event_key)'
            );
            $stmt->execute([
                ':booking_id' => $bookingId,
                ':channel' => $channel,
                ':event_key' => $eventKey,
            ]);
            return $stmt->rowCount() === 1;
        } catch (Throwable $e) {
            error_log('[NOTIFICATION CLAIM ERROR] ' . $e->getMessage());
            return false;
        }
    }

    private function releaseCustomerNotificationClaim(int $bookingId, string $channel, string $eventKey): void
    {
        try {
            Database::connection()->prepare(
                'DELETE FROM notification_claims WHERE booking_id=:booking_id AND channel=:channel AND event_key=:event_key'
            )->execute([
                ':booking_id' => $bookingId,
                ':channel' => $channel,
                ':event_key' => $eventKey,
            ]);
        } catch (Throwable $e) {
            error_log('[NOTIFICATION CLAIM RELEASE ERROR] ' . $e->getMessage());
        }
    }

    private function logDelivery(?int $bookingId, string $channel, string $recipient, string $eventKey, string $status, ?string $provider = null, ?string $messageId = null, ?string $error = null): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO notification_logs (booking_id, channel, recipient, event_key, status, provider, provider_message_id, error_message, sent_at)
                 VALUES (:booking_id, :channel, :recipient, :event_key, :status, :provider, :message_id, :error, :sent_at)'
            );
            $stmt->execute([
                ':booking_id' => $bookingId, ':channel' => $channel, ':recipient' => $recipient, ':event_key' => $eventKey,
                ':status' => $status, ':provider' => $provider, ':message_id' => $messageId, ':error' => $error,
                ':sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (Throwable $e) {
            error_log('[NOTIFICATION LOG ERROR] ' . $e->getMessage());
        }
    }

    /**
     * Admin WhatsApp alert. Cloud API uses approved utility templates;
     * CallMeBot remains a temporary fallback for the admin's own number.
     */
    private function notifyAdminWhatsApp(string $message, string $eventKey = 'admin_alert', array $templateParameters = []): void
    {
        $adminWhatsapp = setting('notifications', 'admin_whatsapp_phone');
        if (setting('notifications', 'whatsapp_enabled', '0') !== '1' || empty($adminWhatsapp)) return;

        try {
            if ($this->whatsapp->provider() === 'cloud_api') {
                $templateKey = match ($eventKey) {
                    'admin_new_booking' => 'whatsapp_template_admin_new_booking',
                    'admin_payment_received' => 'whatsapp_template_admin_payment_received',
                    'admin_status_changed' => 'whatsapp_template_admin_status_changed',
                    'admin_payment_due' => 'whatsapp_template_admin_payment_due',
                    default => null,
                };
                $template = $templateKey ? trim((string)setting('notifications', $templateKey, '')) : '';
                if ($template === '') throw new RuntimeException('No approved WhatsApp admin template configured for '.$eventKey.'.');
                $messageId = $this->whatsapp->sendTemplate($adminWhatsapp, $template, $templateParameters);
                $this->logDelivery(null, 'whatsapp', $adminWhatsapp, $eventKey, 'sent', 'cloud_api', $messageId);
                return;
            }
            $this->whatsapp->send($adminWhatsapp, $message);
            $this->logDelivery(null, 'whatsapp', $adminWhatsapp, $eventKey, 'sent', 'callmebot');
        } catch (Throwable $e) {
            $this->logDelivery(null, 'whatsapp', $adminWhatsapp, $eventKey, 'failed', $this->whatsapp->provider(), null, $e->getMessage());
            error_log('[NOTIFICATION WHATSAPP ERROR] ' . $e->getMessage());
        }
    }

    private function wrapEmail(string $heading, string $bodyHtml): string
    {
        $siteName = e(setting('general', 'site_name', 'Big Kahuna Car Hire'));
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1B2426;">
            <div style="background:#0B2E3D;color:#fff;padding:20px;text-align:center;font-family:Arial,sans-serif;font-weight:bold;font-size:18px;letter-spacing:1px;">
                {$siteName}
            </div>
            <div style="padding:24px;border:1px solid #eee;border-top:none;">
                <p style="font-weight:bold;margin-top:0;">{$heading}</p>
                <p style="line-height:1.6;">{$bodyHtml}</p>
            </div>
            <div style="padding:14px;text-align:center;color:#8A9A9E;font-size:12px;">
                {$siteName} &middot; This is an automated message.
            </div>
        </div>
        HTML;
    }
}
