<?php
declare(strict_types=1);

final class PaymentService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function create(int $bookingId, string $phone, float $amount, string $method = 'stk', ?string $manualRecipient = null, string $purpose = 'deposit'): int
    {
        $method = in_array($method, ['stk', 'manual'], true) ? $method : 'stk';
        $purpose = in_array($purpose, ['deposit', 'balance', 'other'], true) ? $purpose : 'other';
        $stmt = $this->db->prepare(
            'INSERT INTO payments (booking_id, payment_method, payment_purpose, phone, amount, manual_recipient, status)
             VALUES (:booking_id, :method, :purpose, :phone, :amount, :manual_recipient, "pending")'
        );
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':method' => $method,
            ':phone' => $phone,
            ':amount' => $amount,
            ':manual_recipient' => $manualRecipient,
            ':purpose' => $purpose,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function latestPendingManualForBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments
             WHERE booking_id = :booking_id AND payment_method = 'manual' AND status = 'pending'
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function submitManualReceipt(int $paymentId, string $receipt): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET mpesa_receipt_number = :receipt, result_code = 'MANUAL_PENDING',
                 result_desc = 'Manual M-Pesa transaction submitted for verification'
             WHERE id = :id AND payment_method = 'manual' AND status = 'pending'"
        );
        return $stmt->execute([':receipt' => $receipt, ':id' => $paymentId]);
    }

    public function verifyManual(int $paymentId, int $adminUserId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET status = 'completed', result_code = '0',
                 result_desc = 'Manual M-Pesa payment verified by staff',
                 manual_verified_by = :admin_id, manual_verified_at = NOW()
             WHERE id = :id AND payment_method = 'manual' AND status = 'pending'
               AND mpesa_receipt_number IS NOT NULL AND mpesa_receipt_number <> ''"
        );
        return $stmt->execute([':admin_id' => $adminUserId, ':id' => $paymentId]);
    }

    public function rejectManual(int $paymentId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET status = 'failed', result_code = 'MANUAL_REJECTED',
                 result_desc = 'Manual M-Pesa payment rejected by staff'
             WHERE id = :id AND payment_method = 'manual' AND status = 'pending'"
        );
        return $stmt->execute([':id' => $paymentId]);
    }

    public function attachCheckoutIds(int $paymentId, string $checkoutRequestId, string $merchantRequestId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE payments SET checkout_request_id = :cid, merchant_request_id = :mid WHERE id = :id'
        );
        return $stmt->execute([':cid' => $checkoutRequestId, ':mid' => $merchantRequestId, ':id' => $paymentId]);
    }

    public function findByCheckoutId(string $checkoutRequestId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE checkout_request_id = :cid LIMIT 1');
        $stmt->execute([':cid' => $checkoutRequestId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Returns the payment that should represent the booking in customer/admin UI.
     * A completed payment always wins over a newer pending/failed attempt so an
     * abandoned second checkout cannot make a successfully paid booking look unpaid.
     */
    public function latestForBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments
             WHERE booking_id = :booking_id
             ORDER BY
               CASE status WHEN 'completed' THEN 0 WHEN 'pending' THEN 1 WHEN 'failed' THEN 2 ELSE 3 END,
               created_at DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function completedTotalForBooking(int $bookingId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE booking_id = :booking_id
               AND status = 'completed'"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        return (float)$stmt->fetchColumn();
    }

    public function latestCompletedForBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments
             WHERE booking_id = :booking_id AND status = 'completed'
             ORDER BY created_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function completedPaymentsForBooking(int $bookingId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments
             WHERE booking_id = :booking_id AND status = 'completed'
             ORDER BY created_at ASC, id ASC"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function latestPendingPaystackForBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments
             WHERE booking_id = :booking_id
               AND gateway = 'paystack'
               AND payment_method = 'online'
               AND status = 'pending'
             ORDER BY created_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Close unfinished Paystack attempts before creating a fresh checkout.
     * This preserves payment history while preventing stale access codes from
     * being offered to the customer.
     */
    public function supersedePendingPaystackForBooking(int $bookingId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET status = 'failed',
                 result_code = 'superseded',
                 result_desc = 'Payment attempt replaced by a new checkout.'
             WHERE booking_id = :booking_id
               AND gateway = 'paystack'
               AND payment_method = 'online'
               AND status = 'pending'"
        );
        return $stmt->execute([':booking_id' => $bookingId]);
    }

    public function attachPaystackInitialization(
        string $reference,
        string $accessCode,
        string $authorizationUrl
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE payments
             SET paystack_access_code = :access_code,
                 authorization_url = :authorization_url
             WHERE reference = :reference'
        );
        return $stmt->execute([
            ':access_code' => $accessCode,
            ':authorization_url' => $authorizationUrl,
            ':reference' => $reference,
        ]);
    }

    public function createPaystack(
        int $bookingId,
        string $phone,
        string $email,
        float $amount,
        string $reference,
        string $purpose = 'deposit'
    ): int {
        $allowedPurposes = ['deposit', 'balance', 'other'];
        if (!in_array($purpose, $allowedPurposes, true)) {
            $purpose = 'other';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO payments
             (booking_id, payment_method, gateway, channel, reference, payment_purpose, phone, customer_email, amount, status)
             VALUES (:booking_id, "online", "paystack", "checkout", :reference, :purpose, :phone, :email, :amount, "pending")'
        );
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':reference' => $reference,
            ':purpose' => $purpose,
            ':phone' => $phone,
            ':email' => $email,
            ':amount' => $amount,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM payments WHERE reference = :reference LIMIT 1'
        );
        $stmt->execute([':reference' => $reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markPaystackCompleted(
        string $reference,
        ?string $channel,
        array $gatewayData,
        string $rawPayload
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE payments
             SET status = "completed",
                 channel = :channel,
                 gateway_transaction_id = :transaction_id,
                 gateway_response = :gateway_response,
                 result_code = "0",
                 result_desc = "Payment confirmed by Paystack",
                 raw_callback = :raw
             WHERE reference = :reference
               AND status = "pending"'
        );

        $stmt->execute([
            ':channel' => $channel ?: 'unknown',
            ':transaction_id' => (string)($gatewayData['id'] ?? ''),
            ':gateway_response' => (string)($gatewayData['gateway_response'] ?? 'Successful'),
            ':raw' => $rawPayload,
            ':reference' => $reference,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function markPaystackFailed(
        string $reference,
        string $status,
        string $message,
        string $rawPayload
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE payments
             SET status = "failed",
                 result_code = :code,
                 result_desc = :message,
                 gateway_response = :message,
                 raw_callback = :raw
             WHERE reference = :reference
               AND status = "pending"'
        );

        return $stmt->execute([
            ':code' => $status ?: 'failed',
            ':message' => $message,
            ':raw' => $rawPayload,
            ':reference' => $reference,
        ]);
    }

    public function pendingManualVerificationCount(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM payments
             WHERE payment_method = 'manual'
               AND status = 'pending'
               AND mpesa_receipt_number IS NOT NULL
               AND mpesa_receipt_number <> ''"
        )->fetchColumn();
    }

    /**
     * Completed-payment totals and counts by gateway for a date range,
     * so the dashboard/reports can show the M-Pesa vs Paystack mix
     * instead of only a combined total.
     *
     * @return array<string, array{label:string, count:int, total:float}>
     */
    public function completedTotalsByGateway(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                CASE
                    WHEN gateway = 'paystack' THEN 'paystack'
                    WHEN payment_method = 'manual' THEN 'manual'
                    ELSE 'stk'
                END AS bucket,
                COUNT(*) AS cnt,
                COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE status = 'completed'
               AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY bucket"
        );
        $stmt->execute([':from' => $from, ':to' => $to]);

        $labels = ['paystack' => 'Paystack', 'stk' => 'M-Pesa STK', 'manual' => 'Manual M-Pesa'];
        $result = [];
        foreach ($labels as $key => $label) {
            $result[$key] = ['label' => $label, 'count' => 0, 'total' => 0.0];
        }
        foreach ($stmt->fetchAll() as $row) {
            $bucket = (string)$row['bucket'];
            $result[$bucket]['count'] = (int)$row['cnt'];
            $result[$bucket]['total'] = (float)$row['total'];
        }
        return $result;
    }

    /**
     * Payments list for the admin Payments screen — every payment attempt
     * (not just the one representing a booking), joined with booking/customer
     * info, with filters for gateway, status, and free-text search.
     *
     * Gateway filter values: 'paystack', 'stk', 'manual', '' (all).
     * A payment counts as 'stk' when it has no gateway and isn't manual —
     * this mirrors the bucket logic in completedTotalsByGateway().
     */
    public function searchAdmin(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'completed', 'failed', 'cancelled'], true)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['gateway'])) {
            if ($filters['gateway'] === 'paystack') {
                $where[] = "p.gateway = 'paystack'";
            } elseif ($filters['gateway'] === 'manual') {
                $where[] = "p.payment_method = 'manual'";
            } elseif ($filters['gateway'] === 'stk') {
                $where[] = "(p.gateway IS NULL OR p.gateway = '') AND p.payment_method != 'manual'";
            }
        }

        if (!empty($filters['needs_verification'])) {
            $where[] = "p.payment_method = 'manual' AND p.status = 'pending' AND p.mpesa_receipt_number IS NOT NULL AND p.mpesa_receipt_number <> ''";
        }

        if (!empty($filters['from'])) {
            $where[] = 'p.created_at >= :from_date';
            $params[':from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $where[] = 'p.created_at <= :to_date';
            $params[':to_date'] = $filters['to'] . ' 23:59:59';
        }

        if (!empty($filters['q'])) {
            $where[] = '(b.booking_ref LIKE :q OR b.first_name LIKE :q OR b.last_name LIKE :q '
                . 'OR p.phone LIKE :q OR p.reference LIKE :q OR p.mpesa_receipt_number LIKE :q '
                . 'OR p.gateway_transaction_id LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }

        $sql = "SELECT p.*, b.booking_ref, b.car_id, CONCAT(b.first_name, ' ', b.last_name) AS full_name,
                       b.status AS booking_status, c.name AS car_name
                FROM payments p
                JOIN bookings b ON b.id = p.booking_id
                JOIN cars c ON c.id = b.car_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.created_at DESC LIMIT 300';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function forBookings(array $bookingIds): array
    {
        if (empty($bookingIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM payments WHERE booking_id IN ($placeholders)
             ORDER BY
               CASE status WHEN 'completed' THEN 0 WHEN 'pending' THEN 1 WHEN 'failed' THEN 2 ELSE 3 END,
               created_at DESC, id DESC"
        );
        $stmt->execute(array_values($bookingIds));

        $byBooking = [];
        foreach ($stmt->fetchAll() as $row) {
            $byBooking[$row['booking_id']] ??= $row;
        }
        return $byBooking;
    }

    public function markCompleted(
        string $checkoutRequestId,
        string $receiptNumber,
        string $resultDesc,
        string $rawPayload
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE payments SET status = "completed", mpesa_receipt_number = :receipt,
                result_code = "0", result_desc = :desc, raw_callback = :raw
             WHERE checkout_request_id = :cid AND status = "pending"'
        );
        return $stmt->execute([
            ':receipt' => $receiptNumber,
            ':desc'    => $resultDesc,
            ':raw'     => $rawPayload,
            ':cid'     => $checkoutRequestId,
        ]);
    }

    public function markFailed(string $checkoutRequestId, string $resultCode, string $resultDesc, string $rawPayload): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE payments SET status = "failed", result_code = :code, result_desc = :desc, raw_callback = :raw
             WHERE checkout_request_id = :cid AND status = "pending"'
        );
        return $stmt->execute([
            ':code' => $resultCode,
            ':desc' => $resultDesc,
            ':raw'  => $rawPayload,
            ':cid'  => $checkoutRequestId,
        ]);
    }
}
