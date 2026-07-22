<?php
/**
 * @var string $pageTitle
 * @var array  $admin
 */
$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active(string $file, string $current): string {
    return $file === $current ? 'active' : '';
}
$navItems = [
    ['index.php', 'bi-speedometer2', 'Tableau de bord'],
    ['posts.php', 'bi-megaphone', 'Actualités'],
    ['gallery.php', 'bi-images', 'Galerie photos'],
    ['exam-results.php', 'bi-graph-up', "Résultats d'examens"],
    ['admissions.php', 'bi-inbox', 'Préinscriptions'],
    ['settings.php', 'bi-sliders', 'Informations du site'],
    ['account.php', 'bi-person-lock', 'Mon compte'],
];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Administration') ?> — Collège Les Sauveurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-shell">

  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
      <span class="brand-text">Les Sauveurs</span>
      <button class="sidebar-toggle" data-sidebar-toggle aria-label="Fermer le menu"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="sidebar-section-label">Menu principal</div>
    <nav class="sidebar-nav">
      <?php foreach ($navItems as [$file, $icon, $label]): ?>
        <a class="sidebar-link <?= nav_active($file, $current) ?>" href="<?= h($file) ?>">
          <i class="bi <?= h($icon) ?>"></i><span><?= h($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a class="sidebar-link danger" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Déconnexion</span></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="topbar-menu-btn" data-sidebar-toggle aria-label="Ouvrir le menu"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <i class="bi bi-house-door"></i>
        <span><?= h($pageTitle ?? 'Administration') ?></span>
        <span class="badge-pill">ADMINISTRATEUR</span>
      </div>
      <div class="topbar-right">
        <a href="../index.php" target="_blank" class="topbar-link"><i class="bi bi-globe2"></i> Aller au site</a>
        <div class="topbar-user">
          <span class="user-avatar"><i class="bi bi-person-fill"></i></span>
          <span class="user-info"><strong><?= h($admin['username'] ?? '') ?></strong><small>Les Sauveurs</small></span>
        </div>
      </div>
    </header>
    <main class="admin-content">
