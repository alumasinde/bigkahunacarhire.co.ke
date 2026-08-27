<?php
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($seo['title']) ?></title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<?php
$adminCssComponents = [
    'css/components/00-tokens.css',
    'css/components/02-buttons.css',
    'css/components/03-forms.css',
    'css/components/06-sections.css',
    'css/components/10-admin-layout.css',
    'css/components/11-admin-components.css',
];
foreach ($adminCssComponents as $component): ?>
<link rel="stylesheet" href="<?= asset($component) ?>">
<?php endforeach; ?>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>BIG KAHUNA</h1>
    <p class="sub">Sign in to the admin panel</p>
    <?php foreach ($flashes as $type => $msg): ?>
      <div class="alert alert-<?= $type === 'error' ? 'error' : 'success' ?>"><?= e($msg) ?></div>
    <?php endforeach; ?>
    <form action="<?= base_url('admin/login') ?>" method="post">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
