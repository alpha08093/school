<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();

$postCount     = (int) get_db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$galleryCount  = (int) get_db()->query("SELECT COUNT(*) FROM gallery WHERE image NOT LIKE '__placeholder__:%'")->fetchColumn();
$examRowCount  = (int) get_db()->query('SELECT COUNT(*) FROM exam_rows')->fetchColumn();
$newAdmissions = (int) get_db()->query("SELECT COUNT(*) FROM admissions WHERE status = 'nouveau'")->fetchColumn();
$totalAdmissions = (int) get_db()->query('SELECT COUNT(*) FROM admissions')->fetchColumn();

$days = ['Monday'=>'lundi','Tuesday'=>'mardi','Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi','Sunday'=>'dimanche'];
$months = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];
$today = date('l j F Y');
foreach ($days as $en => $fr) { $today = str_replace($en, $fr, $today); }
foreach ($months as $en => $fr) { $today = str_replace($en, $fr, $today); }

$pageTitle = 'Tableau de bord';
require __DIR__ . '/partials/header.php';
?>

<div class="welcome-row">
  <h1>Bonjour, <?= h($admin['username']) ?> 👋</h1>
  <span class="welcome-date"><?= h(ucfirst($today)) ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-teal"><i class="bi bi-megaphone"></i></span>
      <div><div class="stat-value"><?= $postCount ?></div><div class="stat-label">Actualités</div></div>
      <a href="posts.php" class="stat-link">Gérer →</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-purple"><i class="bi bi-images"></i></span>
      <div><div class="stat-value"><?= $galleryCount ?></div><div class="stat-label">Photos en ligne</div></div>
      <a href="gallery.php" class="stat-link">Gérer →</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-blue"><i class="bi bi-graph-up"></i></span>
      <div><div class="stat-value"><?= $examRowCount ?></div><div class="stat-label">Lignes de résultats</div></div>
      <a href="exam-results.php" class="stat-link">Gérer →</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-orange"><i class="bi bi-inbox"></i></span>
      <div><div class="stat-value"><?= $newAdmissions ?></div><div class="stat-label">Nouvelles préinscriptions</div></div>
      <a href="admissions.php" class="stat-link">Voir →</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-cyan"><i class="bi bi-people"></i></span>
      <div><div class="stat-value"><?= $totalAdmissions ?></div><div class="stat-label">Préinscriptions totales</div></div>
      <a href="admissions.php" class="stat-link">Voir →</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <span class="stat-icon icon-red"><i class="bi bi-sliders"></i></span>
      <div><div class="stat-value"><i class="bi bi-gear fs-6"></i></div><div class="stat-label">Informations du site</div></div>
      <a href="settings.php" class="stat-link">Modifier →</a>
    </div>
  </div>
</div>

<div class="card p-4">
  <h2 class="h6">Comment ça marche</h2>
  <ul class="small text-secondary mb-0">
    <li><strong>Actualités</strong> : publiez, modifiez ou supprimez les news du site (avec une photo si vous le souhaitez).</li>
    <li><strong>Galerie photos</strong> : ajoutez autant de photos que nécessaire, remplacez ou supprimez celles déjà en ligne.</li>
    <li><strong>Résultats d'examens</strong> : mettez à jour les chiffres clés et le tableau détaillé par classe et série.</li>
    <li><strong>Préinscriptions</strong> : suivez les demandes envoyées depuis le site public et leur statut.</li>
    <li><strong>Informations du site</strong> : coordonnées, bandeau d'annonce, horaires, etc.</li>
    <li>Toutes les modifications apparaissent immédiatement sur le site public dès l'enregistrement.</li>
  </ul>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
