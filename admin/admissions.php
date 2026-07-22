<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'mark_status') {
        $status = in_array($_POST['status'] ?? '', ['nouveau', 'contacte', 'inscrit', 'classe'], true) ? $_POST['status'] : 'nouveau';
        $db->prepare('UPDATE admissions SET status = ? WHERE id = ?')->execute([$status, $id]);
        header('Location: admissions.php?updated=1');
        exit;
    }

    if ($action === 'delete') {
        $db->prepare('DELETE FROM admissions WHERE id = ?')->execute([$id]);
        header('Location: admissions.php?deleted=1');
        exit;
    }
}

$submissions = $db->query('SELECT * FROM admissions ORDER BY created_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'nouveau'  => ['Nouvelle', 'text-bg-primary'],
    'contacte' => ['Contacté', 'text-bg-warning'],
    'inscrit'  => ['Inscrit', 'text-bg-success'],
    'classe'   => ['Classé sans suite', 'text-bg-secondary'],
];

$pageTitle = 'Préinscriptions';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Demandes de préinscription</h1>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success py-2 small">Statut mis à jour.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success py-2 small">Demande supprimée.</div><?php endif; ?>

<div class="card p-4">
  <p class="text-secondary small">Ces demandes proviennent du formulaire « Nous contacter » de la page Admission du site public.</p>
  <?php if (!$submissions): ?>
    <p class="text-secondary fst-italic mb-0">Aucune demande de préinscription pour le moment.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Reçu le</th><th>Élève</th><th>Classe visée</th><th>Parent / tuteur</th><th>Téléphone</th><th>Message</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($submissions as $s): $st = $statusLabels[$s['status']] ?? $statusLabels['nouveau']; ?>
        <tr>
          <td class="small text-secondary"><?= h(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
          <td><?= h($s['student_name']) ?></td>
          <td><?= h($s['target_class']) ?></td>
          <td><?= h($s['guardian_name']) ?></td>
          <td><a href="tel:<?= h(preg_replace('/\s+/', '', $s['phone'])) ?>"><?= h($s['phone']) ?></a></td>
          <td class="small" style="max-width:220px;"><?= nl2br(h($s['message'] ?: '—')) ?></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="mark_status">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $key => $meta): ?>
                  <option value="<?= h($key) ?>" <?= $s['status'] === $key ? 'selected' : '' ?>><?= h($meta[0]) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td>
            <form method="post" onsubmit="return confirm('Supprimer cette demande ?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
