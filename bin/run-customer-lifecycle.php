<?php
declare(strict_types=1);

require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/includes/functions.php';
require dirname(__DIR__).'/includes/Auth.php';
require dirname(__DIR__).'/includes/CustomerAuth.php';
require dirname(__DIR__).'/app/Services/MpesaService.php';
require dirname(__DIR__).'/app/Services/AuditService.php';
require dirname(__DIR__).'/app/Services/WhatsAppService.php';
require dirname(__DIR__).'/app/Services/MailService.php';
require dirname(__DIR__).'/app/Services/SmsService.php';
require dirname(__DIR__).'/app/Services/PaymentService.php';
require dirname(__DIR__).'/app/Services/BookingService.php';
require dirname(__DIR__).'/app/Services/NotificationService.php';

if (setting('notifications','whatsapp_enabled','0') !== '1'
    || setting('notifications','whatsapp_customer_enabled','0') !== '1'
    || (new WhatsAppService())->provider() !== 'cloud_api') {
    echo "Customer WhatsApp automation disabled or Cloud API unavailable.\n";
    exit;
}

$db = Database::connection();
$lockName = 'bigkahuna_customer_lifecycle';
$lock = (int)$db->query("SELECT GET_LOCK(" . $db->quote($lockName) . ", 0)")->fetchColumn();
if ($lock !== 1) {
    echo "Customer lifecycle job is already running; skipping this run.\n";
    exit(0);
}

$notifications = NotificationService::make();
$payments = PaymentService::make();

function already_sent(PDO $db, int $bookingId, string $key): bool
{
    $s = $db->prepare("SELECT COUNT(*) FROM notification_logs WHERE booking_id=:id AND channel='whatsapp' AND event_key=:key AND status='sent'");
    $s->execute([':id'=>$bookingId, ':key'=>$key]);
    return (int)$s->fetchColumn() > 0;
}

function rows(PDO $db, string $sql, array $params=[]): array
{
    $s=$db->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

try {
    $pickupHours=max(1,(int)setting('notifications','whatsapp_reminder_hours','24'));
    $from=date('Y-m-d H:i:s',time()+($pickupHours-1)*3600);
    $to=date('Y-m-d H:i:s',time()+($pickupHours+1)*3600);
    foreach(rows($db,
        "SELECT b.*, c.name AS car_name FROM bookings b JOIN cars c ON c.id=b.car_id
         WHERE b.status='confirmed' AND b.whatsapp_opt_in=1 AND b.pickup_date BETWEEN :f AND :t",
        [':f'=>$from,':t'=>$to]) as $b) {
        if (!already_sent($db,(int)$b['id'],'pickup_reminder')) {
            $notifications->notifyPickupReminder($b);
        }
    }

    if(setting('notifications','whatsapp_payment_due_enabled','1')==='1') {
        $dueHours=max(1,(int)setting('notifications','whatsapp_payment_due_hours','2'));
        $f=date('Y-m-d H:i:s',time()+($dueHours-1)*3600);
        $t=date('Y-m-d H:i:s',time()+($dueHours+1)*3600);
        foreach(rows($db,
            "SELECT b.*, c.name AS car_name FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.status='confirmed' AND b.whatsapp_opt_in=1 AND b.pickup_date BETWEEN :f AND :t",
            [':f'=>$f,':t'=>$t]) as $b) {
            $paid=$payments->completedTotalForBooking((int)$b['id']);
            $balance=max(0,round((float)$b['total_price']-$paid,2));
            if($balance>0 && !already_sent($db,(int)$b['id'],'payment_due')) {
                $notifications->notifyPaymentDue($b,$balance);
            }
        }
    }

    $returnHours=max(1,(int)setting('notifications','whatsapp_return_reminder_hours','4'));
    $f=date('Y-m-d H:i:s',time()+($returnHours-1)*3600);
    $t=date('Y-m-d H:i:s',time()+($returnHours+1)*3600);
    if(setting('notifications','whatsapp_return_reminders_enabled','1')==='1') {
        foreach(rows($db,
            "SELECT b.*, c.name AS car_name FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.status='ongoing' AND b.whatsapp_opt_in=1 AND b.return_date BETWEEN :f AND :t",
            [':f'=>$f,':t'=>$t]) as $b) {
            if(!already_sent($db,(int)$b['id'],'return_reminder')) {
                $notifications->notifyReturnReminder($b);
            }
        }
    }

    $delay=max(1,(int)setting('notifications','whatsapp_review_delay_hours','24'));
    $f=date('Y-m-d H:i:s',time()-($delay+1)*3600);
    $t=date('Y-m-d H:i:s',time()-($delay-1)*3600);
    if(setting('notifications','whatsapp_post_rental_enabled','1')==='1') {
        foreach(rows($db,
            "SELECT b.*, c.name AS car_name FROM bookings b JOIN cars c ON c.id=b.car_id
             WHERE b.status='completed' AND b.whatsapp_opt_in=1 AND b.updated_at BETWEEN :f AND :t",
            [':f'=>$f,':t'=>$t]) as $b) {
            if(!already_sent($db,(int)$b['id'],'review_request')) {
                $notifications->notifyReviewRequest($b);
            }
        }
    }

    echo "Customer lifecycle run complete.\n";
} finally {
    $db->query("SELECT RELEASE_LOCK(" . $db->quote($lockName) . ")");
}
