<?php
require_once __DIR__ . '/includes/bootstrap.php';

$db = get_db();

$posts = $db->query('SELECT * FROM posts ORDER BY post_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
$postsForJs = array_map(function ($p) {
    return [
        'title'      => $p['title'],
        'desc'       => $p['description'],
        'date_label' => format_date_fr($p['post_date']),
        'date_iso'   => $p['post_date'],
        'cat_bg'     => 'cat-' . $p['category'],
        'tag_class'  => category_class($p['category']),
        'label'      => category_label($p['category']),
        'icon'       => category_icon($p['category']),
        'image_url'  => image_url($p['image']),
    ];
}, $posts);

$galleryItems = $db->query('SELECT * FROM gallery ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$tickerLines = array_values(array_filter(array_map('trim', explode("\n", get_setting('ticker_items')))));
$whatsappNumber = preg_replace('/\D+/', '', get_setting('whatsapp_number', '2290197134344'));
$whatsappMessage = rawurlencode("Bonjour, je souhaite avoir des informations sur le Collège Privé Les Sauveurs.");
$whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}";
$whatsappDisplay = get_setting('whatsapp_display', '01 97 13 43 44');
$phoneDisplay = get_setting('phone', '01 40 53 14 94');
$phoneTelLink = get_setting('phone_tel_link', '+2290140531494');

$examHeadline = [];
foreach ($db->query('SELECT * FROM exam_headline') as $row) {
    $examHeadline[$row['skey']] = $row;
}
$examRows = $db->query('SELECT * FROM exam_rows ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
// Découpage en 2 colonnes pour l'affichage du tableau (purement visuel)
$examRowsHalf = (int) ceil(count($examRows) / 2);
$examCol1 = array_slice($examRows, 0, $examRowsHalf);
$examCol2 = array_slice($examRows, $examRowsHalf);

function fmt_rate(float $rate, int $decimals): string {
    return number_format($rate, $decimals, ',', '');
}

$mapAddress = get_setting('address');
$mapLat = 6.379007;
$mapLng = 2.4595451;
$mapEmbedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox='
    . ($mapLng - 0.005) . '%2C' . ($mapLat - 0.005) . '%2C' . ($mapLng + 0.005) . '%2C' . ($mapLat + 0.005)
    . '&layer=mapnik&marker=' . $mapLat . '%2C' . $mapLng;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collège Privé Les Sauveurs — Cotonou, Bénin</title>
<meta name="description" content="Collège Privé Les Sauveurs — enseignement général et commercial de la 6ème à la Terminale, à Cotonou, Bénin.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Work+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="has-announce is-loading" id="pageBody">

<!-- ===== PRELOADER ===== -->
<div id="preloader" aria-hidden="true">
  <div class="pl-logo"><img class="brand-logo-img" src="" alt="Logo Collège Privé Les Sauveurs"></div>
  <div class="pl-brand">Collège Privé Les Sauveurs</div>
  <div class="pl-bar"></div>
</div>

<div class="site-wrap">

<!-- ===== BANDEAU D'ANNONCE ===== -->
<div class="announce-bar" id="announceBar">
  🎓 <b><?= h(get_setting('announce_bar')) ?></b> — <a href="#" onclick="showPage('admission');return false;">Faire une préinscription →</a>
  <button class="announce-close" id="announceClose" aria-label="Fermer">✕</button>
</div>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="#" onclick="showPage('accueil');return false;">
      <img class="brand-logo-img" src="" alt="Logo Collège Privé Les Sauveurs" width="40" height="40" style="border-radius:50%;object-fit:cover;border:2px solid #5AC0EA;animation:spin 18s linear infinite;">
      <span class="b-text"><strong>Les Sauveurs</strong><span>Collège Privé · Cotonou</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center" id="navList">
        <li class="nav-item"><a class="nav-link" data-page="accueil" onclick="showPage('accueil');return false;" href="#">Accueil</a></li>
        <li class="nav-item"><a class="nav-link" data-page="apropos" onclick="showPage('apropos');return false;" href="#">Le collège</a></li>
        <li class="nav-item"><a class="nav-link" data-page="classes" onclick="showPage('classes');return false;" href="#">Nos classes</a></li>
        <li class="nav-item"><a class="nav-link" data-page="resultats" onclick="showPage('resultats');return false;" href="#">Résultats</a></li>
        <li class="nav-item"><a class="nav-link" data-page="actualites" onclick="showPage('actualites');return false;" href="#">Actualités</a></li>
        <li class="nav-item"><a class="nav-link" data-page="admission" onclick="showPage('admission');return false;" href="#">Admission</a></li>
        <li class="nav-item"><a class="nav-link" data-page="faq" onclick="showPage('faq');return false;" href="#">FAQ</a></li>
        <li class="nav-item mt-2 mt-lg-0"><a class="espace-btn" href="admin/login.php" target="_blank">Espace École</a></li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0 util-controls">
          <select class="lang-select" id="langSelect" aria-label="Choisir la langue">
            <option value="fr">🇫🇷 FR</option>
            <option value="en">🇬🇧 EN</option>
          </select>
          <button class="theme-toggle" id="themeToggle" type="button" aria-label="Changer de thème" title="Mode clair / sombre">🌙</button>
        </li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn btn-ciel btn-sm px-3" href="#" onclick="showPage('admission');return false;">S'inscrire</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ===================================================== -->
<!-- PAGE : ACCUEIL -->
<!-- ===================================================== -->
<div class="page-section active" id="page-accueil">
  <section class="hero has-bg" id="heroSection">
    <div class="container position-relative">
      <div class="row align-items-center gy-5">
        <div class="col-lg-7">
          <div class="eyebrow text-ciel">Collège Privé Les Sauveurs — Cotonou, Bénin</div>
          <span class="devise-badge">✦ Crainte de Dieu · Sagesse · Intelligence</span>
          <h1>Une <em>tradition d'excellence</em>, de la 6ème à la Terminale A,B,C et D.</h1>
          <p class="lead mb-4">Enseignement général et commercial, encadrement rigoureux et valeurs solides : le Collège Privé Les Sauveurs prépare chaque élève à réussir ses examens et sa vie d'adulte.</p>
          <div class="d-flex flex-wrap gap-3 mb-5">
            <a href="#" onclick="showPage('admission');return false;" class="btn btn-ciel px-4 py-2">Faire une préinscription</a>
            <a href="#" onclick="showPage('resultats');return false;" class="btn btn-outline-ciel px-4 py-2">Voir nos résultats</a>
          </div>
          <div class="hero-stat" style="border-left:2px solid var(--ciel);padding-left:18px;">
            <b style="font-size:2rem;"><span class="counter" data-target="<?= h((string) $examHeadline['general']['rate']) ?>" data-decimals="<?= (int) $examHeadline['general']['decimals'] ?>">0%</span></b><span>de réussite générale sur l'année 2025–2026</span>
          </div>
        </div>
        <div class="col-lg-5 text-center">
          <div class="hero-logo-frame">
            <img class="hero-logo-img" src="" alt="Logo Collège Privé Les Sauveurs">
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="ticker-band"><div class="ticker-track" id="tickerTrack"></div></div>

  <section class="inner">
    <div class="container">
      <div class="eyebrow">Pourquoi nous choisir</div>
      <h2 class="mt-2 mb-4">L'essentiel du Collège Les Sauveurs</h2>
      <div class="row g-4">
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><div class="icon-badge mb-3"><i class="bi bi-mortarboard"></i></div><h4 class="fs-6">Enseignants qualifiés</h4><p class="text-secondary small mb-0">Un corps professoral expérimenté et suivi tout au long de l'année.</p></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><div class="icon-badge mb-3"><i class="bi bi-person-check"></i></div><h4 class="fs-6">Suivi personnalisé</h4><p class="text-secondary small mb-0">Carte de suivi de l'écolage et des notes, dialogue régulier avec les parents, Forum d'informations des parents.</p></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><div class="icon-badge mb-3"><i class="bi bi-building"></i></div><h4 class="fs-6">Infrastructures adaptées</h4><p class="text-secondary small mb-0">Salles carrelées et bien aérées, tableaux à marqueur.</p></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><div class="icon-badge mb-3"><i class="bi bi-award"></i></div><h4 class="fs-6">Préparation aux examens</h4><p class="text-secondary small mb-0">Cours de renforcement en 4e, 3e, 1ères et Tles et examens blancs en 3e et Tles.</p></div></div>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : LE COLLEGE -->
<!-- ===================================================== -->
<div class="page-section" id="page-apropos">
  <section class="inner bg-ciel-pale">
    <div class="container">
      <div class="row gy-5">
        <div class="col-lg-6">
          <div class="eyebrow">Le collège</div>
          <h2 class="mt-2 mb-3">Une communauté éducative tournée vers l'excellence</h2>
          <p class="text-secondary">Le Collège Privé Les Sauveurs accueille les élèves de la 6ème à la Terminale A,B,C,D en enseignement général et commercial, dans un environnement structuré où l'exigence académique va de pair avec l'attention portée à chaque enfant.</p>

          <div class="devise-block my-4">
            <div class="eyebrow text-ciel">Notre devise</div>
            <div class="mots mt-2"><span>Crainte de Dieu</span><span>Sagesse</span><span>Intelligence</span></div>
          </div>
          <div class="devise-block my-4">
            <div class="eyebrow text-ciel">Notre idéologie</div>
            <p class="ideo">" Le meilleur héritage, c'est un enfant bien éduqué, avec de très bons diplômes. "</p>
          </div>

          <div class="row g-3">
            <div class="col-12 d-flex gap-3">
              <span class="mono fw-bold" style="color:var(--bleu);">01</span>
              <div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Rigueur académique</strong><span class="text-secondary small">Un programme conforme aux exigences du système éducatif béninois, du 1er au 2e cycle.</span></div>
            </div>
            <div class="col-12 d-flex gap-3">
              <span class="mono fw-bold" style="color:var(--bleu);">02</span>
              <div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Éducation intégrale</strong><span class="text-secondary small">Formation académique et valeurs ; des cours de catéchisme preparant au baptême, à la communion et à la confirmation sont proposés au sein de l'établissement pour les chretiens catholiques.</span></div>
            </div>
            <div class="col-12 d-flex gap-3">
              <span class="mono fw-bold" style="color:var(--bleu);">03</span>
              <div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Discipline et cadre de vie</strong><span class="text-secondary small">Salles carrelées et bien aérées, règlement clair, suivi de proximité avec les familles.</span></div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="row g-2">
            <div class="col-6"><div class="stat-cell"><b><span class="counter" data-target="<?= h((string) $examHeadline['bepc']['rate']) ?>" data-decimals="<?= (int) $examHeadline['bepc']['decimals'] ?>">0%</span></b><span><?= h($examHeadline['bepc']['label']) ?></span></div></div>
            <div class="col-6"><div class="stat-cell"><b><span class="counter" data-target="<?= h((string) $examHeadline['bac']['rate']) ?>" data-decimals="<?= (int) $examHeadline['bac']['decimals'] ?>">0%</span></b><span><?= h($examHeadline['bac']['label']) ?></span></div></div>
            <div class="col-6"><div class="stat-cell"><b><span class="counter" data-target="<?= h((string) $examHeadline['general']['rate']) ?>" data-decimals="<?= (int) $examHeadline['general']['decimals'] ?>">0%</span></b><span>SUCCÈS GÉNÉRAL</span></div></div>
            <div class="col-6"><div class="stat-cell"><b><span class="counter" data-target="100" data-decimals="0">0%</span></b><span>SÉRIE D — BAC</span></div></div>
          </div>
          <div class="card-soft p-4 mt-3">
            <div class="eyebrow">Nos avantages</div>
            <p class="text-secondary mb-0 mt-2">Une bonne éducation garantie à nos apprenants, dans des salles carrelées et bien aérées, équipées de tableaux à marqueur.</p>
          </div>
          <div class="card-soft p-4 mt-3">
            <div class="eyebrow">Accompagnement spirituel</div>
            <p class="text-secondary mb-0 mt-2">Des cours de catéchisme préparation au baptême à la communion et à la confirmation sont proposés au sein de l'établissement pour les chretiens catholiques.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : NOS CLASSES -->
<!-- ===================================================== -->
<div class="page-section" id="page-classes">
  <section class="inner">
    <div class="container">
      <div class="eyebrow">Enseignement général et commercial</div>
      <h2 class="mt-2 mb-3">Nos classes, de la 6ème à la Terminale</h2>
      <p class="text-secondary mb-4" style="max-width:640px;">Un parcours complet en deux cycles, de la 6ème à la Terminale, avec les séries A2, B, C et D.</p>

      <div class="d-flex gap-2 mb-4">
        <button class="btn-pill-outline active" id="btnCollege" onclick="showCycle('college')">Premier cycle (6e–3e)</button>
        <button class="btn-pill-outline" id="btnLycee" onclick="showCycle('lycee')">Second cycle (2nde–Tle)</button>
      </div>

      <div id="cycleCollege" class="row g-3">
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><span class="eyebrow">1er cycle</span><h3 class="fs-4 mt-2">6ème</h3></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><span class="eyebrow">1er cycle</span><h3 class="fs-4 mt-2">5ème</h3></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><span class="eyebrow">1er cycle</span><h3 class="fs-4 mt-2">4ème</h3></div></div>
        <div class="col-6 col-md-3"><div class="card-soft p-3 h-100"><span class="eyebrow">1er cycle</span><h3 class="fs-4 mt-2">3ème</h3></div></div>
      </div>

      <div id="cycleLycee" class="row g-3" style="display:none;">
        <div class="col-12 col-md-4"><div class="card-soft p-3 h-100"><span class="eyebrow">Séries A2 · B · C · D</span><h3 class="fs-4 mt-2">2nde</h3></div></div>
        <div class="col-12 col-md-4"><div class="card-soft p-3 h-100"><span class="eyebrow">Séries A2 · B · C · D</span><h3 class="fs-4 mt-2">1ère</h3></div></div>
        <div class="col-12 col-md-4"><div class="card-soft p-3 h-100"><span class="eyebrow">Séries A2 · B · C · D</span><h3 class="fs-4 mt-2">Terminale</h3></div></div>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : RESULTATS -->
<!-- ===================================================== -->
<div class="page-section" id="page-resultats">
  <section class="inner resultats">
    <div class="container">
      <div class="eyebrow text-ciel">Nos résultats parlent pour nous</div>
      <h2 class="mt-2 mb-3">Résultats aux examens 2025–2026</h2>
      <p style="max-width:640px;">Année après année, le Collège Les Sauveurs affiche des taux de réussite parmi les plus élevés de ses classes d'examen.</p>

      <div class="row g-3 mb-4 mt-2">
        <?php foreach (['bepc', 'bac', 'general'] as $key): $h = $examHeadline[$key]; ?>
        <div class="col-md-4"><div class="head-stat"><b><span class="counter" data-target="<?= h((string) $h['rate']) ?>" data-decimals="<?= (int) $h['decimals'] ?>">0%</span></b><span><?= h($h['label']) ?></span></div></div>
        <?php endforeach; ?>
      </div>

      <div class="table-responsive mb-4" style="border:1px solid rgba(90,192,234,.25);border-radius:6px;">
        <table class="table resu mb-0 align-middle">
          <thead><tr><th>Classe</th><th>Taux</th><th>Classe</th><th>Taux</th></tr></thead>
          <tbody>
            <?php for ($i = 0; $i < max(count($examCol1), count($examCol2)); $i++): $a = $examCol1[$i] ?? null; $b = $examCol2[$i] ?? null; ?>
            <tr>
              <td><?= $a ? h($a['label']) : '' ?></td>
              <td><?= $a ? '<span class="counter" data-target="'.h((string)$a['rate']).'" data-decimals="'.(int)$a['decimals'].'">0%</span>' : '' ?></td>
              <td><?= $b ? h($b['label']) : '' ?></td>
              <td><?= $b ? '<span class="counter" data-target="'.h((string)$b['rate']).'" data-decimals="'.(int)$b['decimals'].'">0%</span>' : '' ?></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="distinction-card h-100">
            <img class="trophy-img" src="" alt="Trophée Africa Excellence Awards" style="width:100%;max-width:160px;display:block;margin:0 auto 14px;border-radius:4px;">
            <div class="d-flex gap-3"><i class="bi bi-trophy"></i><div><h4 class="fs-6 text-white">Trophée Africa Excellence Awards</h4><p class="mb-0">Prix de l'Excellence décerné au collège lors de la 11ème édition (22 mai 2025), par l'Association Les Ambassadeurs du Développement.</p></div></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="distinction-card h-100">
            <img class="lettre-img" src="" alt="Lettre de félicitations du Ministère" style="width:100%;max-width:260px;display:block;margin:0 auto 14px;border-radius:4px;box-shadow:0 6px 18px rgba(0,0,0,.35);">
            <div class="d-flex gap-3"><i class="bi bi-envelope-paper"></i><div><h4 class="fs-6 text-white">Lettre de félicitations du Ministère</h4><p class="mb-0">Adressée au collège par le Ministère des Enseignements Secondaire, Technique et de la Formation Professionnelle, pour ses résultats BEPC et BAC 2024–2025.</p></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : ACTUALITES -->
<!-- ===================================================== -->
<div class="page-section" id="page-actualites">
  <section class="inner bg-ciel-pale">
    <div class="container">
      <div class="eyebrow">Vie de l'établissement</div>
      <h2 class="mt-2 mb-3">Actualités &amp; événements</h2>
      <p class="text-secondary mb-4" style="max-width:640px;">Résultats d'examens, cours de vacances, inscriptions et distinctions : suivez ici l'actualité du collège.</p>

      <div class="row g-3" id="postGrid">
        <?php if (!$posts): ?>
          <p class="text-secondary fst-italic">Aucune actualité publiée pour le moment.</p>
        <?php else: foreach ($postsForJs as $i => $p): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card-soft post-card h-100 d-flex flex-column">
              <div class="post-img <?= h($p['cat_bg']) ?><?= $p['image_url'] ? ' has-photo' : '' ?>"<?php if ($p['image_url']): ?> style="background-image:url('<?= h($p['image_url']) ?>')"<?php endif; ?>>
                <i class="bi <?= h($p['icon']) ?> post-icon"></i>
                <div class="post-caption"><?= h($p['title']) ?></div>
              </div>
              <div class="p-3 d-flex flex-column gap-2 flex-grow-1">
                <span class="tag <?= h($p['tag_class']) ?> align-self-start"><?= h($p['label']) ?></span>
                <span class="post-date mono" data-date-iso="<?= h($p['date_iso']) ?>"><?= h($p['date_label']) ?></span>
                <p class="text-secondary small flex-grow-1 mb-0"><?= h(truncate_text($p['desc'], 90)) ?></p>
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <button class="voir-plus" onclick="openPostModal(<?= (int) $i ?>)">Voir plus →</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : ADMISSION (+ vie scolaire + témoignages) -->
<!-- ===================================================== -->
<div class="page-section" id="page-admission">
  <section class="inner">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-6">
          <div class="eyebrow">Inscriptions</div>
          <h2 class="mt-2 mb-3">Comment inscrire votre enfant</h2>
          <div class="p-3 mb-4" style="background:var(--ciel-pale);border:1px solid var(--ligne);border-radius:6px;">
            <b class="text-marine">Rentrée 2026–2027 :</b> <span class="text-secondary small">inscriptions et réinscriptions ouvertes depuis le 1er juin 2026. Étude de dossier pour tout nouvel élève.</span>
          </div>
          <ul class="list-unstyled etapes">
            <li><span class="step-n">1</span><div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Préinscription</strong><span class="text-secondary small">Contactez le secrétariat ou remplissez le formulaire ci-contre.</span></div></li>
            <li><span class="step-n">2</span><div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Constitution du dossier</strong><span class="text-secondary small">2 photocopies d'acte de naissance sécurisé, bulletins de passage ou de redoublement, relevé du CEP (6ème) ou du BEPC (2ndes).</span></div></li>
            <li><span class="step-n">3</span><div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Dossier d'examen (rentrée)</strong><span class="text-secondary small">Extrait d'acte de naissance sécurisé, attestation du BEPC légalisée, copie CIN (candidats au BAC).</span></div></li>
            <li><span class="step-n">4</span><div><strong class="d-block text-marine" style="font-family:'Fraunces',serif;">Confirmation</strong><span class="text-secondary small">Validation de l'inscription et remise du calendrier de la rentrée.</span></div></li>
          </ul>
        </div>

        <div class="col-lg-6">
          <div class="contact-card" id="contact">
            <h3 class="text-white fs-4">Nous contacter</h3>
            <ul class="list-unstyled contact-list mt-3">
              <li>
  <a href="https://www.google.com/maps/search/?api=1&query=6.379007,2.4595451" target="_blank" rel="noopener" style="color:#C9DCEB;">
    <i class="bi bi-geo-alt"></i> <?= h(get_setting('address')) ?>
  </a>
</li>
              <li><i class="bi bi-whatsapp"></i> <a href="<?= h($whatsappUrl) ?>" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp" style="color:#C9DCEB;"><span><?= h($whatsappDisplay) ?></span> (WhatsApp)</a></li>
              <li><i class="bi bi-telephone"></i> <a href="tel:<?= h($phoneTelLink) ?>" style="color:#C9DCEB;"><?= h($phoneDisplay) ?> (Appel direct)</a></li>
              <li><i class="bi bi-clock"></i> Secrétariat ouvert : <span><?= h(get_setting('office_hours')) ?></span></li>
            </ul>
            <form id="contactForm" class="d-grid gap-2">
              <input type="text" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" name="website" aria-hidden="true">
              <input type="text" name="student_name" class="form-control" placeholder="Nom complet de l'élève" required>
              <select name="target_class" class="form-select" required>
                <option value="" disabled selected>Classe visée</option>
                <optgroup label="Collège"><option>6ème</option><option>5ème</option><option>4ème</option><option>3ème</option></optgroup>
                <optgroup label="2nde – Terminale"><option>2nde</option><option>1ère</option><option>Terminale</option></optgroup>
              </select>
              <input type="text" name="guardian_name" class="form-control" placeholder="Nom du parent / tuteur" required>
              <input type="tel" name="phone" class="form-control" placeholder="Téléphone" required>
              <textarea name="message" class="form-control" rows="3" placeholder="Message complémentaire (optionnel)"></textarea>
              <button type="submit" class="btn btn-ciel">Envoyer la préinscription</button>
              <span class="small mono d-none" id="formMsg"></span>
            </form>
            <p class="small mt-2" style="color:#9FB4C6;">Votre demande est transmise directement au secrétariat, qui vous recontactera pour finaliser l'inscription.</p>
          </div>
        </div>
      </div>

      <!-- Vie scolaire -->
      <div class="mt-5 pt-5">
        <div class="eyebrow">Au quotidien</div>
        <h2 class="mt-2 mb-4">La vie scolaire aux Sauveurs</h2>
        <img class="classroom-img" src="" alt="Salle de classe" style="width:100%;max-height:340px;object-fit:cover;border-radius:6px;margin-bottom:24px;">
        <div class="row g-3">
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-clock fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Horaires de cours</h4><p class="text-secondary small mb-0"><?= h(get_setting('class_hours')) ?></p></div></div>
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-cup-hot fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Séjour &amp; cantine</h4><p class="text-secondary small mb-0">Options facultatives et prépayées, les lundi, mardi, jeudi et vendredi.</p></div></div>
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-journal-bookmark fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Cours de renforcement</h4><p class="text-secondary small mb-0">13 juillet–21 août : 4e, 3e/1ères, Terminales.</p></div></div>
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-sun fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Cours de vacances</h4><p class="text-secondary small mb-0">27 juillet–21 août : 6e et 5e/2ndes.</p></div></div>
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-vest fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Tenue &amp; uniforme</h4><p class="text-secondary small mb-0">Uniforme en tissu imprimé obligatoire(lundi - mardi - mercredi), Le lacoste(jeudi - vendredi - samedi), le t-shirt pour EPS.</p></div></div>
          <div class="col-md-4"><div class="card-soft p-3 h-100"><i class="bi bi-book fs-4" style="color:var(--bleu);"></i><h4 class="fs-6 mt-2">Accompagnement spirituel</h4><p class="text-secondary small mb-0">Des cours de Catéchisme preparant au baptême à la communion et à la confirmation sont proposés au sein de l'établissement pour les chrétiens catholiques.</p></div></div>
        </div>

        <!-- Galerie photos : gérée dynamiquement, ajout/suppression via l'espace administration -->
        <div class="mt-5">
          <div class="eyebrow">En images</div>
          <h2 class="mt-2 mb-4 fs-3">La vie au collège en photos</h2>
          <div class="row g-3" id="galleryGrid">
            <?php foreach ($galleryItems as $g): $url = image_url($g['image']); ?>
              <div class="col-md-4">
                <div class="gallery-slot">
                  <?php if ($url): ?>
                    <img src="<?= h($url) ?>" alt="<?= h($g['caption']) ?>">
                    <?php if ($g['caption']): ?><div class="slot-caption"><?= h($g['caption']) ?></div><?php endif; ?>
                  <?php else: ?>
                    <div class="slot-placeholder"><i class="bi <?= h(placeholder_icon($g['image'])) ?>"></i><span><?= h($g['caption']) ?></span></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <a class="btn btn-outline-secondary btn-sm mt-4" href="admin/login.php" target="_blank">
            <i class="bi bi-images"></i> Gérer les photos (accès administration)
          </a>
        </div>

        <div class="accordion mt-4" id="charteAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#eng1">Nos engagements</button></h2>
            <div id="eng1" class="accordion-collapse collapse show" data-bs-parent="#charteAccordion">
              <div class="accordion-body text-secondary small">Une bonne éducation garantie à chaque apprenant · Des salles carrelées, bien aérées, équipées de tableaux à marqueur · Une carte de suivi de l'écolage et des notes tout au long de l'année · Un dialogue régulier entre l'école et les familles.</div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eng2">Charte de vie scolaire</button></h2>
            <div id="eng2" class="accordion-collapse collapse" data-bs-parent="#charteAccordion">
              <div class="accordion-body text-secondary small">Port de l'uniforme et tenue correcte exigés · Téléphone portable, tabac et alcool interdits dans l'enceinte du collège · Ponctualité, respect et discipline attendus de chaque élève · Règlement intérieur détaillé disponible au secrétariat.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Témoignages -->
      <div class="mt-5 pt-5">
        <div class="text-center mx-auto" style="max-width:600px;">
          <div class="eyebrow">Ils en parlent</div>
          <h2 class="mt-2 mb-1">La parole aux parents et aux élèves</h2>
          <p class="fst-italic small text-secondary">Témoignages illustratifs — à remplacer par de vrais retours de votre communauté.</p>
        </div>

        <div id="testiCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <div class="testi-card"><div class="quote">"</div><p class="text-secondary">Depuis que mon fils est aux Sauveurs, je vois une vraie différence dans sa rigueur de travail et sa confiance en lui.</p><strong class="text-marine" style="font-family:'Fraunces',serif;">Mère d'élève</strong><div class="small text-secondary mono">Classe de 4ème</div></div>
            </div>
            <div class="carousel-item">
              <div class="testi-card"><div class="quote">"</div><p class="text-secondary">Les professeurs prennent vraiment le temps d'expliquer et de suivre chaque élève individuellement.</p><strong class="text-marine" style="font-family:'Fraunces',serif;">Ancien élève</strong><div class="small text-secondary mono">Admis au BEPC</div></div>
            </div>
            <div class="carousel-item">
              <div class="testi-card"><div class="quote">"</div><p class="text-secondary">Un cadre sérieux mais chaleureux, où mon enfant se sent en confiance pour progresser.</p><strong class="text-marine" style="font-family:'Fraunces',serif;">Père d'élève</strong><div class="small text-secondary mono">Classe de 6ème</div></div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#testiCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
          <button class="carousel-control-next" type="button" data-bs-target="#testiCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===================================================== -->
<!-- PAGE : FAQ -->
<!-- ===================================================== -->
<div class="page-section" id="page-faq">
  <section class="inner bg-ciel-pale">
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <div class="eyebrow">Questions fréquentes</div>
          <h2 class="mt-2 mb-3">Tout ce que vous voulez savoir</h2>
          <p class="text-secondary mb-4" style="max-width:600px;">Inscriptions, écolage, horaires, examens : voici les réponses aux questions les plus posées par les parents et futurs élèves.</p>

          <div class="accordion faq-item" id="faqAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Comment inscrire mon enfant au collège ?</button></h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">Rendez-vous au secrétariat ou remplissez le formulaire de préinscription sur ce site. Le dossier est ensuite étudié, puis vous recevrez une confirmation et le calendrier de la rentrée.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Quels documents sont nécessaires pour l'inscription ?</button></h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">2 photocopies d'acte de naissance sécurisé, les bulletins de passage ou de redoublement, ainsi que le relevé du CEP (pour la 6ème) ou du BEPC (pour les 2ndes). Pour les candidats aux examens, une attestation légalisée et une copie de la CIN sont demandées à la rentrée.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Quelles sont les séries proposées au second cycle ?</button></h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">De la 2nde à la Terminale, le collège propose les séries A2, B, C et D, avec un accompagnement renforcé en 1ère et Terminale pour la préparation du baccalauréat.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Quels sont les horaires de cours et de secrétariat ?</button></h2>
              <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">Cours du lundi au vendredi : matin 7h–13h, soir 15h–17h, et TD le samedi 7h–13h pour les 4e, 3e, 1ères et Terminales. Le secrétariat est ouvert du lundi au vendredi de 7h à 17h.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Le collège propose-t-il un accompagnement spirituel ?</button></h2>
              <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">Oui, un catéchisme préparant au baptême, à la communion et a la confirmation est proposé au sein de l'établissement pour les chretiens catholiques, en complément de la formation académique.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">Comment suis-je informé des notes et de l'écolage de mon enfant ?</button></h2>
              <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">Une carte de suivi de l'écolage et des notes est remise à chaque famille et mise à jour tout au long de l'année, en complément d'un dialogue régulier avec l'équipe pédagogique.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">Où se trouve le collège et comment vous contacter ?</button></h2>
              <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">Le collège est situé à <span><?= h(get_setting('address')) ?></span>. Vous pouvez nous joindre par WhatsApp, par téléphone ou directement au secrétariat.</div>
              </div>
            </div>
          </div>

          <div class="faq-cta mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><strong class="text-marine d-block" style="font-family:'Fraunces',serif;">Une autre question ?</strong><span class="text-secondary small">Notre secrétariat vous répond rapidement.</span></div>
            <a href="#" onclick="showPage('admission');return false;" class="btn btn-marine btn-sm px-3">Nous contacter</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ===== Modal Détail actualité ===== -->
<div class="modal fade" id="postModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="post-img" id="postModalImg"><i class="bi post-icon" id="postModalIcon"></i></div>
      <div class="modal-header">
        <div>
          <span class="tag" id="postModalTag"></span>
          <div class="post-date mono mt-1" id="postModalDate"></div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h4 class="text-marine mb-2" id="postModalTitle"></h4>
        <p class="text-secondary mb-0" id="postModalDesc"></p>
      </div>
    </div>
  </div>
</div>

<!-- ===== Boutons flottants ===== -->
<a class="whatsapp-float" href="<?= h($whatsappUrl) ?>" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>
<button class="scrolltop-btn" id="scrollTopBtn" aria-label="Retour en haut" onclick="window.scrollTo({top:0,behavior:'smooth'});">
  <i class="bi bi-arrow-up"></i>
</button>

<!-- ===== FOOTER ===== -->


<footer>
  <div class="container">
    <div class="row gy-4 mb-4">
      <div class="col-md-5">
        <div class="d-flex align-items-center gap-2 mb-2">
          <img class="brand-logo-img" src="" alt="Logo Collège Privé Les Sauveurs" width="32" height="32" style="border-radius:50%;object-fit:cover;border:2px solid #5AC0EA;">
          <strong class="text-white" style="font-family:'Fraunces',serif;">Collège Privé Les Sauveurs</strong>
        </div>
        <p class="small" style="max-width:34ch;">Crainte de Dieu · Sagesse · Intelligence — Cotonou, Bénin.</p>
      </div>
      <div class="col-6 col-md-3">
        <h5>Navigation</h5>
        <ul class="small">
          <li><a href="#" onclick="showPage('apropos');return false;">Le collège</a></li>
          <li><a href="#" onclick="showPage('classes');return false;">Nos classes</a></li>
          <li><a href="#" onclick="showPage('resultats');return false;">Résultats</a></li>
          <li><a href="#" onclick="showPage('admission');return false;">Admission</a></li>
          <li><a href="#" onclick="showPage('faq');return false;">FAQ</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <h5>Contact</h5>
        <ul class="small">
          <li><a href="<?= h($whatsappUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> <?= h($whatsappDisplay) ?></a></li>
          <li><a href="tel:<?= h($phoneTelLink) ?>"><i class="bi bi-telephone"></i> <?= h($phoneDisplay) ?></a></li>
          <li>
  <a href="https://www.google.com/maps/search/?api=1&query=6.379007,2.4595451" target="_blank" rel="noopener" style="color:#C9DCEB;">
    <i class="bi bi-geo-alt"></i> <?= h(get_setting('address')) ?>
  </a>
</li>
          <li><a href="https://www.facebook.com/profile.php?id=61591726087946&mibextid=rS40aB7S9Ucbxw6v" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> Facebook</a></li>
          <li><a href="admin/login.php" target="_blank"><i class="bi bi-gear"></i> Gestion du site</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5>Newsletter</h5>
        <p class="small mb-2" style="max-width:26ch;">Recevez les actualités et dates clés du collège.</p>
        <form class="newsletter-form" id="newsletterForm">
          <input type="email" placeholder="Votre email" required>
          <button type="submit" aria-label="S'abonner">→</button>
        </form>
        <span class="small d-none" id="newsletterMsg" style="color:var(--ciel);">Merci, vous êtes inscrit(e) !</span>
      </div>
    </div>
    <div class="foot-bottom d-flex flex-wrap justify-content-between gap-2">
      <span><?= h(get_setting('footer_copyright')) ?></span>
      <span>La Gloire de Dieu, c'est l'homme vivant, c'est l'homme debout.</span>
    </div>
  </div>
</footer>

</div><!-- /.site-wrap -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="assets/branding.js"></script>

<?php
$tickerJson = json_encode($tickerLines, JSON_UNESCAPED_UNICODE);
$postsJson = json_encode($postsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<script>
window.SITE_DATA = {
  tickerItems: <?= $tickerJson ?>,
  posts: <?= $postsJson ?>
};
</script>
<script src="assets/translate.js"></script>
<script src="assets/site.js"></script>
</body>
</html>
