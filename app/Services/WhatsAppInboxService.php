<?php
declare(strict_types=1);

final class WhatsAppInboxService
{
    private PDO $db;

    public function __construct(?PDO $db = null) { $this->db = $db ?: Database::connection(); }
    public static function make(): self { return new self(); }

    public function conversations(bool $unreadOnly = false): array
    {
        $where = $unreadOnly ? 'WHERE c.unread_count > 0' : '';
        $stmt = $this->db->query(
            "SELECT c.*, b.booking_ref, b.status AS booking_status,
                    (SELECT body FROM whatsapp_messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) AS last_message
             FROM whatsapp_conversations c
             LEFT JOIN bookings b ON b.id=c.booking_id
             {$where}
             ORDER BY (c.unread_count > 0) DESC, COALESCE(c.updated_at,c.created_at) DESC"
        );
        return $stmt->fetchAll();
    }

    public function findConversation(int $id): ?array
    {
        $stmt=$this->db->prepare('SELECT c.*, b.booking_ref, b.status AS booking_status FROM whatsapp_conversations c LEFT JOIN bookings b ON b.id=c.booking_id WHERE c.id=:id');
        $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row ?: null;
    }

    public function messages(int $conversationId, int $limit=100): array
    {
        $limit=max(1,min(300,$limit));
        $stmt=$this->db->query("SELECT * FROM whatsapp_messages WHERE conversation_id=".(int)$conversationId." ORDER BY id ASC LIMIT {$limit}");
        return $stmt->fetchAll();
    }

    public function receive(string $phone, ?string $name, string $type, ?string $body, ?string $providerId, array $raw, ?string $mediaUrl=null): int
    {
        if ($providerId !== null && trim($providerId) !== '') {
            $existing = $this->db->prepare('SELECT id FROM whatsapp_messages WHERE provider_message_id=:pid LIMIT 1');
            $existing->execute([':pid'=>trim($providerId)]);
            $existingId = $existing->fetchColumn();
            if ($existingId !== false) return (int)$existingId;
        }

        $phone=(new MpesaService())->normalizePhone($phone);
        if ($phone==='') throw new RuntimeException('Invalid WhatsApp inbound phone number.');
        $booking=$this->latestBookingForPhone($phone);
        $stmt=$this->db->prepare(
            'INSERT INTO whatsapp_conversations (phone,customer_name,booking_id,status,last_inbound_at,unread_count)
             VALUES (:phone,:name,:booking_id,\'open\',NOW(),1)
             ON DUPLICATE KEY UPDATE customer_name=COALESCE(VALUES(customer_name),customer_name), booking_id=COALESCE(VALUES(booking_id),booking_id), status=\'open\', last_inbound_at=NOW(), unread_count=unread_count+1, updated_at=NOW()'
        );
        $stmt->execute([':phone'=>$phone,':name'=>$name ?: null,':booking_id'=>$booking['id'] ?? null]);
        $id=(int)$this->db->query("SELECT id FROM whatsapp_conversations WHERE phone=".$this->db->quote($phone))->fetchColumn();
        $m=$this->db->prepare('INSERT INTO whatsapp_messages (conversation_id,booking_id,direction,message_type,body,provider_message_id,raw_payload,media_url) VALUES (:cid,:bid,\'inbound\',:type,:body,:pid,:raw,:media)');
        $m->execute([':cid'=>$id,':bid'=>$booking['id'] ?? null,':type'=>$type,':body'=>$body,':pid'=>$providerId,':raw'=>json_encode($raw,JSON_UNESCAPED_SLASHES),':media'=>$mediaUrl]);
        AuditService::make()->log('whatsapp.message.received','Incoming WhatsApp message received.',isset($booking['id'])?(int)$booking['id']:null,'whatsapp_conversation',$id,['phone'=>$phone,'type'=>$type]);
        return $id;
    }

    public function sendReply(int $conversationId, string $body): string
    {
        $conversation=$this->findConversation($conversationId);
        if (!$conversation) throw new RuntimeException('Conversation not found.');
        if (empty($conversation['last_inbound_at']) || strtotime($conversation['last_inbound_at']) < time()-86400) {
            throw new RuntimeException('The WhatsApp customer-service window has expired. Use an approved template to start the conversation again.');
        }
        $body=trim($body); if ($body==='') throw new RuntimeException('Reply cannot be empty.');
        $messageId=(new WhatsAppService())->sendText($conversation['phone'],$body);
        $stmt=$this->db->prepare('INSERT INTO whatsapp_messages (conversation_id,booking_id,direction,message_type,body,provider_message_id,provider_status) VALUES (:cid,:bid,\'outbound\',\'text\',:body,:pid,\'sent\')');
        $stmt->execute([':cid'=>$conversationId,':bid'=>$conversation['booking_id'] ?: null,':body'=>$body,':pid'=>$messageId]);
        $this->db->prepare('UPDATE whatsapp_conversations SET last_outbound_at=NOW(), updated_at=NOW(), unread_count=0 WHERE id=:id')->execute([':id'=>$conversationId]);
        AuditService::make()->log('whatsapp.message.sent','Admin sent a WhatsApp reply.', $conversation['booking_id'] ? (int)$conversation['booking_id'] : null,'whatsapp_conversation',$conversationId);
        return $messageId ?: '';
    }

    public function markRead(int $conversationId): void
    {
        $this->db->prepare('UPDATE whatsapp_conversations SET unread_count=0 WHERE id=:id')->execute([':id'=>$conversationId]);
    }

    public function updateStatus(int $conversationId,string $status): void
    {
        if(!in_array($status,['open','closed'],true)) throw new InvalidArgumentException('Invalid conversation status.');
        $this->db->prepare('UPDATE whatsapp_conversations SET status=:status WHERE id=:id')->execute([':status'=>$status,':id'=>$conversationId]);
    }

    public function applyStatus(string $providerId,string $status): void
    {
        if($providerId==='') return;
        $stmt=$this->db->prepare('UPDATE whatsapp_messages SET provider_status=:status WHERE provider_message_id=:pid');
        $stmt->execute([':status'=>$status,':pid'=>$providerId]);
    }

    private function latestBookingForPhone(string $phone): ?array
    {
        $stmt=$this->db->prepare("SELECT id, booking_ref, first_name, last_name, status FROM bookings WHERE phone=:phone ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':phone'=>$phone]); $row=$stmt->fetch(); return $row ?: null;
    }
}
