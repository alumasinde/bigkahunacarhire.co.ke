<?php
declare(strict_types=1);

final class BookingService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            // Serialize bookings for the same vehicle. This prevents two
            // concurrent requests from both passing the availability check.
            $carLock = $this->db->prepare('SELECT id, status FROM cars WHERE id = :car_id FOR UPDATE');
            $carLock->execute([':car_id' => (int)$data['car_id']]);
            $car = $carLock->fetch();
            if (!$car) {
                throw new RuntimeException('Selected vehicle was not found.');
            }

            // A car in maintenance or retired has no reliable date range in
            // the bookings table representing its unavailability, so the
            // overlap check below can't see it. Block it outright rather
            // than letting a customer book (and pay a deposit for) a car
            // that isn't coming back into service on a known date.
            if (in_array($car['status'], ['maintenance', 'retired'], true)) {
                throw new RuntimeException('This vehicle is not currently available for booking. Please choose another vehicle.');
            }

            if ($this->hasBookingConflict((int)$data['car_id'], $data['pickup_date'], $data['return_date'])) {
                throw new RuntimeException('That vehicle has just been booked for part of the selected period. Please choose another vehicle or dates.');
            }

            $stmt = $this->db->prepare(
                'INSERT INTO bookings (booking_ref, public_token_hash, public_token_created_at, user_id, customer_id, car_id, first_name, last_name, id_number,
                    driving_license_number, email, phone, pickup_location, dropoff_location, pickup_date,
                    return_date, driver_option, total_days, total_price, status, notes, terms_accepted, terms_accepted_at, damage_accepted, damage_accepted_at, whatsapp_opt_in)
                 VALUES (:booking_ref, :public_token_hash, :public_token_created_at, :user_id, :customer_id, :car_id, :first_name, :last_name, :id_number,
                    :driving_license_number, :email, :phone, :pickup_location, :dropoff_location, :pickup_date,
                    :return_date, :driver_option, :total_days, :total_price, :status, :notes, :terms_accepted, :terms_accepted_at, :damage_accepted, :damage_accepted_at, :whatsapp_opt_in)'
            );
            $stmt->execute([
                ':booking_ref'             => $data['booking_ref'],
                ':public_token_hash'      => $data['public_token_hash'] ?? null,
                ':public_token_created_at'=> $data['public_token_created_at'] ?? null,
                ':user_id'                 => $data['user_id'] ?? null,
                ':customer_id'             => $data['customer_id'] ?? null,
                ':car_id'                  => $data['car_id'],
                ':first_name'              => $data['first_name'],
                ':last_name'               => $data['last_name'],
                ':id_number'               => $data['id_number'],
                ':driving_license_number'  => $data['driving_license_number'],
                ':email'                   => $data['email'],
                ':phone'                   => $data['phone'],
                ':pickup_location'         => $data['pickup_location'],
                ':dropoff_location'        => $data['dropoff_location'],
                ':pickup_date'             => $data['pickup_date'],
                ':return_date'             => $data['return_date'],
                ':driver_option'           => $data['driver_option'],
                ':total_days'              => $data['total_days'],
                ':total_price'             => $data['total_price'],
                ':status'                  => 'pending',
                ':notes'                   => $data['notes'] ?? null,
                ':terms_accepted'          => !empty($data['terms_accepted']) ? 1 : 0,
                ':terms_accepted_at'       => $data['terms_accepted_at'] ?? null,
                ':damage_accepted'         => !empty($data['damage_accepted']) ? 1 : 0,
                ':damage_accepted_at'      => $data['damage_accepted_at'] ?? null,
                ':whatsapp_opt_in'        => !empty($data['whatsapp_opt_in']) ? 1 : 0,
            ]);

            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Admin booking search/filtering. All filter values are parameterized.
     */
    public function searchAdmin(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['pending','confirmed','ongoing','completed','cancelled'], true)) {
            $where[] = 'b.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['car_id']) && ctype_digit((string)$filters['car_id'])) {
            $where[] = 'b.car_id = :car_id';
            $params[':car_id'] = (int)$filters['car_id'];
        }

        if (!empty($filters['from'])) {
            $where[] = 'b.return_date > :from_date';
            $params[':from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $where[] = 'b.pickup_date < :to_date';
            $params[':to_date'] = $filters['to'] . ' 23:59:59';
        }

        if (!empty($filters['q'])) {
            $where[] = '(b.booking_ref LIKE :q OR b.first_name LIKE :q OR b.last_name LIKE :q OR b.phone LIKE :q OR b.email LIKE :q OR c.name LIKE :q OR c.plate_number LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }

        $sql = "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name,
                       c.name AS car_name, c.plate_number, c.image_path
                FROM bookings b
                JOIN cars c ON c.id = b.car_id";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY b.pickup_date ASC, b.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Returns bookings intersecting a calendar month.
     */
    public function forCalendar(string $month): array
    {
        $start = new DateTime($month . '-01');
        $end = (clone $start)->modify('+1 month');

        $stmt = $this->db->prepare(
            "SELECT b.id, b.booking_ref, b.first_name, b.last_name, b.car_id,
                    b.pickup_date, b.return_date, b.status, b.total_price,
                    c.name AS car_name, c.plate_number
             FROM bookings b
             JOIN cars c ON c.id = b.car_id
             WHERE b.status <> 'cancelled'
               AND b.pickup_date < :month_end
               AND b.return_date > :month_start
             ORDER BY b.pickup_date ASC"
        );
        $stmt->execute([
            ':month_start' => $start->format('Y-m-d H:i:s'),
            ':month_end' => $end->format('Y-m-d H:i:s'),
        ]);
        return $stmt->fetchAll();
    }

    public function operationalStats(?string $from = null, ?string $to = null): array
    {
        $where = [];
        $params = [];
        if ($from) {
            $where[] = 'pickup_date < :to_date';
            $params[':to_date'] = ($to ?: date('Y-m-d')) . ' 23:59:59';
        }
        if ($to) {
            $where[] = 'return_date > :from_date';
            $params[':from_date'] = ($from ?: '2000-01-01') . ' 00:00:00';
        }

        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS bookings,
                COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_price ELSE 0 END),0) AS gross_value,
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END),0) AS confirmed_value,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END),0) AS completed_value
             FROM bookings{$condition}"
        );
        $stmt->execute($params);
        return $stmt->fetch() ?: ['bookings'=>0,'gross_value'=>0,'confirmed_value'=>0,'completed_value'=>0];
    }

    public function upcoming(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name,
                    c.name AS car_name, c.plate_number
             FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.status IN ('confirmed','ongoing')
               AND b.return_date >= NOW()
             ORDER BY b.pickup_date ASC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function inspections(int $bookingId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ri.*, CONCAT(u.first_name, ' ', u.last_name) AS inspector_name
             FROM rental_inspections ri
             LEFT JOIN users u ON u.id = ri.inspected_by
             WHERE ri.booking_id = :booking_id
             ORDER BY ri.inspection_type, ri.inspected_at ASC"
        );
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function latestInspection(int $bookingId, string $type): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ri.*, CONCAT(u.first_name, ' ', u.last_name) AS inspector_name
             FROM rental_inspections ri
             LEFT JOIN users u ON u.id = ri.inspected_by
             WHERE ri.booking_id = :booking_id AND ri.inspection_type = :type
             ORDER BY ri.inspected_at DESC LIMIT 1"
        );
        $stmt->execute([':booking_id'=>$bookingId, ':type'=>$type]);
        $row=$stmt->fetch();
        return $row ?: null;
    }

    public function charges(int $bookingId): array
    {
        $stmt=$this->db->prepare(
            "SELECT rc.*, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name
             FROM rental_charges rc LEFT JOIN users u ON u.id=rc.created_by
             WHERE rc.booking_id=:booking_id ORDER BY rc.created_at DESC"
        );
        $stmt->execute([':booking_id'=>$bookingId]);
        return $stmt->fetchAll();
    }

    public function chargeTotal(int $bookingId, ?string $status=null): float
    {
        if ($status && in_array($status,['pending','paid','waived'],true)) {
            $stmt=$this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM rental_charges WHERE booking_id=:id AND status=:status");
            $stmt->execute([':id'=>$bookingId,':status'=>$status]);
        } else {
            $stmt=$this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM rental_charges WHERE booking_id=:id AND status <> 'waived'");
            $stmt->execute([':id'=>$bookingId]);
        }
        return (float)$stmt->fetchColumn();
    }

    public function createInspection(int $bookingId, int $userId, string $type, array $data): int
    {
        if (!in_array($type,['checkout','return'],true)) throw new InvalidArgumentException('Invalid inspection type.');
        $booking=$this->find($bookingId);
        if (!$booking) throw new RuntimeException('Booking not found.');

        $stmt=$this->db->prepare(
            "INSERT INTO rental_inspections
             (booking_id,car_id,inspection_type,inspected_by,odometer_km,fuel_level,condition_notes,damage_notes,photos_json,customer_acknowledged,customer_name)
             VALUES (:booking_id,:car_id,:type,:user_id,:odometer,:fuel,:condition,:damage,:photos,:ack,:customer)"
        );
        $stmt->execute([
            ':booking_id'=>$bookingId,
            ':car_id'=>(int)$booking['car_id'],
            ':type'=>$type,
            ':user_id'=>$userId,
            ':odometer'=>($data['odometer_km'] ?? '') !== '' ? (float)$data['odometer_km'] : null,
            ':fuel'=>($data['fuel_level'] ?? '') !== '' ? (float)$data['fuel_level'] : null,
            ':condition'=>trim($data['condition_notes'] ?? ''),
            ':damage'=>trim($data['damage_notes'] ?? ''),
            ':photos'=>!empty($data['photos']) ? json_encode($data['photos'], JSON_UNESCAPED_SLASHES) : null,
            ':ack'=>!empty($data['customer_acknowledged']) ? 1 : 0,
            ':customer'=>trim($data['customer_name'] ?? ($booking['full_name'] ?? '')),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function checkout(int $bookingId, int $userId, array $inspection): void
    {
        $db=$this->db;
        $db->beginTransaction();
        try {
            $stmt=$db->prepare("SELECT * FROM bookings WHERE id=:id FOR UPDATE");
            $stmt->execute([':id'=>$bookingId]);
            $booking=$stmt->fetch();
            if (!$booking) throw new RuntimeException('Booking not found.');
            if ($booking['status'] !== 'confirmed') throw new RuntimeException('Only confirmed bookings can be checked out.');

            $paidStmt = $db->prepare(
                "SELECT COALESCE(SUM(amount), 0)
                 FROM payments
                 WHERE booking_id = :booking_id
                   AND status = 'completed'"
            );
            $paidStmt->execute([':booking_id' => $bookingId]);
            $paid = (float)$paidStmt->fetchColumn();
            $balance = round((float)$booking['total_price'] - $paid, 2);

            if ($balance > 0.009) {
                throw new RuntimeException(
                    'Handover is locked. The remaining rental balance of ' .
                    number_format($balance, 2) .
                    ' must be paid and verified before the keys can be released.'
                );
            }

            if (!$this->isCarAvailableForCheckout((int)$booking['car_id'],$bookingId)) throw new RuntimeException('This vehicle is not currently available for checkout.');

            $this->createInspection($bookingId,$userId,'checkout',$inspection);

            if (($inspection['odometer_km'] ?? '') !== '') {
                $stmt=$db->prepare("INSERT INTO vehicle_odometer_logs (car_id,booking_id,reading_km,reading_type,recorded_by,notes) VALUES (:car_id,:booking_id,:reading,'checkout',:user_id,:notes)");
                $stmt->execute([
                    ':car_id'=>(int)$booking['car_id'], ':booking_id'=>$bookingId,
                    ':reading'=>(float)$inspection['odometer_km'], ':user_id'=>$userId,
                    ':notes'=>'Checkout inspection'
                ]);
            }

            $stmt=$db->prepare("UPDATE bookings SET status='ongoing', checkout_at=NOW(), actual_pickup_at=NOW() WHERE id=:id");
            $stmt->execute([':id'=>$bookingId]);
            $stmt=$db->prepare("UPDATE cars SET status='booked' WHERE id=:id");
            $stmt->execute([':id'=>(int)$booking['car_id']]);
            $db->commit();
        } catch(Throwable $e) {
            if($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function isCarAvailableForCheckout(int $carId,int $bookingId): bool
    {
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM bookings WHERE car_id=:car_id AND id<>:booking_id AND status='ongoing'");
        $stmt->execute([':car_id'=>$carId,':booking_id'=>$bookingId]);
        return (int)$stmt->fetchColumn()===0;
    }

    public function returnVehicle(int $bookingId, int $userId, array $inspection, array $autoCharges=[]): void
    {
        $db=$this->db;
        $db->beginTransaction();
        try {
            $stmt=$db->prepare("SELECT * FROM bookings WHERE id=:id FOR UPDATE");
            $stmt->execute([':id'=>$bookingId]);
            $booking=$stmt->fetch();
            if(!$booking) throw new RuntimeException('Booking not found.');
            if($booking['status']!=='ongoing') throw new RuntimeException('Only ongoing rentals can be returned.');

            $checkout=$this->latestInspection($bookingId,'checkout');
            if($checkout && ($inspection['odometer_km'] ?? '') !== '' && $checkout['odometer_km'] !== null && (float)$inspection['odometer_km'] < (float)$checkout['odometer_km']) {
                throw new RuntimeException('Return odometer cannot be lower than checkout odometer.');
            }

            $this->createInspection($bookingId,$userId,'return',$inspection);

            if (($inspection['odometer_km'] ?? '') !== '') {
                $stmt=$db->prepare("INSERT INTO vehicle_odometer_logs (car_id,booking_id,reading_km,reading_type,recorded_by,notes) VALUES (:car_id,:booking_id,:reading,'return',:user_id,:notes)");
                $stmt->execute([
                    ':car_id'=>(int)$booking['car_id'], ':booking_id'=>$bookingId,
                    ':reading'=>(float)$inspection['odometer_km'], ':user_id'=>$userId,
                    ':notes'=>'Return inspection'
                ]);
            }

            foreach($autoCharges as $charge){
                if(($charge['amount'] ?? 0)>0){
                    $this->createChargeInternal($bookingId,$userId,$charge['type'] ?? 'other',$charge['description'] ?? 'Additional rental charge',(float)$charge['amount']);
                }
            }

            $newCarStatus=!empty($inspection['needs_maintenance'])?'maintenance':'available';
            $stmt=$db->prepare("UPDATE bookings SET status='completed', returned_at=NOW(), actual_return_at=NOW() WHERE id=:id");
            $stmt->execute([':id'=>$bookingId]);
            $stmt=$db->prepare("UPDATE cars SET status=:status WHERE id=:id");
            $stmt->execute([':status'=>$newCarStatus,':id'=>(int)$booking['car_id']]);
            $db->commit();
        } catch(Throwable $e) {
            if($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function createChargeInternal(int $bookingId,int $userId,string $type,string $description,float $amount): int
    {
        if(!in_array($type,['late_return','extra_mileage','fuel','damage','cleaning','other'],true)) $type='other';
        $stmt=$this->db->prepare("INSERT INTO rental_charges (booking_id,charge_type,description,amount,created_by) VALUES (:booking_id,:type,:description,:amount,:user_id)");
        $stmt->execute([':booking_id'=>$bookingId,':type'=>$type,':description'=>$description,':amount'=>max(0,$amount),':user_id'=>$userId]);
        return (int)$this->db->lastInsertId();
    }

    public function addCharge(int $bookingId,int $userId,string $type,string $description,float $amount): int
    {
        if(!in_array($type,['late_return','extra_mileage','fuel','damage','cleaning','other'],true)) throw new InvalidArgumentException('Invalid charge type.');
        if(trim($description)==='' || $amount<=0) throw new InvalidArgumentException('Charge description and amount are required.');
        return $this->createChargeInternal($bookingId,$userId,$type,$description,$amount);
    }

    public function updateChargeStatus(int $chargeId,string $status): bool
    {
        if(!in_array($status,['pending','paid','waived'],true)) return false;
        $stmt=$this->db->prepare("UPDATE rental_charges SET status=:status WHERE id=:id");
        return $stmt->execute([':status'=>$status,':id'=>$chargeId]);
    }

    public function rentalSnapshot(int $bookingId): array
    {
        $booking=$this->find($bookingId);
        if(!$booking) return [];
        $checkout=$this->latestInspection($bookingId,'checkout');
        $return=$this->latestInspection($bookingId,'return');
        return [
            'booking'=>$booking,
            'checkout'=>$checkout,
            'return'=>$return,
            'charges'=>$this->charges($bookingId),
            'pending_charges'=>$this->chargeTotal($bookingId,'pending'),
            'all_charges'=>$this->chargeTotal($bookingId),
        ];
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name, c.name AS car_name, c.image_path
             FROM bookings b JOIN cars c ON c.id = b.car_id
             WHERE b.customer_id = :customer_id ORDER BY b.created_at DESC"
        );
        $stmt->execute([':customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function findForCustomer(int $bookingId, int $customerId): ?array
    {
        $stmt=$this->db->prepare(
            "SELECT b.*, CONCAT(b.first_name,' ',b.last_name) AS full_name,
                    c.name AS car_name, c.brand, c.model, c.plate_number, c.image_path,
                    c.location AS vehicle_location
             FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.id=:booking_id AND b.customer_id=:customer_id LIMIT 1"
        );
        $stmt->execute([':booking_id'=>$bookingId,':customer_id'=>$customerId]);
        $row=$stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name, c.name AS car_name, c.image_path
             FROM bookings b JOIN cars c ON c.id = b.car_id ORDER BY b.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function findByPublicToken(string $bookingRef, string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name, c.name AS car_name
             FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.booking_ref=:ref AND b.public_token_hash=:hash LIMIT 1"
        );
        $stmt->execute([':ref'=>$bookingRef, ':hash'=>$hash]);
        $row=$stmt->fetch();
        return $row ?: null;
    }

    public function publicTokenExistsForBooking(int $id): bool
    {
        $stmt=$this->db->prepare('SELECT public_token_hash IS NOT NULL FROM bookings WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        return (bool)$stmt->fetchColumn();
    }

    public function verifyPublicTokenForBooking(int $id, string $token): bool
    {
        $token = trim($token);
        if ($token === '') return false;
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('SELECT 1 FROM bookings WHERE id=:id AND public_token_hash=:hash LIMIT 1');
        $stmt->execute([':id'=>$id, ':hash'=>$hash]);
        return (bool)$stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) AS full_name, c.name AS car_name
             FROM bookings b JOIN cars c ON c.id = b.car_id WHERE b.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowedStatuses = ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        $allowedTransitions = [
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['cancelled'],
            'ongoing'   => [],
            'completed' => [],
            'cancelled' => [],
        ];

        $stmt = $this->db->prepare('SELECT status FROM bookings WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $current = (string)($stmt->fetchColumn() ?: '');
        if ($current === '' || !in_array($status, $allowedTransitions[$current] ?? [], true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE bookings SET status = :status WHERE id = :id AND status = :current');
        $stmt->execute([':status' => $status, ':id' => $id, ':current' => $current]);
        return $stmt->rowCount() === 1;
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status');
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Total bookings ever placed, for real homepage stats. */
    public function totalCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    }

    public function isCarAvailable(int $carId, string $pickup, string $return): bool
    {
        return !$this->hasBookingConflict($carId, $pickup, $return);
    }

    /**
     * Public booking-page availability check. This deliberately returns only
     * customer-safe availability information; it never exposes another
     * customer's name, booking reference, phone number or exact reservation.
     */
    public function availabilityForPeriod(string $pickup, string $return): array
    {
        $bufferHours = max(0, (int) setting('rental', 'turnaround_buffer_hours', '3'));

        $stmt = $this->db->prepare(
            "SELECT c.id, c.status,
                    CASE
                        WHEN c.status IN ('maintenance','retired') THEN 1
                        WHEN EXISTS (
                            SELECT 1
                            FROM bookings b
                            WHERE b.car_id = c.id
                              AND b.status IN ('pending','confirmed','ongoing')
                              AND DATE_SUB(b.pickup_date, INTERVAL :buffer_before HOUR) < :return_date
                              AND DATE_ADD(b.return_date, INTERVAL :buffer_after HOUR) > :pickup_date
                        ) THEN 1
                        ELSE 0
                    END AS unavailable
             FROM cars c
             WHERE c.status <> 'retired'
             ORDER BY c.featured DESC, c.price_per_day ASC, c.id ASC"
        );
        $stmt->execute([
            ':buffer_before' => $bufferHours,
            ':buffer_after' => $bufferHours,
            ':pickup_date' => $pickup,
            ':return_date' => $return,
        ]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $unavailable = (int) $row['unavailable'] === 1;
            $reason = null;
            if (in_array($row['status'], ['maintenance', 'retired'], true)) {
                $reason = 'Temporarily unavailable';
            } elseif ($unavailable) {
                $reason = 'Booked for part of these dates';
            }

            $result[(int) $row['id']] = [
                'available' => !$unavailable,
                'reason' => $reason,
            ];
        }

        return $result;
    }

    /**
     * True if $carId has a booking (pending/confirmed/ongoing) whose window,
     * padded by the configured turnaround buffer, overlaps [$pickup, $return].
     * The buffer is applied to the *existing* booking on both sides, which
     * guarantees at least that many hours of gap between any two bookings
     * of the same car — time for cleaning/inspection between rentals.
     * Pass $excludeBookingId when checking a booking against itself (e.g.
     * when extending its own return date).
     */
    public function hasBookingConflict(int $carId, string $pickup, string $return, ?int $excludeBookingId = null): bool
    {
        $bufferHours = max(0, (int)setting('rental', 'turnaround_buffer_hours', '3'));

        $sql = "SELECT COUNT(*) FROM bookings
                 WHERE car_id = :car_id
                   AND status IN ('pending','confirmed','ongoing')
                   AND DATE_SUB(pickup_date, INTERVAL :buffer1 HOUR) < :return_date
                   AND DATE_ADD(return_date, INTERVAL :buffer2 HOUR) > :pickup_date";
        $params = [
            ':car_id' => $carId,
            ':pickup_date' => $pickup,
            ':return_date' => $return,
            ':buffer1' => $bufferHours,
            ':buffer2' => $bufferHours,
        ];

        if ($excludeBookingId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Read-only lookup used by the admin "extend booking" live-check —
     * returns the reference of the next booking for this car that would
     * conflict with a candidate pickup/return window, or null if there
     * is none. Does not lock rows or write anything; the real guard is
     * still the FOR UPDATE check inside extendBooking()/create() at
     * commit time — this is purely to show staff an early warning while
     * they are still typing a date.
     */
    public function conflictingBookingRef(int $carId, string $pickup, string $return, ?int $excludeBookingId = null): ?string
    {
        $bufferHours = max(0, (int)setting('rental', 'turnaround_buffer_hours', '3'));
        $sql = "SELECT booking_ref FROM bookings
                 WHERE car_id = :car_id
                   AND status IN ('pending','confirmed','ongoing')
                   AND DATE_SUB(pickup_date, INTERVAL :buffer1 HOUR) < :return_date
                   AND DATE_ADD(return_date, INTERVAL :buffer2 HOUR) > :pickup_date";
        $params = [
            ':car_id' => $carId,
            ':return_date' => $return,
            ':pickup_date' => $pickup,
            ':buffer1' => $bufferHours,
            ':buffer2' => $bufferHours,
        ];
        if ($excludeBookingId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeBookingId;
        }
        $sql .= ' ORDER BY pickup_date ASC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ref = $stmt->fetchColumn();
        return $ref !== false ? (string)$ref : null;
    }

    /**
     * Push a confirmed/ongoing booking's return date later — e.g. the
     * customer wants to keep the car longer. Re-checks for a conflict
     * against every OTHER booking of the same car (with the same
     * turnaround buffer used at booking time) before committing, and
     * recalculates total_days/total_price for the new duration.
     *
     * Returns the conflicting booking's reference if the extension isn't
     * possible, or null on success.
     */
    public function extendBooking(int $bookingId, string $newReturnDate, float $pricePerDay, float $chauffeurFeePerDay = 0.0): ?string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM bookings WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new RuntimeException('Booking not found.');
            }
            if (!in_array($booking['status'], ['confirmed', 'ongoing'], true)) {
                throw new RuntimeException('Only confirmed or ongoing bookings can be extended.');
            }

            $newReturn = new DateTime($newReturnDate);
            $pickup = new DateTime($booking['pickup_date']);
            $currentReturn = new DateTime($booking['return_date']);
            if ($newReturn <= $currentReturn) {
                throw new RuntimeException('The new return date must be later than the current return date.');
            }

            $conflictStmt = $this->db->prepare(
                "SELECT booking_ref FROM bookings
                 WHERE car_id = :car_id
                   AND id <> :id
                   AND status IN ('pending','confirmed','ongoing')
                   AND DATE_SUB(pickup_date, INTERVAL :buffer1 HOUR) < :return_date
                   AND DATE_ADD(return_date, INTERVAL :buffer2 HOUR) > :pickup_date
                 ORDER BY pickup_date ASC LIMIT 1"
            );
            $bufferHours = max(0, (int)setting('rental', 'turnaround_buffer_hours', '3'));
            $conflictStmt->execute([
                ':car_id' => (int)$booking['car_id'],
                ':id' => $bookingId,
                ':buffer1' => $bufferHours,
                ':buffer2' => $bufferHours,
                ':return_date' => $newReturn->format('Y-m-d H:i:s'),
                ':pickup_date' => $booking['pickup_date'],
            ]);
            $conflict = $conflictStmt->fetchColumn();
            if ($conflict) {
                $this->db->rollBack();
                return (string)$conflict;
            }

            $days = max(1, $pickup->diff($newReturn)->days);
            $totalPrice = $days * $pricePerDay;
            if ($booking['driver_option'] === 'with_driver') {
                $totalPrice += $days * $chauffeurFeePerDay;
            }

            $update = $this->db->prepare(
                'UPDATE bookings SET return_date = :return_date, total_days = :days, total_price = :price WHERE id = :id'
            );
            $update->execute([
                ':return_date' => $newReturn->format('Y-m-d H:i:s'),
                ':days' => $days,
                ':price' => $totalPrice,
                ':id' => $bookingId,
            ]);

            $this->db->commit();
            return null;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
