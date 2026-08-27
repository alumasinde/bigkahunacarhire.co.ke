<?php view('admin/layout-header', ['seo' => $seo]); ?>
<div class="wa-page">
  <div class="ops-toolbar">
    <div><span class="section-eyebrow">CUSTOMER COMMUNICATIONS</span><h2>WhatsApp Inbox</h2><p>Reply to customers from one place. New inbound messages appear here through the WhatsApp Cloud webhook.</p></div>
    <div class="ops-toolbar-actions"><a class="btn btn-outline" href="<?= base_url('admin/dashboard') ?>"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
  </div>
  <?php if (($conversation === null) && empty($conversations)): ?>
    <div class="card wa-empty"><i class="fa-brands fa-whatsapp"></i><strong>No WhatsApp conversations yet</strong><span>Once a customer replies to your business WhatsApp number, their conversation will appear here.</span></div>
  <?php else: ?>
    <div class="wa-shell">
      <aside class="wa-list card">
        <div class="wa-list-head"><strong>Conversations</strong><span><?= count($conversations) ?></span></div>
        <?php foreach($conversations as $c): ?>
          <a class="wa-conversation <?= $conversation && (int)$conversation['id']===(int)$c['id'] ? 'is-active':'' ?> <?= (int)$c['unread_count']>0?'is-unread':'' ?>" href="<?= base_url('admin/whatsapp?conversation='.(int)$c['id']) ?>">
            <span class="wa-avatar"><i class="fa-brands fa-whatsapp"></i></span>
            <span class="wa-conv-main"><strong><?= e($c['customer_name'] ?: $c['phone']) ?></strong><small><?= e($c['last_message'] ?: 'No message body') ?></small></span>
            <span class="wa-conv-meta"><?php if((int)$c['unread_count']>0): ?><b><?= (int)$c['unread_count'] ?></b><?php endif; ?><small><?= e(date('H:i',strtotime($c['updated_at']))) ?></small></span>
          </a>
        <?php endforeach; ?>
      </aside>
      <section class="wa-chat card">
        <?php if(!$conversation): ?>
          <div class="wa-empty"><i class="fa-regular fa-comments"></i><strong>Select a conversation</strong><span>Choose a customer from the left to view the conversation.</span></div>
        <?php else: ?>
          <header class="wa-chat-head">
            <div><strong><?= e($conversation['customer_name'] ?: $conversation['phone']) ?></strong><small><?= e($conversation['phone']) ?><?php if($conversation['booking_ref']): ?> · <a href="<?= base_url('admin/bookings/'.(int)$conversation['booking_id']) ?>"><?= e($conversation['booking_ref']) ?></a><?php endif; ?></small></div>
            <form action="<?= base_url('admin/whatsapp/'.(int)$conversation['id'].'/status') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $conversation['status']==='open'?'closed':'open' ?>"><button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-<?= $conversation['status']==='open'?'box-archive':'box-open' ?>"></i> <?= $conversation['status']==='open'?'Close':'Reopen' ?></button></form>
          </header>
          <div class="wa-messages">
            <?php foreach($messages as $m): ?>
              <div class="wa-bubble-row <?= $m['direction']==='outbound'?'outbound':'inbound' ?>"><div class="wa-bubble"><div><?= nl2br(e((string)$m['body'])) ?></div><small><?= e(date('d M H:i',strtotime($m['created_at']))) ?><?php if($m['direction']==='outbound' && $m['provider_status']): ?> · <?= e($m['provider_status']) ?><?php endif; ?></small></div></div>
            <?php endforeach; ?>
          </div>
          <?php $activeWindow=!empty($conversation['last_inbound_at']) && strtotime($conversation['last_inbound_at']) >= time()-86400; ?>
          <div class="wa-reply">
            <?php if($activeWindow): ?>
              <form action="<?= base_url('admin/whatsapp/'.(int)$conversation['id'].'/reply') ?>" method="post"><?= csrf_field() ?><textarea name="message" rows="3" placeholder="Reply to <?= e($conversation['customer_name'] ?: 'customer') ?>..." required></textarea><div class="wa-reply-foot"><small>Customer-service window is active.</small><button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Send WhatsApp</button></div></form>
            <?php else: ?>
              <div class="wa-window-expired"><i class="fa-solid fa-clock"></i><span><strong>Reply window expired.</strong> WhatsApp requires an approved template to restart the conversation. Use the booking notification templates or ask the customer to message again.</span></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>
</div>
<?php view('admin/layout-footer'); ?>
