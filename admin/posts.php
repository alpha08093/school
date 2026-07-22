<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();

$db = get_db();
$flash = null;
$flashType = 'success';

/* ----- Suppression ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int) $_POST['id'];
    $stmt = $db->prepare('SELECT image FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    if ($img = $stmt->fetchColumn()) {
        delete_uploaded_image($img);
    }
    $db->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    header('Location: posts.php?deleted=1');
    exit;
}

/* ----- Création / modification ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_check();
    $id       = (int) ($_POST['id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $category = in_array($_POST['category'] ?? '', ['evenement', 'actualite', 'resultat'], true) ? $_POST['category'] : 'actualite';
    $desc     = trim($_POST['description'] ?? '');
    $date     = $_POST['post_date'] ?? date('Y-m-d');
    $removeImg = !empty($_POST['remove_image']);

    if ($title === '' || $desc === '') {
        $flash = "Merci de renseigner un titre et une description.";
        $flashType = 'danger';
    } else {
        try {
            $newImage = handle_image_upload('image', 'posts');

            if ($id > 0) {
                $stmt = $db->prepare('SELECT image FROM posts WHERE id = ?');
                $stmt->execute([$id]);
                $existingImage = $stmt->fetchColumn();

                $imageToStore = $existingImage;
                if ($newImage !== null) {
                    delete_uploaded_image($existingImage);
                    $imageToStore = $newImage;
                } elseif ($removeImg) {
                    delete_uploaded_image($existingImage);
                    $imageToStore = null;
                }

                $stmt = $db->prepare('UPDATE posts SET category=?, title=?, description=?, image=?, post_date=?, updated_at=? WHERE id=?');
                $stmt->execute([$category, $title, $desc, $imageToStore, $date, date('c'), $id]);
                header('Location: posts.php?saved=1');
                exit;
            } else {
                $stmt = $db->prepare('INSERT INTO posts (category, title, description, image, post_date, created_at, updated_at) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$category, $title, $desc, $newImage, $date, date('c'), date('c')]);
                header('Location: posts.php?saved=1');
                exit;
            }
        } catch (Exception $e) {
            $flash = $e->getMessage();
            $flashType = 'danger';
        }
    }
}

/* ----- Chargement pour édition ----- */
$editPost = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editPost = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$posts = $db->query('SELECT * FROM posts ORDER BY post_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Actualités';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Actualités</h1>

<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success py-2 small">Actualité enregistrée.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success py-2 small">Actualité supprimée.</div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-<?= h($flashType) ?> py-2 small"><?= h($flash) ?></div><?php endif; ?>

<div class="card p-4 mb-4">
  <h2 class="h6 mb-3"><?= $editPost ? "Modifier l'actualité" : 'Publier une nouvelle actualité' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editPost['id'] ?? 0) ?>">

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small">Titre</label>
        <input type="text" name="title" class="form-control" required value="<?= h($editPost['title'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small">Catégorie</label>
        <select name="category" class="form-select">
          <?php foreach (['actualite' => 'Actualité', 'evenement' => 'Événement', 'resultat' => 'Résultat'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($editPost['category'] ?? 'actualite') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Date</label>
        <input type="date" name="post_date" class="form-control" value="<?= h($editPost['post_date'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label small">Description</label>
        <textarea name="description" class="form-control" rows="3" required><?= h($editPost['description'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label small">Photo (optionnelle)</label>
        <input type="file" name="image" accept="image/*" class="form-control">
        <?php if (!empty($editPost['image']) && image_url($editPost['image'])): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage" value="1">
            <label class="form-check-label small" for="removeImage">Supprimer la photo actuelle</label>
          </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($editPost['image']) && image_url($editPost['image'])): ?>
        <div class="col-md-6">
          <label class="form-label small d-block">Photo actuelle</label>
          <img src="../<?= h(image_url($editPost['image'])) ?>" class="thumb" style="max-width:220px;">
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary" type="submit"><?= $editPost ? 'Enregistrer les modifications' : 'Publier' ?></button>
      <?php if ($editPost): ?><a href="posts.php" class="btn btn-outline-secondary">Annuler</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card p-4">
  <h2 class="h6 mb-3">Actualités publiées (<?= count($posts) ?>)</h2>
  <?php if (!$posts): ?>
    <p class="text-secondary small mb-0">Aucune actualité pour le moment.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th></th><th>Titre</th><th>Catégorie</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($posts as $p): $url = image_url($p['image']); ?>
        <tr>
          <td style="width:64px;">
            <?php if ($url): ?>
              <img src="../<?= h($url) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:4px;">
            <?php else: ?>
              <div class="thumb-placeholder" style="width:56px;height:56px;font-size:1.2rem;"><i class="bi <?= h(category_icon($p['category'])) ?>"></i></div>
            <?php endif; ?>
          </td>
          <td><?= h($p['title']) ?></td>
          <td><span class="badge text-bg-light border"><?= h(category_label($p['category'])) ?></span></td>
          <td><?= h(format_date_fr($p['post_date'])) ?></td>
          <td class="text-end">
            <a href="posts.php?edit=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer définitivement cette actualité ?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
