<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();
$db = get_db();

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newUsername     = trim($_POST['username'] ?? '');
    $newPassword     = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $stmt = $db->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($currentPassword, $hash)) {
        $flash = 'Mot de passe actuel incorrect.';
        $flashType = 'danger';
    } elseif ($newUsername === '') {
        $flash = "L'identifiant ne peut pas être vide.";
        $flashType = 'danger';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $flash = 'La confirmation du nouveau mot de passe ne correspond pas.';
        $flashType = 'danger';
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        $flash = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        $flashType = 'danger';
    } else {
        if ($newPassword !== '') {
            $stmt = $db->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?');
            $stmt->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $admin['id']]);
        } else {
            $stmt = $db->prepare('UPDATE admins SET username = ? WHERE id = ?');
            $stmt->execute([$newUsername, $admin['id']]);
        }
        $flash = 'Compte mis à jour.';
        $admin['username'] = $newUsername;
    }
}

$pageTitle = 'Mon compte';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Mon compte</h1>
<?php if ($flash): ?><div class="alert alert-<?= h($flashType) ?> py-2 small"><?= h($flash) ?></div><?php endif; ?>

<div class="card p-4" style="max-width:480px;">
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label small">Identifiant</label>
      <input type="text" name="username" class="form-control" value="<?= h($admin['username']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label small">Mot de passe actuel</label>
      <input type="password" name="current_password" class="form-control" required>
    </div>
    <hr>
    <p class="small text-secondary">Laissez les champs ci-dessous vides pour conserver le mot de passe actuel.</p>
    <div class="mb-3">
      <label class="form-label small">Nouveau mot de passe (8 caractères minimum)</label>
      <input type="password" name="new_password" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label small">Confirmer le nouveau mot de passe</label>
      <input type="password" name="confirm_password" class="form-control">
    </div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
  </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
