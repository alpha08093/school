<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();

$fields = [
    'announce_bar'       => ['label' => "Texte du bandeau d'annonce (en haut du site)", 'type' => 'text'],
    'ticker_items'       => ['label' => 'Bandeau défilant (une ligne par message)', 'type' => 'textarea'],
    'enrollment_note'    => ['label' => "Note d'inscription (page Admission)", 'type' => 'textarea'],
    'class_hours'        => ['label' => 'Horaires de cours', 'type' => 'textarea'],
    'office_hours'       => ['label' => 'Horaires du secrétariat', 'type' => 'text'],
    'address'            => ['label' => 'Adresse', 'type' => 'textarea'],
    'phone'              => ['label' => 'Téléphone (texte affiché)', 'type' => 'text'],
    'phone_tel_link'     => ['label' => "Téléphone (lien d'appel, ex. +2290140531494)", 'type' => 'text'],
    'whatsapp_display'   => ['label' => 'WhatsApp (texte affiché)', 'type' => 'text'],
    'whatsapp_number'    => ['label' => 'WhatsApp (numéro complet sans + ni espaces, ex. 2290197134344)', 'type' => 'text'],
    'footer_copyright'   => ['label' => 'Ligne de copyright (bas de page)', 'type' => 'text'],
];

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $key => $meta) {
        set_setting($key, trim($_POST[$key] ?? ''));
    }
    header('Location: settings.php?saved=1');
    exit;
}

$pageTitle = 'Informations du site';
require __DIR__ . '/partials/header.php';
?>

<h1 class="h4 mb-4">Informations du site</h1>
<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success py-2 small">Informations mises à jour.</div><?php endif; ?>

<div class="card p-4">
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="row g-3">
      <?php foreach ($fields as $key => $meta): $value = get_setting($key); ?>
        <div class="col-md-6">
          <label class="form-label small"><?= h($meta['label']) ?></label>
          <?php if ($meta['type'] === 'textarea'): ?>
            <textarea name="<?= h($key) ?>" class="form-control" rows="3"><?= h($value) ?></textarea>
          <?php else: ?>
            <input type="text" name="<?= h($key) ?>" class="form-control" value="<?= h($value) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-4" type="submit">Enregistrer</button>
  </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
