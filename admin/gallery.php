<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();

$db = get_db();
$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $caption = trim($_POST['caption'] ?? '');
        try {
            $image = handle_image_upload('image', 'gallery');
            if ($image === null) {
                throw new Exception('Merci de choisir une photo à ajouter.');
            }
            $maxPos = (int) $db->query('SELECT COALESCE(MAX(position), -1) FROM gallery')->fetchColumn();
            $stmt = $db->prepare('INSERT INTO gallery (caption, image, position, created_at) VALUES (?,?,?,?)');
            $stmt->execute([$caption, $image, $maxPos + 1, date('c')]);
            header('Location: gallery.php?added=1');
            exit;
        } catch (Exception $e) {
            $flash = $e->getMessage();
            $flashType = 'danger';
        }
    }

    if ($action === 'replace') {
        $id = (int) $_POST['id'];
        try {
            $newImage = handle_image_upload('image', 'gallery');
            if ($newImage === null) {
                throw new Exception('Merci de choisir une photo de remplacement.');
            }
            $stmt = $db->prepare('SELECT image FROM gallery WHERE id = ?');
            $stmt->execute([$id]);
            if ($old = $stmt->fetchColumn()) {
                delete_uploaded_image($old);
            }
            $db->prepare('UPDATE gallery SET image = ? WHERE id = ?')->execute([$newImage, $id]);
            header('Location: gallery.php?replaced=1');
            exit;
        } catch (Exception $e) {
            $flash = $e->getMessage();
            $flashType = 'danger';
        }
    }

    if ($action === 'caption') {
        $id = (int) $_POST['id'];
        $caption = trim($_POST['caption'] ?? '');
        $db->prepare('UPDATE gallery SET caption = ? WHERE id = ?')->execute([$caption, $id]);
        header('Location: gallery.php?updated=1');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $db->prepare('SELECT image FROM gallery WHERE id = ?');
        $stmt->execute([$id]);
        if ($img = $stmt->fetchColumn()) {
            delete_uploaded_image($img);
        }
        $db->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
        header('Location: gallery.php?deleted=1');
        exit;
    }
}

$items = $db->query('SELECT * FROM gallery ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Galerie photos';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Galerie photos</h1>

<?php if (!empty($_GET['added'])): ?><div class="alert alert-success py-2 small">Photo ajoutée.</div><?php endif; ?>
<?php if (!empty($_GET['replaced'])): ?><div class="alert alert-success py-2 small">Photo remplacée.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success py-2 small">Légende mise à jour.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success py-2 small">Photo supprimée.</div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-<?= h($flashType) ?> py-2 small"><?= h($flash) ?></div><?php endif; ?>

<div class="card p-4 mb-4">
  <h2 class="h6 mb-3">Ajouter une photo</h2>
  <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="col-md-5">
      <label class="form-label small">Photo</label>
      <input type="file" name="image" accept="image/*" class="form-control" required>
    </div>
    <div class="col-md-5">
      <label class="form-label small">Légende (optionnelle)</label>
      <input type="text" name="caption" class="form-control" placeholder="Ex. Cour de récréation">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit">Ajouter</button>
    </div>
  </form>
  <p class="small text-secondary mt-2 mb-0">Aucune limite : ajoutez autant de photos que nécessaire.</p>
</div>

<div class="row g-3">
  <?php foreach ($items as $g): $url = image_url($g['image']); ?>
  <div class="col-md-4 col-lg-3">
    <div class="card p-2 h-100">
      <?php if ($url): ?>
        <img src="../<?= h($url) ?>" class="thumb mb-2">
      <?php else: ?>
        <div class="thumb-placeholder mb-2"><i class="bi <?= h(placeholder_icon($g['image'])) ?>"></i></div>
      <?php endif; ?>

      <form method="post" class="d-flex gap-1 mb-2">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="caption">
        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
        <input type="text" name="caption" class="form-control form-control-sm" value="<?= h($g['caption']) ?>" placeholder="Légende">
        <button class="btn btn-sm btn-outline-secondary" type="submit" title="Enregistrer la légende"><i class="bi bi-check2"></i></button>
      </form>

      <div class="d-flex gap-1">
        <form method="post" enctype="multipart/form-data" class="flex-fill">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="replace">
          <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
          <label class="btn btn-sm btn-outline-secondary w-100 mb-0">
            Remplacer
            <input type="file" name="image" accept="image/*" class="d-none" onchange="this.form.submit()">
          </label>
        </form>
        <form method="post" onsubmit="return confirm('Supprimer définitivement cette photo ?');">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
