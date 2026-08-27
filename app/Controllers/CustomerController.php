<?php
declare(strict_types=1);

final class CustomerController
{
    public function showLogin(): void
    {
        if (CustomerAuth::check()) {
            redirect('account/dashboard');
        }

        view('account/login', [
            'seo' => [
                'title'       => 'My Account Login | ' . setting('general', 'site_name'),
                'description' => 'Log in to view your Big Kahuna Car Hire booking history.',
                'keywords'    => '',
                'og_image'    => '',
                'robots'      => 'noindex, nofollow',
            ],
        ]);
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Session expired, please try again.');
            redirect('account/login');
        }

        $phone = trim($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (CustomerAuth::attempt($phone, $password)) {
            redirect('account/dashboard');
        }

        flash('error', 'Incorrect phone number or password.');
        redirect('account/login');
    }

    public function logout(): void
    {
        CustomerAuth::logout();
        redirect('account/login');
    }

    public function dashboard(): void
    {
        CustomerAuth::requireLogin();

        $customer = CustomerService::make()->find((int) CustomerAuth::id());
        $bookings = BookingService::make()->forCustomer((int) CustomerAuth::id());

        view('account/dashboard', [
            'seo' => [
                'title'       => 'My Bookings | ' . setting('general', 'site_name'),
                'description' => '',
                'keywords'    => '',
                'og_image'    => '',
                'robots'      => 'noindex, nofollow',
            ],
            'customer' => $customer,
            'bookings' => $bookings,
        ]);
    }

    public function booking(int $id): void
    {
        CustomerAuth::requireLogin();
        $customerId=(int)CustomerAuth::id();
        $booking=BookingService::make()->findForCustomer($id,$customerId);
        if(!$booking){
            http_response_code(404);
            view('404',['seo'=>seo_for('home')]);
            return;
        }

        $payment=PaymentService::make()->latestForBooking($id);
        $snapshot=BookingService::make()->rentalSnapshot($id);
        $whatsapp=preg_replace('/\D+/','',setting('general','whatsapp_number',''));
        if($whatsapp==='') $whatsapp='254700000000';

        view('account/booking',[
            'seo'=>[
                'title'=>'Booking '.$booking['booking_ref'].' | '.setting('general','site_name'),
                'description'=>'View your rental booking, payment status and pickup information.',
                'keywords'=>'',
                'og_image'=>'',
                'robots'=>'noindex, nofollow',
            ],
            'booking'=>$booking,
            'payment'=>$payment,
            'paystackEnabled'=>PAYSTACK_ENABLED && PAYSTACK_SECRET_KEY !== '' && setting('paystack', 'enabled', '1') === '1',
            'depositPct'=>max(1, min(100, (float)setting('paystack', 'deposit_percentage', '30'))),
            'snapshot'=>$snapshot,
            'whatsapp'=>$whatsapp,
            'sitePhone'=>setting('general','phone_primary'),
            'pickupInstructions'=>setting('rental','pickup_instructions','Please carry your original ID/passport and valid driving licence. Our team will confirm the pickup point before your rental.'),
            'returnInstructions'=>setting('rental','return_instructions','Return the vehicle at the agreed time and location with all keys and accessories.'),
        ]);
    }

    public function agreement(int $id): void
    {
        CustomerAuth::requireLogin();
        $booking=BookingService::make()->findForCustomer($id,(int)CustomerAuth::id());
        if(!$booking){ http_response_code(404); exit('Booking not found.'); }

        view('account/agreement',[
            'seo'=>[
                'title'=>'Rental Agreement '.$booking['booking_ref'].' | '.setting('general','site_name'),
                'description'=>'Rental agreement for booking '.$booking['booking_ref'],
                'keywords'=>'','og_image'=>'','robots'=>'noindex, nofollow'
            ],
            'booking'=>$booking,
            'snapshot'=>BookingService::make()->rentalSnapshot($id),
            'termsText'=>setting('legal','terms_and_conditions',''),
        ]);
    }

    public function showChangePassword(): void
    {
        CustomerAuth::requireLogin();

        view('account/change-password', [
            'seo' => [
                'title'       => 'Change Password | ' . setting('general', 'site_name'),
                'description' => '',
                'keywords'    => '',
                'og_image'    => '',
                'robots'      => 'noindex, nofollow',
            ],
        ]);
    }

    public function changePassword(): void
    {
        CustomerAuth::requireLogin();

        if (!verify_csrf()) {
            flash('error', 'Session expired, please try again.');
            redirect('account/change-password');
        }

        $customerId = (int) CustomerAuth::id();
        $customer = CustomerService::make()->find($customerId);

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!$customer || !password_verify($current, $customer['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            redirect('account/change-password');
        }

        if (strlen($new) < 6) {
            flash('error', 'New password must be at least 6 characters.');
            redirect('account/change-password');
        }

        if ($new !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
            redirect('account/change-password');
        }

        CustomerService::make()->updatePassword($customerId, password_hash($new, PASSWORD_BCRYPT));
        flash('success', 'Password updated successfully.');
        redirect('account/dashboard');
    }
}
