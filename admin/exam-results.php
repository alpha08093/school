<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_headline') {
        $stmt = $db->prepare('UPDATE exam_headline SET label = ?, rate = ?, decimals = ? WHERE skey = ?');
        foreach (['bepc', 'bac', 'general'] as $key) {
            $label    = trim($_POST["label_$key"] ?? '');
            $rate     = (float) str_replace(',', '.', $_POST["rate_$key"] ?? '0');
            $decimals = ((float) $rate === round((float) $rate)) ? (int) ($_POST["decimals_$key"] ?? 2) : (int) ($_POST["decimals_$key"] ?? 2);
            $stmt->execute([$label, $rate, $decimals, $key]);
        }
        header('Location: exam-results.php?saved=1');
        exit;
    }

    if ($action === 'add_row') {
        $label = trim($_POST['label'] ?? '');
        $rate  = (float) str_replace(',', '.', $_POST['rate'] ?? '0');
        $decimals = (int) ($_POST['decimals'] ?? 2);
        if ($label !== '') {
            $maxPos = (int) $db->query('SELECT COALESCE(MAX(position), -1) FROM exam_rows')->fetchColumn();
            $stmt = $db->prepare('INSERT INTO exam_rows (label, rate, decimals, position) VALUES (?, ?, ?, ?)');
            $stmt->execute([$label, $rate, $decimals, $maxPos + 1]);
        }
        header('Location: exam-results.php?added=1');
        exit;
    }

    if ($action === 'update_row') {
        $id = (int) $_POST['id'];
        $label = trim($_POST['label'] ?? '');
        $rate  = (float) str_replace(',', '.', $_POST['rate'] ?? '0');
        $decimals = (int) ($_POST['decimals'] ?? 2);
        $stmt = $db->prepare('UPDATE exam_rows SET label = ?, rate = ?, decimals = ? WHERE id = ?');
        $stmt->execute([$label, $rate, $decimals, $id]);
        header('Location: exam-results.php?updated=1');
        exit;
    }

    if ($action === 'delete_row') {
        $db->prepare('DELETE FROM exam_rows WHERE id = ?')->execute([(int) $_POST['id']]);
        header('Location: exam-results.php?deleted=1');
        exit;
    }

    if ($action === 'move_row') {
        $id = (int) $_POST['id'];
        $dir = $_POST['dir'] === 'up' ? 'up' : 'down';
        $rows = $db->query('SELECT id, position FROM exam_rows ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $index = null;
        foreach ($rows as $i => $r) { if ((int) $r['id'] === $id) { $index = $i; break; } }
        $swapWith = $dir === 'up' ? $index - 1 : $index + 1;
        if ($index !== null && $swapWith >= 0 && $swapWith < count($rows)) {
            $a = $rows[$index]; $b = $rows[$swapWith];
            $stmt = $db->prepare('UPDATE exam_rows SET position = ? WHERE id = ?');
            $stmt->execute([$b['position'], $a['id']]);
            $stmt->execute([$a['position'], $b['id']]);
        }
        header('Location: exam-results.php');
        exit;
    }
}

$headline = [];
foreach ($db->query('SELECT * FROM exam_headline') as $row) {
    $headline[$row['skey']] = $row;
}
$rows = $db->query('SELECT * FROM exam_rows ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Résultats d'examens";
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Résultats d'examens</h1>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success py-2 small">Chiffres clés mis à jour.</div><?php endif; ?>
<?php if (!empty($_GET['added'])): ?><div class="alert alert-success py-2 small">Ligne ajoutée.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success py-2 small">Ligne mise à jour.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success py-2 small">Ligne supprimée.</div><?php endif; ?>

<div class="card p-4 mb-4">
  <h2 class="h6 mb-3">Chiffres clés (page d'accueil et page Résultats)</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="save_headline">
    <div class="row g-3">
      <?php foreach (['bepc' => 'BEPC', 'bac' => 'BAC', 'general' => 'Succès général'] as $key => $title): $h = $headline[$key]; ?>
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <div class="fw-semibold small mb-2"><?= h($title) ?></div>
            <label class="form-label small">Libellé affiché</label>
            <input type="text" name="label_<?= $key ?>" class="form-control mb-2" value="<?= h($h['label']) ?>">
            <label class="form-label small">Taux (%)</label>
            <input type="text" name="rate_<?= $key ?>" class="form-control mb-2" value="<?= h(rtrim(rtrim(number_format($h['rate'], 2, '.', ''), '0'), '.')) ?>">
            <label class="form-label small">Décimales affichées</label>
            <select name="decimals_<?= $key ?>" class="form-select">
              <option value="0" <?= $h['decimals'] == 0 ? 'selected' : '' ?>>0 (ex. 100%)</option>
              <option value="2" <?= $h['decimals'] == 2 ? 'selected' : '' ?>>2 (ex. 91,67%)</option>
            </select>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Enregistrer les chiffres clés</button>
  </form>
</div>

<div class="card p-4 mb-4">
  <h2 class="h6 mb-3">Ajouter une ligne (classe ou série)</h2>
  <form method="post" class="row g-2 align-items-end">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_row">
    <div class="col-md-5">
      <label class="form-label small">Classe / série</label>
      <input type="text" name="label" class="form-control" placeholder="Ex. Terminale C" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Taux (%)</label>
      <input type="text" name="rate" class="form-control" placeholder="Ex. 92.5" required>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Décimales</label>
      <select name="decimals" class="form-select">
        <option value="2">2</option>
        <option value="0">0</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit">Ajouter</button>
    </div>
  </form>
</div>

<div class="card p-4">
  <h2 class="h6 mb-3">Tableau détaillé (<?= count($rows) ?> lignes)</h2>
  <?php if (!$rows): ?>
    <p class="text-secondary small mb-0">Aucune ligne pour le moment.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th></th><th>Classe / série</th><th>Taux</th><th>Décimales</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td style="width:70px;">
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="move_row">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <input type="hidden" name="dir" value="up">
              <button class="btn btn-sm btn-outline-secondary" type="submit" <?= $i === 0 ? 'disabled' : '' ?>><i class="bi bi-arrow-up"></i></button>
            </form>
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="move_row">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <input type="hidden" name="dir" value="down">
              <button class="btn btn-sm btn-outline-secondary" type="submit" <?= $i === count($rows) - 1 ? 'disabled' : '' ?>><i class="bi bi-arrow-down"></i></button>
            </form>
          </td>
          <td>
            <form method="post" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="update_row">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <input type="text" name="label" class="form-control form-control-sm" value="<?= h($r['label']) ?>" style="max-width:200px;">
          </td>
          <td>
              <input type="text" name="rate" class="form-control form-control-sm" value="<?= h(rtrim(rtrim(number_format($r['rate'], 2, '.', ''), '0'), '.')) ?>" style="max-width:100px;">
          </td>
          <td>
              <select name="decimals" class="form-select form-select-sm" style="max-width:90px;">
                <option value="2" <?= $r['decimals'] == 2 ? 'selected' : '' ?>>2</option>
                <option value="0" <?= $r['decimals'] == 0 ? 'selected' : '' ?>>0</option>
              </select>
          </td>
          <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" type="submit">Enregistrer</button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette ligne ?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_row">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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
