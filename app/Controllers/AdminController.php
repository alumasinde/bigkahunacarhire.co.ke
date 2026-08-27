<?php
declare(strict_types=1);

final class AdminController
{
    public function whatsapp(): void
    {
        Auth::requirePermission('messages.view');
        $inbox=WhatsAppInboxService::make();
        $selected=!empty($_GET['conversation'])?(int)$_GET['conversation']:null;
        $conversation=$selected?$inbox->findConversation($selected):null;
        if($conversation) $inbox->markRead($selected);
        view('admin/whatsapp',[
            'seo'=>['title'=>'WhatsApp Inbox | Admin','description'=>'','keywords'=>'','og_image'=>'','robots'=>'noindex, nofollow'],
            'conversations'=>$inbox->conversations(),
            'conversation'=>$conversation,
            'messages'=>$conversation?$inbox->messages($selected):[],
        ]);
    }

    public function whatsappReply(int $id): void
    {
        Auth::requirePermission('messages.view');
        if(!verify_csrf()){flash('error','Session expired. Please try again.');redirect('admin/whatsapp?conversation='.$id);}
        try{ WhatsAppInboxService::make()->sendReply($id,(string)($_POST['message']??'')); flash('success','WhatsApp reply sent.'); }
        catch(Throwable $e){ error_log('[WHATSAPP REPLY] '.$e->getMessage()); flash('error',$e->getMessage()); }
        redirect('admin/whatsapp?conversation='.$id);
    }

    public function whatsappStatus(int $id): void
    {
        Auth::requirePermission('messages.view');
        if(!verify_csrf()){flash('error','Session expired.');redirect('admin/whatsapp?conversation='.$id);}
        try{WhatsAppInboxService::make()->updateStatus($id,(string)($_POST['status']??'open'));flash('success','Conversation updated.');}
        catch(Throwable $e){flash('error',$e->getMessage());}
        redirect('admin/whatsapp?conversation='.$id);
    }

    public function dashboard(): void
    {
        Auth::requireLogin();

        $bookingService = BookingService::make();
        $carService = CarService::make();
        $contactService = ContactService::make();

        view('admin/dashboard', [
            'seo'            => ['title' => 'Dashboard | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'bookingCounts'  => $bookingService->countByStatus(),
            'recentBookings' => array_slice($bookingService->all(), 0, 5),
            'upcomingBookings' => $bookingService->upcoming(8),
            'totalCars'      => count($carService->all()),
            'newMessages'    => count(array_filter($contactService->all(), fn($m) => $m['status'] === 'new')),
            'operationalStats' => $bookingService->operationalStats(date('Y-m-01'), date('Y-m-t')),
            'manualPaymentsPending' => PaymentService::make()->pendingManualVerificationCount(),
            'gatewayBreakdown' => PaymentService::make()->completedTotalsByGateway(date('Y-m-01'), date('Y-m-t')),
            'fleetStats' => $carService->fleetStats(),
            'fleetAlerts' => $carService->fleetAlerts(),
            'activityCount' => AuditService::make()->countSince(date('Y-m-d 00:00:00')),
            'todayPickupCount' => count(array_filter($bookingService->upcoming(50), fn($b) => date('Y-m-d', strtotime($b['pickup_date'])) === date('Y-m-d'))),
            'unreadWhatsAppCount' => count(WhatsAppInboxService::make()->conversations(true)),
        ]);
    }

    // ---------------------------------------------------------------
    // Data Purge (super admin only)
    // ---------------------------------------------------------------
    public function purgeData(): void
    {
        Auth::requireLogin();
        if ((Auth::user()['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            die('403 — Only the super administrator can access data purge.');
        }

        $db = Database::connection();
        $counts = [];
        foreach (['bookings', 'payments', 'rental_inspections', 'rental_charges', 'contact_messages'] as $table) {
            $counts[$table] = (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        }

        view('admin/purge-data', [
            'seo' => ['title' => 'Purge Data | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'counts' => $counts,
        ]);
    }

    public function purgeDataExecute(): void
    {
        Auth::requireLogin();
        if ((Auth::user()['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            die('403 — Only the super administrator can purge data.');
        }
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/purge-data');
        }

        $confirmation = trim((string) ($_POST['confirmation'] ?? ''));
        if ($confirmation !== 'PURGE TRANSACTION DATA') {
            flash('error', 'Type PURGE TRANSACTION DATA exactly to confirm the destructive action.');
            redirect('admin/purge-data');
        }

        $selected = $_POST['datasets'] ?? [];
        if (!is_array($selected) || !$selected) {
            flash('error', 'Select at least one data set to purge.');
            redirect('admin/purge-data');
        }

        $allowed = ['bookings', 'payments', 'rental_history', 'contact_messages'];
        $selected = array_values(array_intersect($allowed, array_map('strval', $selected)));
        if (!$selected) {
            flash('error', 'No valid data set was selected.');
            redirect('admin/purge-data');
        }

        $db = Database::connection();
        $deleted = [];

        try {
            $db->beginTransaction();

            // A booking purge intentionally removes its dependent transaction records.
            // Payments/inspections/charges use ON DELETE CASCADE from bookings.
            if (in_array('bookings', $selected, true)) {
                $deleted['bookings'] = (int) $db->exec('DELETE FROM bookings');
            }

            if (in_array('payments', $selected, true) && !in_array('bookings', $selected, true)) {
                $deleted['payments'] = (int) $db->exec('DELETE FROM payments');
            }

            if (in_array('rental_history', $selected, true) && !in_array('bookings', $selected, true)) {
                $deleted['rental_inspections'] = (int) $db->exec('DELETE FROM rental_inspections');
                $deleted['rental_charges'] = (int) $db->exec('DELETE FROM rental_charges');
            }

            if (in_array('contact_messages', $selected, true)) {
                $deleted['contact_messages'] = (int) $db->exec('DELETE FROM contact_messages');
            }

            $db->commit();

            $parts = [];
            if (in_array('bookings', $selected, true)) {
                $parts[] = 'bookings and their related payment/rental records';
            } else {
                if (isset($deleted['payments'])) $parts[] = 'payments';
                if (isset($deleted['rental_inspections']) || isset($deleted['rental_charges'])) $parts[] = 'rental history';
            }
            if (isset($deleted['contact_messages'])) $parts[] = 'contact messages';

            flash('success', 'Purge completed: ' . implode(', ', $parts) . '. Protected accounts, RBAC, settings and fleet master data were not touched.');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[DATA PURGE] ' . $e->getMessage());
            flash('error', 'Purge failed. No changes were committed.');
        }

        redirect('admin/purge-data');
    }

    // ---------------------------------------------------------------
    // Cars
    // ---------------------------------------------------------------
    public function cars(): void
    {
        Auth::requirePermission('cars.view');
        view('admin/cars', [
            'seo'  => ['title' => 'Manage Fleet | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'cars' => CarService::make()->all(),
        ]);
    }

    public function carForm(?int $id = null): void
    {
        Auth::requirePermission('cars.manage');
        $car = $id ? CarService::make()->find($id) : null;

        view('admin/car-form', [
            'seo'        => ['title' => ($id ? 'Edit' : 'Add') . ' Car | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'car'        => $car,
            'categories' => CarService::make()->allCategories(),
            'gallery'    => $id ? CarService::make()->gallery($id) : [],
        ]);
    }

    public function saveCar(): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/cars');
        }

        $carService = CarService::make();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;

        $imagePath = $_POST['existing_image'] ?? null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->handleUpload($_FILES['image']);
        }

        $data = $_POST;
        $data['image_path'] = $imagePath;

        if ($id) {
            $carService->update($id, $data);
            $savedId = $id;
            flash('success', 'Car updated successfully.');
        } else {
            $savedId = $carService->create($data);
            flash('success', 'Car added successfully.');
        }

        // Extra gallery photos (multi-file input: gallery_images[])
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $count = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $file = [
                    'name'     => $_FILES['gallery_images']['name'][$i],
                    'type'     => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_images']['error'][$i],
                    'size'     => $_FILES['gallery_images']['size'][$i],
                ];
                $path = $this->handleUpload($file);
                if ($path) {
                    $carService->addGalleryImage($savedId, $path);
                }
            }
        }

        redirect('admin/cars/' . $savedId . '/edit');
    }

    public function deleteCarImage(int $imageId): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/cars');
        }
        $carService = CarService::make();
        $image = $carService->findGalleryImage($imageId);
        if ($image) {
            $carService->deleteGalleryImage($imageId);
            $fullPath = APP_ROOT . '/public' . $image['image_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            flash('success', 'Photo removed.');
            redirect('admin/cars/' . (int) $image['car_id'] . '/edit');
        }
        flash('error', 'Photo not found.');
        redirect('admin/cars');
    }

    public function deleteCar(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/cars');
        }
        CarService::make()->delete($id);
        flash('success', 'Car removed.');
        redirect('admin/cars');
    }

    private function handleUpload(array $file): ?string
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0755, true);
        }

        $filename = uniqid('car_', true) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $filename);

        return '/assets/images/cars/' . $filename;
    }

    // ---------------------------------------------------------------
    // Bookings
    // ---------------------------------------------------------------
    public function calendar(): void
    {
        Auth::requirePermission('bookings.view');

        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $start = new DateTime($month . '-01');
        $calendarBookings = BookingService::make()->forCalendar($month);

        view('admin/booking-calendar', [
            'seo' => ['title' => 'Booking Calendar | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'month' => $month,
            'monthLabel' => $start->format('F Y'),
            'calendarBookings' => $calendarBookings,
            'cars' => CarService::make()->all(),
        ]);
    }

    public function reports(): void
    {
        Auth::requirePermission('bookings.view');

        $from = trim($_GET['from'] ?? date('Y-m-01'));
        $to = trim($_GET['to'] ?? date('Y-m-t'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-t');

        $bookingService = BookingService::make();
        $bookings = $bookingService->searchAdmin(['from' => $from, 'to' => $to]);
        $stats = $bookingService->operationalStats($from, $to);

        view('admin/reports', [
            'seo' => ['title' => 'Reports | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'from' => $from,
            'to' => $to,
            'bookings' => $bookings,
            'stats' => $stats,
        ]);
    }

    public function exportBookingsCsv(): void
    {
        Auth::requirePermission('bookings.view');

        $from = trim($_GET['from'] ?? date('Y-m-01'));
        $to = trim($_GET['to'] ?? date('Y-m-t'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-t');

        $bookings = BookingService::make()->searchAdmin(['from' => $from, 'to' => $to]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bigkahuna-bookings-' . $from . '-to-' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Booking Ref','Customer','Phone','Email','Car','Plate','Pickup','Return','Driver','Status','Total (KES)']);
        foreach ($bookings as $b) {
            fputcsv($out, [
                $b['booking_ref'], $b['full_name'], $b['phone'], $b['email'], $b['car_name'],
                $b['plate_number'], $b['pickup_date'], $b['return_date'],
                $b['driver_option'], $b['status'], $b['total_price'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function bookings(): void
    {
        Auth::requirePermission('bookings.view');
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'car_id' => trim($_GET['car_id'] ?? ''),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
        ];
        $bookings = BookingService::make()->searchAdmin($filters);
        $bookingIds = array_column($bookings, 'id');
        $payments = PaymentService::make()->forBookings($bookingIds);

        view('admin/bookings', [
            'seo'      => ['title' => 'Bookings | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'bookings' => $bookings,
            'payments' => $payments,
            'filters'  => $filters,
            'cars'     => CarService::make()->all(),
        ]);
    }

    /**
     * Standalone Payments screen — every payment attempt across every
     * gateway, filterable independently of bookings. Also doubles as the
     * manual-verification queue via the "needs_verification" quick filter.
     */
    public function payments(): void
    {
        Auth::requirePermission('bookings.view');

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'gateway' => trim($_GET['gateway'] ?? ''),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
            'needs_verification' => !empty($_GET['needs_verification']) ? '1' : '',
        ];

        $paymentService = PaymentService::make();

        view('admin/payments', [
            'seo'      => ['title' => 'Payments | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'payments' => $paymentService->searchAdmin($filters),
            'filters'  => $filters,
            'manualPaymentsPending' => $paymentService->pendingManualVerificationCount(),
            'gatewayBreakdown' => $paymentService->completedTotalsByGateway(date('Y-m-01'), date('Y-m-t')),
        ]);
    }

    public function bookingDetail(int $id): void
    {
        Auth::requirePermission('bookings.view');
        $booking = BookingService::make()->find($id);

        if (!$booking) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('admin/booking-detail', [
            'seo'     => ['title' => 'Booking ' . $booking['booking_ref'] . ' | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'booking' => $booking,
            'payment' => PaymentService::make()->latestForBooking($id),
            'activity' => AuditService::make()->forBooking($id, 50),
        ]);
    }

    /**
     * Same printable receipt customers get, reachable from the admin
     * booking detail page so staff can reprint or forward a copy.
     */
    public function bookingReceipt(int $id): void
    {
        Auth::requirePermission('bookings.view');
        $booking = BookingService::make()->find($id);

        if (!$booking) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        $payment = PaymentService::make()->latestCompletedForBooking($id);
        if (!$payment) {
            flash('error', 'This booking has no completed payment to generate a receipt for.');
            redirect('admin/bookings/' . $id);
        }

        view('receipt', [
            'booking' => $booking,
            'payment' => $payment,
            'isAdmin' => true,
            'backUrl' => base_url('admin/bookings/' . $id),
        ]);
    }

    public function paymentReceipt(int $paymentId): void
    {
        Auth::requirePermission('bookings.view');
        $payment = PaymentService::make()->findById($paymentId);
        if (!$payment || ($payment['status'] ?? '') !== 'completed') {
            http_response_code(404);
            view('404', ['seo'=>seo_for('home')]);
            return;
        }
        $booking = BookingService::make()->find((int)$payment['booking_id']);
        if (!$booking) {
            http_response_code(404);
            view('404', ['seo'=>seo_for('home')]);
            return;
        }
        view('receipt', [
            'booking'=>$booking,
            'payment'=>$payment,
            'isAdmin'=>true,
            'backUrl'=>base_url('admin/bookings/'.(int)$booking['id']),
        ]);
    }

    public function fleet(): void
    {
        Auth::requirePermission('cars.view');
        $carService=CarService::make();
        view('admin/fleet',[
            'seo'=>['title'=>'Fleet Operations | Admin','description'=>'','keywords'=>'','og_image'=>'','robots'=>'noindex, nofollow'],
            'cars'=>$carService->all(),
            'stats'=>$carService->fleetStats(),
            'alerts'=>$carService->fleetAlerts(),
        ]);
    }

    public function vehicleOperations(int $id): void
    {
        Auth::requirePermission('cars.view');
        $carService=CarService::make();
        $car=$carService->find($id);
        if(!$car){
            http_response_code(404);
            view('404',['seo'=>seo_for('home')]);
            return;
        }
        view('admin/vehicle-operations',[
            'seo'=>['title'=>'Fleet · '. $car['name'].' | Admin','description'=>'','keywords'=>'','og_image'=>'','robots'=>'noindex, nofollow'],
            'car'=>$car,
            'maintenance'=>$carService->maintenance($id),
            'documents'=>$carService->documents($id),
            'odometer'=>$carService->odometerLogs($id,25),
            'latestOdometer'=>$carService->latestOdometer($id),
        ]);
    }

    public function createMaintenance(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet/'.$id); }
        try{
            CarService::make()->createMaintenance($id,Auth::id(),$_POST);
            flash('success','Maintenance item added.');
        }catch(Throwable $e){ error_log('[MAINTENANCE CREATE ERROR] '.$e->getMessage()); flash('error',$e->getMessage()); }
        redirect('admin/fleet/'.$id);
    }

    public function updateMaintenance(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet'); }
        $item=CarService::make()->maintenanceFind($id);
        if(!$item){ flash('error','Maintenance item not found.'); redirect('admin/fleet'); }
        $status=$_POST['status']??'';
        if(CarService::make()->updateMaintenanceStatus($id,$status,Auth::id())) flash('success','Maintenance status updated.');
        else flash('error','Invalid maintenance status.');
        redirect('admin/fleet/'.$item['car_id']);
    }

    public function uploadVehicleDocument(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet/'.$id); }

        $path=null;
        if(isset($_FILES['document']) && $_FILES['document']['error']!==UPLOAD_ERR_NO_FILE){
            $path=$this->handleDocumentUpload($_FILES['document']);
            if(!$path){
                flash('error','Document upload failed. Allowed: PDF, JPG, JPEG, PNG, WEBP. Maximum 5MB.');
                redirect('admin/fleet/'.$id);
            }
        }

        try{
            CarService::make()->createDocument($id,Auth::id(),[
                'document_type'=>$_POST['document_type']??'other',
                'title'=>$_POST['title']??'Vehicle document',
                'document_number'=>$_POST['document_number']??'',
                'file_path'=>$path,
                'issued_date'=>$_POST['issued_date']??'',
                'expiry_date'=>$_POST['expiry_date']??'',
                'notes'=>$_POST['notes']??'',
            ]);
            flash('success','Vehicle document saved.');
        }catch(Throwable $e){ error_log('[DOCUMENT CREATE ERROR] '.$e->getMessage()); flash('error',$e->getMessage()); }
        redirect('admin/fleet/'.$id);
    }

    public function updateVehicleDocument(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet'); }
        $doc=CarService::make()->documentFind($id);
        if(!$doc){ flash('error','Document not found.'); redirect('admin/fleet'); }
        $status=$_POST['status']??'';
        if(CarService::make()->markDocumentStatus($id,$status)) flash('success','Document status updated.');
        else flash('error','Invalid document status.');
        redirect('admin/fleet/'.$doc['car_id']);
    }

    public function deleteVehicleDocument(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet'); }
        $service=CarService::make();
        $doc=$service->documentFind($id);
        if(!$doc){ flash('error','Document not found.'); redirect('admin/fleet'); }
        $service->markDocumentStatus($id,'replaced');
        if(!empty($doc['file_path'])){
            $fullPath=$this->resolveVehicleDocumentPath((string)$doc['file_path']);
            if($fullPath && is_file($fullPath)) @unlink($fullPath);
        }
        flash('success','Vehicle document archived.');
        redirect('admin/fleet/'.$doc['car_id']);
    }

    public function addOdometer(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if(!verify_csrf()){ flash('error','Session expired.'); redirect('admin/fleet/'.$id); }
        try{
            $reading=(float)($_POST['reading_km']??0);
            if($reading<0) throw new RuntimeException('Enter a valid odometer reading.');
            CarService::make()->logOdometer($id,Auth::id(),$reading,'manual',null,$_POST['notes']??'');
            flash('success','Odometer reading recorded.');
        }catch(Throwable $e){ flash('error',$e->getMessage()); }
        redirect('admin/fleet/'.$id);
    }

    private function handleDocumentUpload(array $file): ?string
    {
        $allowed=['pdf','jpg','jpeg','png','webp'];
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return null;
        if(($file['size']??0)>5*1024*1024) return null;
        $ext=strtolower(pathinfo($file['name']??'',PATHINFO_EXTENSION));
        if(!in_array($ext,$allowed,true)) return null;
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) finfo_close($finfo);
        $allowedMimes = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
        if ($mime !== ($allowedMimes[$ext] ?? '')) return null;
        $filename=uniqid('vehicle_doc_',true).'.'.$ext;
        $privateDir = APP_ROOT . '/storage/private/vehicle-documents';
        if (!is_dir($privateDir) && !mkdir($privateDir, 0750, true) && !is_dir($privateDir)) {
            return null;
        }
        $filename = 'vehicle_doc_' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $privateDir . '/' . $filename)) return null;
        return 'private://vehicle-documents/' . $filename;
    }

    public function downloadVehicleDocument(int $id): void
    {
        Auth::requirePermission('cars.view');
        $doc = CarService::make()->documentFind($id);
        if (!$doc || empty($doc['file_path'])) {
            http_response_code(404);
            echo 'Document not found.';
            return;
        }

        $path = $this->resolveVehicleDocumentPath((string)$doc['file_path']);
        if (!$path || !is_file($path) || !is_readable($path)) {
            http_response_code(404);
            echo 'Document file is unavailable.';
            return;
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';
        $allowedMime = ['application/pdf','image/jpeg','image/png','image/webp'];
        if (!in_array($mime, $allowedMime, true)) $mime = 'application/octet-stream';
        $filename = basename($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    private function resolveVehicleDocumentPath(string $storedPath): ?string
    {
        if (str_starts_with($storedPath, 'private://vehicle-documents/')) {
            $filename = basename(substr($storedPath, strlen('private://vehicle-documents/')));
            if ($filename === '' || $filename !== basename($filename)) return null;
            return APP_ROOT . '/storage/private/vehicle-documents/' . $filename;
        }

        // Backwards compatibility for documents uploaded by earlier phases.
        if (str_starts_with($storedPath, '/assets/images/cars/')) {
            $relative = ltrim($storedPath, '/');
            $candidate = APP_ROOT . '/public_html/' . $relative;
            return realpath($candidate) ?: null;
        }
        return null;
    }

    public function handover(int $id): void
    {
        Auth::requirePermission('bookings.view');
        $snapshot = BookingService::make()->rentalSnapshot($id);
        if (!$snapshot) {
            http_response_code(404);
            view('404', ['seo'=>seo_for('home')]);
            return;
        }

        $paymentService = PaymentService::make();
        $paidAmount = $paymentService->completedTotalForBooking($id);
        $remainingBalance = max(0.0, round((float)$snapshot['booking']['total_price'] - $paidAmount, 2));

        view('admin/rental-handover', [
            'seo' => ['title'=>'Rental Handover · '.$snapshot['booking']['booking_ref'].' | Admin','description'=>'','keywords'=>'','og_image'=>'','robots'=>'noindex, nofollow'],
            'snapshot'=>$snapshot,
            'paidAmount'=>$paidAmount,
            'remainingBalance'=>$remainingBalance,
            'paystackEnabled'=>PAYSTACK_ENABLED && PAYSTACK_PUBLIC_KEY !== '' && PAYSTACK_SECRET_KEY !== '' && setting('paystack','enabled','1') === '1',
            'paystackPublicKey'=>PAYSTACK_PUBLIC_KEY,
            'lateGraceMinutes'=>(int)setting('rental','late_return_grace_minutes','30'),
            'extraMileageRate'=>(float)setting('rental','extra_mileage_rate_per_km','0'),
        ]);
    }

    public function checkoutBooking(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if (!verify_csrf()) {
            flash('error','Session expired.');
            redirect('admin/bookings/'.$id.'/handover');
        }

        $ack=!empty($_POST['customer_acknowledged']);
        if (!$ack) {
            flash('error','Customer acknowledgement is required before vehicle checkout.');
            redirect('admin/bookings/'.$id.'/handover');
        }

        try {
            BookingService::make()->checkout($id,Auth::id(),[
                'odometer_km'=>$_POST['odometer_km']??'',
                'fuel_level'=>$_POST['fuel_level']??'',
                'condition_notes'=>$_POST['condition_notes']??'',
                'damage_notes'=>$_POST['damage_notes']??'',
                'customer_acknowledged'=>1,
                'customer_name'=>$_POST['customer_name']??'',
            ]);
            AuditService::make()->log('rental.checked_out', 'Vehicle checked out and rental started.', $id, 'booking', $id);
            $booking=BookingService::make()->find($id);
            if($booking) NotificationService::make()->notifyBookingStatusChanged($booking,'ongoing');
            flash('success','Vehicle checked out successfully. Booking is now ongoing.');
        } catch(Throwable $e) {
            error_log('[CHECKOUT ERROR] '.$e->getMessage());
            flash('error',$e->getMessage());
        }
        redirect('admin/bookings/'.$id.'/handover');
    }

    public function returnBooking(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if (!verify_csrf()) {
            flash('error','Session expired.');
            redirect('admin/bookings/'.$id.'/handover');
        }

        $booking=BookingService::make()->find($id);
        if(!$booking){
            flash('error','Booking not found.');
            redirect('admin/bookings');
        }

        $checkout=BookingService::make()->latestInspection($id,'checkout');
        $autoCharges=[];
        $returnOdo=trim($_POST['odometer_km']??'');
        $lateGrace=(int)setting('rental','late_return_grace_minutes','30');
        $extraRate=(float)setting('rental','extra_mileage_rate_per_km','0');

        if($returnOdo!=='' && $checkout && $checkout['odometer_km']!==null && $extraRate>0){
            $km=max(0,(float)$returnOdo-(float)$checkout['odometer_km']);
            // If a future mileage allowance is configured, it can be layered
            // on later; Phase 7 only charges when an explicit extra-mileage
            // rate is configured.
            $autoCharges[]=['type'=>'extra_mileage','description'=>'Additional mileage ('.$km.' km)','amount'=>$km*$extraRate];
        }

        try {
            $scheduled=new DateTime($booking['return_date']);
            $actual=new DateTime();
            $grace=(clone $scheduled)->modify('+'.$lateGrace.' minutes');
            if($actual>$grace){
                $daysLate=max(1,(int)ceil(($actual->getTimestamp()-$scheduled->getTimestamp())/86400));
                $autoCharges[]=[
                    'type'=>'late_return',
                    'description'=>'Late return charge ('.$daysLate.' day'.($daysLate>1?'s':'').')',
                    'amount'=>$daysLate*(float)$booking['total_price']/max(1,(int)$booking['total_days'])
                ];
            }

            BookingService::make()->returnVehicle($id,Auth::id(),[
                'odometer_km'=>$returnOdo,
                'fuel_level'=>$_POST['fuel_level']??'',
                'condition_notes'=>$_POST['condition_notes']??'',
                'damage_notes'=>$_POST['damage_notes']??'',
                'customer_acknowledged'=>!empty($_POST['customer_acknowledged']),
                'customer_name'=>$_POST['customer_name']??'',
                'needs_maintenance'=>!empty($_POST['needs_maintenance']),
            ],$autoCharges);

            AuditService::make()->log('rental.returned', 'Vehicle returned and rental completed.', $id, 'booking', $id, ['needs_maintenance' => !empty($_POST['needs_maintenance'])]);
            $booking=BookingService::make()->find($id);
            if($booking) { NotificationService::make()->notifyBookingStatusChanged($booking,'completed'); NotificationService::make()->notifyRentalCompleted($booking); }
            flash('success','Vehicle returned and rental closed successfully.');
        } catch(Throwable $e) {
            error_log('[RETURN ERROR] '.$e->getMessage());
            flash('error',$e->getMessage());
        }
        redirect('admin/bookings/'.$id.'/handover');
    }

    public function addRentalCharge(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if(!verify_csrf()){
            flash('error','Session expired.');
            redirect('admin/bookings/'.$id.'/handover');
        }
        try {
            BookingService::make()->addCharge(
                $id,Auth::id(),
                $_POST['charge_type']??'other',
                trim($_POST['description']??''),
                (float)($_POST['amount']??0)
            );
            flash('success','Additional charge added.');
        } catch(Throwable $e) {
            flash('error',$e->getMessage());
        }
        redirect('admin/bookings/'.$id.'/handover');
    }

    public function updateRentalCharge(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if(!verify_csrf()){
            flash('error','Session expired.');
            redirect('admin/bookings');
        }
        $status=$_POST['status']??'';
        if(BookingService::make()->updateChargeStatus($id,$status)) flash('success','Charge status updated.');
        else flash('error','Invalid charge status.');
        $bookingId=(int)($_POST['booking_id']??0);
        redirect($bookingId ? 'admin/bookings/'.$bookingId.'/handover' : 'admin/bookings');
    }

    public function verifyManualPayment(int $id): void
    {
        Auth::requirePermission('bookings.manage');

        if (!verify_csrf()) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Session expired. Please refresh the page and try again.'], 419);
            flash('error', 'Session expired.');
            redirect('admin/bookings');
        }

        $returnTo = ($_POST['return'] ?? '') === 'payments' ? 'admin/payments' : null;

        $paymentService = PaymentService::make();
        $payment = $paymentService->findById($id);

        if (!$payment || $payment['payment_method'] !== 'manual') {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Manual payment not found.'], 404);
            flash('error', 'Manual payment not found.');
            redirect($returnTo ?? 'admin/bookings');
        }

        if ($payment['status'] !== 'pending' || empty($payment['mpesa_receipt_number'])) {
            $message = 'This manual payment is not ready for verification.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 422);
            flash('error', $message);
            redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
        }

        $bookingBefore = BookingService::make()->find((int)$payment['booking_id']);
        if (!$bookingBefore || $bookingBefore['status'] !== 'pending') {
            $message = 'Only a pending booking can be confirmed by a deposit payment.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 422);
            flash('error', $message);
            redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
        }

        if (!$paymentService->verifyManual($id, Auth::id())) {
            $message = 'Could not verify this payment. It may already have been processed.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 409);
            flash('error', $message);
            redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
        }

        if (!BookingService::make()->updateStatus((int)$payment['booking_id'], 'confirmed')) {
            $message = 'Payment was verified, but the booking could not be confirmed. Please review it manually.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 500);
            flash('error', $message);
            redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
        }
        AuditService::make()->log('payment.manual_verified', 'Manual M-Pesa payment verified and booking confirmed.', (int)$payment['booking_id'], 'payment', $id, ['amount' => $payment['amount'], 'receipt' => $payment['mpesa_receipt_number']]);
        $booking = BookingService::make()->find((int)$payment['booking_id']);
        $completed = PaymentService::make()->findById($id);

        if ($booking && $completed) {
            NotificationService::make()->notifyPaymentReceived($booking, $completed);
            NotificationService::make()->notifyAdminPaymentReceived($booking, $completed);
        }

        if (wants_json()) {
            json_out([
                'ok' => true,
                'message' => 'Manual M-Pesa payment verified and booking confirmed.',
                'paymentStatus' => 'completed',
                'bookingStatus' => 'confirmed',
            ]);
        }
        flash('success', 'Manual M-Pesa payment verified and booking confirmed.');
        redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
    }

    public function rejectManualPayment(int $id): void
    {
        Auth::requirePermission('bookings.manage');

        if (!verify_csrf()) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Session expired. Please refresh the page and try again.'], 419);
            flash('error', 'Session expired.');
            redirect('admin/bookings');
        }

        $returnTo = ($_POST['return'] ?? '') === 'payments' ? 'admin/payments' : null;

        $payment = PaymentService::make()->findById($id);
        if (!$payment || $payment['payment_method'] !== 'manual') {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Manual payment not found.'], 404);
            flash('error', 'Manual payment not found.');
            redirect($returnTo ?? 'admin/bookings');
        }

        PaymentService::make()->rejectManual($id);
        AuditService::make()->log('payment.manual_rejected', 'Manual M-Pesa payment rejected.', (int)$payment['booking_id'], 'payment', $id, ['receipt' => $payment['mpesa_receipt_number'] ?? null]);

        if (wants_json()) {
            json_out([
                'ok' => true,
                'message' => 'Manual payment marked as rejected.',
                'paymentStatus' => 'failed',
            ]);
        }
        flash('success', 'Manual payment marked as rejected.');
        redirect($returnTo ?? ('admin/bookings/' . (int)$payment['booking_id']));
    }

    /**
     * Push a booking's return date later — the customer wants to keep the
     * car longer. Blocks the change if it would run into the next booking
     * for that car (respecting the same turnaround buffer used at booking
     * time) so staff can't accidentally create a real double-booking by
     * extending a rental into a slot someone else already paid a deposit
     * for.
     */
    public function extendBooking(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if (!verify_csrf()) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Session expired. Please refresh the page and try again.'], 419);
            flash('error', 'Session expired.');
            redirect('admin/bookings/' . $id);
        }

        $booking = BookingService::make()->find($id);
        if (!$booking) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Booking not found.'], 404);
            flash('error', 'Booking not found.');
            redirect('admin/bookings');
        }

        $newReturnRaw = trim($_POST['new_return_date'] ?? '');
        if ($newReturnRaw === '') {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Enter the new return date and time.'], 422);
            flash('error', 'Enter the new return date and time.');
            redirect('admin/bookings/' . $id);
        }

        $car = CarService::make()->find((int)$booking['car_id']);
        if (!$car) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'The vehicle for this booking could not be found.'], 404);
            flash('error', 'The vehicle for this booking could not be found.');
            redirect('admin/bookings/' . $id);
        }

        try {
            $newReturn = new DateTime($newReturnRaw);
            $chauffeurFee = $booking['driver_option'] === 'with_driver'
                ? CarService::make()->effectiveChauffeurFee($car)
                : 0.0;

            $conflictRef = BookingService::make()->extendBooking(
                $id,
                $newReturn->format('Y-m-d H:i:s'),
                (float)$car['price_per_day'],
                $chauffeurFee
            );

            if ($conflictRef !== null) {
                $message = "Can't extend — that would overlap booking {$conflictRef} for this vehicle. Move the other booking, choose a different car, or pick an earlier return date.";
                if (wants_json()) json_out(['ok'=>false,'message'=>$message,'conflictRef'=>$conflictRef], 409);
                flash('error', $message);
                redirect('admin/bookings/' . $id);
            }

            AuditService::make()->log('booking.extended', 'Rental return date extended to '.$newReturn->format('d M Y, H:i').'.', $id, 'booking', $id, ['old_return' => $booking['return_date'], 'new_return' => $newReturn->format('Y-m-d H:i:s')]);
            $updated = BookingService::make()->find($id);
            if ($updated) {
                $completedPayment = PaymentService::make()->latestCompletedForBooking($id);
                $amountPaid = $completedPayment ? (float)$completedPayment['amount'] : 0.0;
                NotificationService::make()->notifyBookingExtended($updated, $booking['return_date'], $amountPaid);
            }

            $message = 'Booking extended to ' . $newReturn->format('d M Y, H:i') . '. The customer has been notified of the new date and price.';
            if (wants_json()) {
                json_out([
                    'ok' => true,
                    'message' => $message,
                    'newReturnDate' => $newReturn->format('d M Y, H:i'),
                    'totalPrice' => $updated ? money($updated['total_price']) : null,
                ]);
            }
            flash('success', $message);
        } catch (Throwable $e) {
            $message = $e->getMessage() ?: 'Could not extend this booking.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 422);
            flash('error', $message);
        }

        redirect('admin/bookings/' . $id);
    }

    /**
     * Live, read-only pre-check used by the "Extend Rental" form: as staff
     * pick a new return date, this tells them immediately whether it
     * overlaps another booking for the same vehicle — before they submit
     * and hit the same guard as a full-page error. Never writes anything.
     */
    public function extendCheck(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $booking = BookingService::make()->find($id);
        if (!$booking) {
            json_out(['ok'=>false,'message'=>'Booking not found.'], 404);
        }

        $newReturnRaw = trim((string)($_GET['new_return_date'] ?? ''));
        if ($newReturnRaw === '') {
            json_out(['ok'=>true,'conflict'=>false]);
        }

        try {
            $newReturn = new DateTime($newReturnRaw);
        } catch (Throwable) {
            json_out(['ok'=>false,'message'=>'Please choose a valid date and time.'], 422);
        }

        $currentReturn = new DateTime($booking['return_date']);
        if ($newReturn <= $currentReturn) {
            json_out(['ok'=>true,'conflict'=>false,'warning'=>'The new return date must be later than the current return date.']);
        }

        $conflictRef = BookingService::make()->conflictingBookingRef(
            (int)$booking['car_id'],
            $booking['pickup_date'],
            $newReturn->format('Y-m-d H:i:s'),
            $id
        );

        json_out([
            'ok' => true,
            'conflict' => $conflictRef !== null,
            'conflictRef' => $conflictRef,
            'message' => $conflictRef !== null
                ? "This overlaps booking {$conflictRef} for this vehicle — the extension will be blocked. Pick an earlier date or move the other booking first."
                : 'No conflict — this vehicle is free through the new return date.',
        ]);
    }

    public function updateBookingStatus(int $id): void
    {
        Auth::requirePermission('bookings.manage');
        if (!verify_csrf()) {
            if (wants_json()) json_out(['ok'=>false,'message'=>'Session expired. Please refresh the page and try again.'], 419);
            flash('error','Session expired.');
            redirect('admin/bookings');
        }

        $newStatus=$_POST['status']??'';
        $booking=BookingService::make()->find($id);
        if(!$booking){
            if (wants_json()) json_out(['ok'=>false,'message'=>'Booking not found.'], 404);
            flash('error','Booking not found.');
            redirect('admin/bookings');
        }

        $allowed=[
            'pending'=>['confirmed','cancelled'],
            'confirmed'=>['cancelled'],
            'ongoing'=>[],
            'completed'=>[],
            'cancelled'=>[],
        ];

        if(!in_array($newStatus,$allowed[$booking['status']]??[],true)){
            $message = 'That status transition is not allowed. Use the rental handover screen for vehicle checkout and return.';
            if (wants_json()) json_out(['ok'=>false,'message'=>$message], 422);
            flash('error',$message);
            redirect('admin/bookings/'.$id);
        }

        BookingService::make()->updateStatus($id,$newStatus);
        AuditService::make()->log('booking.status_changed', 'Booking status changed to '.ucfirst($newStatus).'.', $id, 'booking', $id, ['status' => $newStatus]);
        $booking=BookingService::make()->find($id);
        if($booking) NotificationService::make()->notifyBookingStatusChanged($booking,$newStatus);

        if (wants_json()) {
            json_out([
                'ok' => true,
                'message' => 'Booking status updated and customer notified.',
                'status' => $newStatus,
                'statusLabel' => ucfirst($newStatus),
            ]);
        }
        flash('success','Booking status updated and customer notified.');
        redirect('admin/bookings/'.$id);
    }


    // ---------------------------------------------------------------
    // Activity / operations audit
    // ---------------------------------------------------------------
    public function activity(): void
    {
        Auth::requirePermission('bookings.view');
        view('admin/activity', [
            'seo' => ['title' => 'Activity | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'logs' => AuditService::make()->recent(100),
        ]);
    }

    // ---------------------------------------------------------------
    // Messages
    // ---------------------------------------------------------------
    public function messages(): void
    {
        Auth::requirePermission('messages.view');
        view('admin/messages', [
            'seo'      => ['title' => 'Messages | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'messages' => ContactService::make()->all(),
        ]);
    }

    public function replyMessage(int $id): void
    {
        Auth::requirePermission('messages.view');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/messages');
        }

        $replyText = trim($_POST['reply'] ?? '');
        if ($replyText === '') {
            flash('error', 'Please write a reply before sending.');
            redirect('admin/messages');
        }

        $contactService = ContactService::make();
        $message = $contactService->find($id);

        if (!$message) {
            flash('error', 'Message not found.');
            redirect('admin/messages');
        }

        try {
            NotificationService::make()->sendMessageReply($message, $replyText);
            $contactService->markReplied($id, $replyText, Auth::id());
            flash('success', 'Reply sent to ' . $message['email'] . '.');
        } catch (Throwable $e) {
            error_log('[ADMIN REPLY ERROR] ' . $e->getMessage());
            flash('error', 'Could not send the reply email: ' . $e->getMessage());
        }

        redirect('admin/messages');
    }

    // ---------------------------------------------------------------
    // Car categories
    // ---------------------------------------------------------------
    public function categories(): void
    {
        Auth::requirePermission('cars.manage');
        $carService = CarService::make();

        view('admin/categories', [
            'seo'        => ['title' => 'Categories | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'categories' => $carService->allCategories(),
            'carCounts'  => $carService->categoryCarCounts(),
        ]);
    }

    public function categoryForm(?int $id = null): void
    {
        Auth::requirePermission('cars.manage');
        $category = $id ? CarService::make()->findCategory($id) : null;

        if ($id && !$category) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('admin/category-form', [
            'seo'      => ['title' => ($id ? 'Edit' : 'Add') . ' Category | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'category' => $category,
        ]);
    }

    public function saveCategory(): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/categories');
        }

        $carService = CarService::make();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $data = $_POST;

        if (trim($data['name'] ?? '') === '') {
            flash('error', 'Category name is required.');
            redirect($id ? 'admin/categories/' . $id . '/edit' : 'admin/categories/new');
        }

        if ($id) {
            $carService->updateCategory($id, $data);
            flash('success', 'Category updated successfully.');
        } else {
            $carService->createCategory($data);
            flash('success', 'Category added successfully.');
        }

        redirect('admin/categories');
    }

    public function deleteCategory(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/categories');
        }
        CarService::make()->deleteCategory($id);
        flash('success', 'Category removed. Any cars in it are now uncategorized.');
        redirect('admin/categories');
    }

    // ---------------------------------------------------------------
    // Chauffeur rates (per-location "with driver" pricing)
    // ---------------------------------------------------------------
    public function chauffeurRates(): void
    {
        Auth::requirePermission('cars.manage');
        view('admin/chauffeur-rates', [
            'seo'   => ['title' => 'Chauffeur Rates | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'rates' => CarService::make()->chauffeurRates(),
        ]);
    }

    public function chauffeurRateForm(?int $id = null): void
    {
        Auth::requirePermission('cars.manage');
        $rate = $id ? CarService::make()->findChauffeurRate($id) : null;

        if ($id && !$rate) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('admin/chauffeur-rate-form', [
            'seo'  => ['title' => ($id ? 'Edit' : 'Add') . ' Chauffeur Rate | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'rate' => $rate,
        ]);
    }

    public function saveChauffeurRate(): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/chauffeur-rates');
        }

        $carService = CarService::make();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $location = trim($_POST['location'] ?? '');
        $rate = (float) ($_POST['rate_per_day'] ?? 0);

        if ($location === '' || $rate <= 0) {
            flash('error', 'Location and a rate greater than zero are required.');
            redirect($id ? 'admin/chauffeur-rates/' . $id . '/edit' : 'admin/chauffeur-rates/new');
        }

        if ($id) {
            $carService->updateChauffeurRate($id, $location, $rate);
            flash('success', 'Chauffeur rate updated successfully.');
        } else {
            $carService->createChauffeurRate($location, $rate);
            flash('success', 'Chauffeur rate added successfully.');
        }

        redirect('admin/chauffeur-rates');
    }

    public function deleteChauffeurRate(int $id): void
    {
        Auth::requirePermission('cars.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/chauffeur-rates');
        }
        CarService::make()->deleteChauffeurRate($id);
        flash('success', 'Chauffeur rate removed. Cars in that location will fall back to the sitewide default.');
        redirect('admin/chauffeur-rates');
    }

    // ---------------------------------------------------------------
    // Testimonials
    // ---------------------------------------------------------------
    public function testimonials(): void
    {
        Auth::requirePermission('settings.manage');
        view('admin/testimonials', [
            'seo'          => ['title' => 'Testimonials | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'testimonials' => SettingsService::make()->allTestimonials(),
        ]);
    }

    public function testimonialForm(?int $id = null): void
    {
        Auth::requirePermission('settings.manage');
        $testimonial = $id ? SettingsService::make()->findTestimonial($id) : null;

        if ($id && !$testimonial) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        view('admin/testimonial-form', [
            'seo'         => ['title' => ($id ? 'Edit' : 'Add') . ' Testimonial | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'testimonial' => $testimonial,
        ]);
    }

    public function saveTestimonial(): void
    {
        Auth::requirePermission('settings.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/testimonials');
        }

        $settingsService = SettingsService::make();
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $data = $_POST;

        if (trim($data['client_name'] ?? '') === '' || trim($data['message'] ?? '') === '') {
            flash('error', 'Client name and message are required.');
            redirect($id ? 'admin/testimonials/' . $id . '/edit' : 'admin/testimonials/new');
        }

        if ($id) {
            $settingsService->updateTestimonial($id, $data);
            flash('success', 'Testimonial updated successfully.');
        } else {
            $settingsService->createTestimonial($data);
            flash('success', 'Testimonial added successfully.');
        }

        redirect('admin/testimonials');
    }

    public function deleteTestimonial(int $id): void
    {
        Auth::requirePermission('settings.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired. Please try again.');
            redirect('admin/testimonials');
        }
        SettingsService::make()->deleteTestimonial($id);
        flash('success', 'Testimonial removed.');
        redirect('admin/testimonials');
    }

    // ---------------------------------------------------------------
    // Settings (general + SEO)
    // ---------------------------------------------------------------
    public function settings(): void
    {
        Auth::requirePermission('settings.manage');
        $settingsService = SettingsService::make();

        view('admin/settings', [
            'seo'                => ['title' => 'Settings | Admin', 'description' => '', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow'],
            'general'            => $settingsService->group('general'),
            'seoItems'           => $settingsService->group('seo'),
            'paystackItems'      => $settingsService->group('paystack'),
            'legalItems'         => $settingsService->group('legal'),
            'notificationItems'  => $settingsService->group('notifications'),
        ]);
    }

    public function saveSettings(): void
    {
        Auth::requirePermission('settings.manage');
        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/settings');
        }

        $group = $_POST['group'] ?? 'general';
        $values = $_POST['settings'] ?? [];
        unset($values['csrf_token']);

        SettingsService::make()->saveGroup($group, $values);
        flash('success', ucfirst($group) . ' settings updated.');
        redirect('admin/settings');
    }
}
