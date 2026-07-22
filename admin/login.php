<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_admin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = "Identifiants incorrects.";
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — Administration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#0E2A47;min-height:100vh;display:flex;align-items:center;font-family:system-ui,sans-serif;}
  .login-card{max-width:380px;margin:auto;}
</style>
</head>
<body>
<div class="container">
  <div class="card login-card shadow-lg border-0">
    <div class="card-body p-4">
      <h1 class="h5 mb-1">Collège Privé Les Sauveurs</h1>
      <p class="text-secondary small mb-4">Espace administration</p>
      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <div class="mb-3">
          <label class="form-label small">Identifiant</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label small">Mot de passe</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Se connecter</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
