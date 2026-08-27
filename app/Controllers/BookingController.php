<?php
declare(strict_types=1);

final class BookingController
{
    public function create(): void
    {
        $carService = CarService::make();
        $preselectedCarId = isset($_GET['car']) ? (int) $_GET['car'] : null;

        view('booking', [
            'seo'   => seo_for('booking'),
            'cars'  => $carService->search(),
            'carId' => $preselectedCarId,
        ]);
    }

    public function availability(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $pickupRaw = trim((string) ($_GET['pickup'] ?? ''));
        $returnRaw = trim((string) ($_GET['return'] ?? ''));

        try {
            $pickup = new DateTime($pickupRaw);
            $return = new DateTime($returnRaw);
        } catch (Throwable) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Please choose valid pickup and return dates.']);
            return;
        }

        if ($return <= $pickup) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Return time must be after pickup time.']);
            return;
        }

        if ($pickup < new DateTime('today')) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Pickup date cannot be in the past.']);
            return;
        }

        // Keep this public endpoint lightweight and bounded. A customer does
        // not need to check a rental period longer than a year on the booking UI.
        if (($return->getTimestamp() - $pickup->getTimestamp()) > (366 * 86400)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Please choose a rental period of one year or less.']);
            return;
        }

        $availability = BookingService::make()->availabilityForPeriod(
            $pickup->format('Y-m-d H:i:s'),
            $return->format('Y-m-d H:i:s')
        );

        echo json_encode([
            'ok' => true,
            'pickup' => $pickup->format('Y-m-d H:i:s'),
            'return' => $return->format('Y-m-d H:i:s'),
            'cars' => $availability,
        ], JSON_UNESCAPED_SLASHES);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            flash('error', 'Your session expired, please try again.');
            redirect('book');
        }

        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['old_input'] = $_POST;
            $_SESSION['errors'] = $errors;
            redirect('book');
        }

        $carService = CarService::make();
        $bookingService = BookingService::make();
        $publicToken = bin2hex(random_bytes(32));
        $car = $carService->find((int) $_POST['car_id']);
        if (!$car) {
            flash('error', 'Selected car could not be found.');
            redirect('book');
        }

        $pickup = new DateTime($_POST['pickup_date']);
        $return = new DateTime($_POST['return_date']);
        $seconds = $return->getTimestamp() - $pickup->getTimestamp();
        $days = max(1, (int) ceil($seconds / 86400));

        if (!$bookingService->isCarAvailable((int) $car['id'], $pickup->format('Y-m-d H:i:s'), $return->format('Y-m-d H:i:s'))) {
            $_SESSION['old_input'] = $_POST;
            $_SESSION['errors'] = ['car_id' => 'This vehicle is not available for the selected dates. Please choose another available vehicle or change your dates.'];
            flash('error', 'That vehicle has just become unavailable for those dates. We kept your details — choose another available vehicle or change your dates.');
            redirect('book');
        }

        $totalPrice = $days * (float) $car['price_per_day'];

        $driverOption = $_POST['driver_option'] === 'with_driver' ? 'with_driver' : 'self_drive';
        if ($driverOption === 'with_driver') {
            $totalPrice += $days * $carService->effectiveChauffeurFee($car);
        }

        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phoneRaw = trim($_POST['phone']);
        $normalizedPhone = (new MpesaService())->normalizePhone($phoneRaw);

        // Booking is guest-first. Existing customer accounts are linked when
        // the phone number matches, but a new customer is not forced to create
        // an account or receive a temporary password just to book a car.
        $customer = CustomerService::make()->findByPhone($normalizedPhone);
        $customerId = $customer ? (int) $customer['id'] : null;

        try {
            $bookingId = $bookingService->create([
            'booking_ref'            => booking_reference(),
            'public_token_hash'     => hash('sha256', $publicToken),
            'public_token_created_at'=> date('Y-m-d H:i:s'),
            'user_id'                => Auth::id(),
            'customer_id'            => $customerId,
            'car_id'                 => $car['id'],
            'first_name'             => $firstName,
            'last_name'              => $lastName,
            'id_number'              => trim($_POST['id_number']),
            'driving_license_number' => trim($_POST['driving_license_number'] ?? ''),
            'email'                  => $email,
            'phone'                  => $normalizedPhone,
            'whatsapp_opt_in'        => !empty($_POST['whatsapp_opt_in']) ? 1 : 0,
            'pickup_location'        => trim($_POST['pickup_location']),
            'dropoff_location'       => trim($_POST['dropoff_location']),
            'pickup_date'            => $pickup->format('Y-m-d H:i:s'),
            'return_date'            => $return->format('Y-m-d H:i:s'),
            'driver_option'          => $driverOption,
            'total_days'             => $days,
            'total_price'            => $totalPrice,
            'notes'                  => trim($_POST['notes'] ?? ''),
            'terms_accepted'         => 1,
            'terms_accepted_at'      => date('Y-m-d H:i:s'),
            'damage_accepted'        => 1,
            'damage_accepted_at'     => date('Y-m-d H:i:s'),
        ]);
        } catch (Throwable $e) {
            error_log('[BOOKING CREATE ERROR] ' . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            $message = $e->getMessage() ?: 'We could not create the booking. Please try again.';
            if (stripos($message, 'booked for part of the selected period') !== false || stripos($message, 'not currently available for booking') !== false) {
                $_SESSION['errors'] = ['car_id' => 'This vehicle is not available for the selected dates. Please choose another available vehicle or change your dates.'];
                $message = 'That vehicle has just become unavailable for those dates. We kept your details — choose another available vehicle or change your dates.';
            }
            flash('error', $message);
            redirect('book');
        }

        $booking = $bookingService->find($bookingId);
        if ($booking) {
            AuditService::make()->log('booking.created', 'New booking request created.', $bookingId, 'booking', $bookingId, ['booking_ref' => $booking['booking_ref']]);
        }
        $notifications = NotificationService::make();
        $notifications->notifyBookingCreated($booking);
        $notifications->notifyAdminNewBooking($booking);
        $_SESSION['last_booking_id'] = $bookingId;
        $_SESSION['last_booking_token'] = $publicToken;
        flash('success', "Booking received! Your reference is {$booking['booking_ref']}. Our team will confirm via phone or email shortly.");
        redirect('book/confirmation?id=' . $bookingId);
    }

    public function publicAccess(string $bookingRef, string $token): void
    {
        $booking=BookingService::make()->findByPublicToken($bookingRef,$token);
        if(!$booking){
            http_response_code(404);
            view('404',['seo'=>seo_for('home')]);
            return;
        }
        $payment=PaymentService::make()->latestForBooking((int)$booking['id']);
        $paid=PaymentService::make()->completedTotalForBooking((int)$booking['id']);
        $balance=max(0,round((float)$booking['total_price']-$paid,2));
        $depositPct=max(1,min(100,(float)setting('paystack','deposit_percentage','30')));
        $depositTarget=round((float)$booking['total_price']*($depositPct/100),2);
        $nextPayment=max(0,round(min($balance,max(0,$depositTarget-$paid)),2));
        $nextPaymentPurpose=$nextPayment>0 ? 'deposit' : ($balance>0 ? 'balance' : 'paid');
        if($nextPaymentPurpose==='balance') $nextPayment=$balance;
        view('booking-status',[
            'seo'=>array_merge(seo_for('booking'),['robots'=>'noindex,nofollow','title'=>'Booking '.$booking['booking_ref'].' | Big Kahuna']),
            'booking'=>$booking,'payment'=>$payment,'paid'=>$paid,'balance'=>$balance,
            'publicToken'=>$token,
            'paystackEnabled'=>PAYSTACK_ENABLED && PAYSTACK_SECRET_KEY!=='' && setting('paystack','enabled','1')==='1',
            'depositPct'=>$depositPct,'depositTarget'=>$depositTarget,'nextPayment'=>$nextPayment,'nextPaymentPurpose'=>$nextPaymentPurpose,
        ]);
    }

    public function confirmation(): void
    {
        $bookingId = (int) ($_GET['id'] ?? 0);
        $booking = $bookingId ? BookingService::make()->find($bookingId) : null;
        $publicToken = trim((string)($_GET['token'] ?? ''));
        $owned = $booking && (
            (CustomerAuth::check() && (int)$booking['customer_id'] === (int)CustomerAuth::id())
            || (isset($_SESSION['last_booking_id']) && (int)$_SESSION['last_booking_id'] === $bookingId)
            || BookingService::make()->verifyPublicTokenForBooking($bookingId, $publicToken)
        );
        if ($booking && !$owned) {
            http_response_code(403);
            view('404', ['seo'=>seo_for('home')]);
            return;
        }

        view('booking-confirmation', [
            'seo'          => array_merge(seo_for('booking'), ['robots' => 'noindex, nofollow']),
            'booking'      => $booking,
            'paystackEnabled' => PAYSTACK_ENABLED
                && PAYSTACK_SECRET_KEY !== ''
                && setting('paystack', 'enabled', '1') === '1',
            'paystackLabel' => setting('paystack', 'display_label', 'Pay securely'),
            'paystackDescription' => setting(
                'paystack',
                'checkout_description',
                'Pay your booking deposit securely using the payment methods available through Paystack.'
            ),
            'depositPct'   => max(1, min(100, (float) setting('paystack', 'deposit_percentage', '30'))),
            'payment' => $booking ? PaymentService::make()->latestForBooking($bookingId) : null,
            'publicToken' => $publicToken !== '' ? $publicToken : (($bookingId === (int)($_SESSION['last_booking_id'] ?? 0)) ? (string)($_SESSION['last_booking_token'] ?? '') : ''),
        ]);
    }

    /**
     * Printable/downloadable payment receipt. Only reachable once a payment
     * has actually completed — an unpaid or pending booking has nothing to
     * show a receipt for.
     */
    public function receipt(int $bookingId): void
    {
        $booking = BookingService::make()->find($bookingId);
        $publicToken = trim((string)($_GET['token'] ?? ''));
        $owned = $booking && (
            (CustomerAuth::check() && (int)$booking['customer_id'] === (int)CustomerAuth::id())
            || (isset($_SESSION['last_booking_id']) && (int)$_SESSION['last_booking_id'] === $bookingId)
            || BookingService::make()->verifyPublicTokenForBooking($bookingId, $publicToken)
        );

        if (!$booking || !$owned) {
            http_response_code(403);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        $payment = PaymentService::make()->latestCompletedForBooking($bookingId);
        if (!$payment) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('receipt', [
            'booking' => $booking,
            'payment' => $payment,
            'isAdmin' => false,
            'backUrl' => base_url('book/confirmation?id=' . $bookingId . ($publicToken !== '' ? '&token=' . rawurlencode($publicToken) : '')),
        ]);
    }

    public function receiptPayment(int $bookingId, int $paymentId): void
    {
        $booking = BookingService::make()->find($bookingId);
        $publicToken = trim((string)($_GET['token'] ?? ''));
        $owned = $booking && (
            (CustomerAuth::check() && (int)$booking['customer_id'] === (int)CustomerAuth::id())
            || (isset($_SESSION['last_booking_id']) && (int)$_SESSION['last_booking_id'] === $bookingId)
            || BookingService::make()->verifyPublicTokenForBooking($bookingId, $publicToken)
        );

        if (!$booking || !$owned) {
            http_response_code(403);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        $payment = PaymentService::make()->findById($paymentId);
        if (!$payment || (int)$payment['booking_id'] !== $bookingId || ($payment['status'] ?? '') !== 'completed') {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('receipt', [
            'booking' => $booking,
            'payment' => $payment,
            'isAdmin' => false,
            'backUrl' => base_url('book/confirmation?id=' . $bookingId . ($publicToken !== '' ? '&token=' . rawurlencode($publicToken) : '')),
        ]);
    }

    private function validate(array $input): array
    {
        $errors = [];

        if (empty($input['car_id'])) $errors['car_id'] = 'Please select a car.';
        if (empty($input['first_name'])) $errors['first_name'] = 'First name is required.';
        if (empty($input['last_name'])) $errors['last_name'] = 'Last name is required.';
        if (empty($input['id_number'])) $errors['id_number'] = 'National ID / Passport number is required.';
        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }
        if (empty($input['phone'])) $errors['phone'] = 'Phone number is required.';
        if (empty($input['pickup_location'])) $errors['pickup_location'] = 'Pickup location is required.';
        if (empty($input['dropoff_location'])) $errors['dropoff_location'] = 'Drop-off location is required.';
        if (empty($input['terms_agree'])) $errors['terms_agree'] = 'Please accept the rental terms to continue.';
        if (empty($input['damage_agree'])) $errors['damage_agree'] = 'Please confirm you accept responsibility for damage as described.';

        $driverOption = $input['driver_option'] ?? 'self_drive';
        if (!in_array($driverOption, ['self_drive', 'with_driver'], true)) {
            $errors['driver_option'] = 'Please choose a driving option.';
        }
        if ($driverOption === 'self_drive' && empty($input['driving_license_number'])) {
            $errors['driving_license_number'] = 'A valid driving licence number is required for self-drive.';
        }

        if (empty($input['pickup_date']) || empty($input['return_date'])) {
            $errors['dates'] = 'Pickup and return dates are required.';
        } else {
            try {
                $pickup = new DateTime($input['pickup_date']);
                $return = new DateTime($input['return_date']);
                if ($return <= $pickup) {
                    $errors['dates'] = 'Return date must be after the pickup date.';
                }
                if ($pickup < new DateTime('today')) {
                    $errors['dates'] = 'Pickup date cannot be in the past.';
                }
            } catch (Exception) {
                $errors['dates'] = 'Please provide valid dates.';
            }
        }

        return $errors;
    }
}
