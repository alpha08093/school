<?php
/**
 * Connexion à la base de données (SQLite) + création automatique du schéma
 * au premier lancement. Aucune configuration de serveur MySQL nécessaire :
 * tout est stocké dans un seul fichier (data/database.sqlite).
 */

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }
    $dbFile = $dataDir . '/database.sqlite';
    $isNew = !file_exists($dbFile);

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    // WAL : les lectures (le site public) ne bloquent pas les écritures (l'admin), et inversement.
    $pdo->exec('PRAGMA journal_mode = WAL');

    if ($isNew) {
        install_schema($pdo);
    }

    return $pdo;
}

function install_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL DEFAULT 'actualite',
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            image TEXT,
            post_date TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE gallery (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            caption TEXT,
            image TEXT NOT NULL,
            position INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE settings (
            skey TEXT PRIMARY KEY,
            svalue TEXT
        );
    ");

    $pdo->exec("
        CREATE TABLE exam_headline (
            skey TEXT PRIMARY KEY,
            label TEXT NOT NULL,
            rate REAL NOT NULL,
            decimals INTEGER NOT NULL DEFAULT 2
        );
    ");

    $pdo->exec("
        CREATE TABLE exam_rows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            rate REAL NOT NULL,
            decimals INTEGER NOT NULL DEFAULT 2,
            position INTEGER NOT NULL DEFAULT 0
        );
    ");

    $pdo->exec("
        CREATE TABLE admissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_name TEXT NOT NULL,
            target_class TEXT NOT NULL,
            guardian_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            message TEXT,
            status TEXT NOT NULL DEFAULT 'nouveau',
            created_at TEXT NOT NULL
        );
    ");

    seed_default_data($pdo);
}

function seed_default_data(PDO $pdo): void {
    // Compte administrateur par défaut : admin / sauveurs2026
    // /!\ À changer immédiatement depuis "Mon compte" une fois connecté.
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)');
    $stmt->execute(['admin', password_hash('sauveurs2026', PASSWORD_DEFAULT), date('c')]);

    $now = date('c');
    $posts = [
        ['resultat', "Résultats aux examens 2025–2026", "Succès général de 91,67% pour l'année 2025–2026 : BEPC 72,73%, BAC 90,32% (Série B 87,5%, Série D 100%). Toute l'équipe félicite ses admis !", '2026-07-08'],
        ['evenement', "Cours de renforcement et de vacances 2026", "Cours de renforcement du 13 juillet au 21 août (4e, 3e/1ères, Terminales) et cours de vacances du 27 juillet au 21 août (6e, 5e/2ndes). Lundi, mardi, jeudi, vendredi. Tarifs disponibles au secrétariat.", '2026-07-01'],
        ['actualite', "Le collège primé aux Africa Excellence Awards", "Le Collège Privé Les Sauveurs a reçu le Prix de l'Excellence lors de la 11ème édition des Africa Excellence Awards, décerné par l'Association Les Ambassadeurs du Développement.", '2025-05-22'],
        ['actualite', "Rentrée 2026–2027 : inscriptions ouvertes", "Les inscriptions et réinscriptions pour l'année scolaire 2026–2027 sont ouvertes depuis le 1er juin. Étude de dossier pour tout nouvel élève.", '2026-06-01'],
    ];
    $stmt = $pdo->prepare('INSERT INTO posts (category, title, description, image, post_date, created_at, updated_at) VALUES (?, ?, ?, NULL, ?, ?, ?)');
    foreach ($posts as $p) {
        $stmt->execute([$p[0], $p[1], $p[2], $p[3], $now, $now]);
    }

    $gallery = [
        ['Cour de récréation', 'bi-sun'],
        ['Élèves en uniforme', 'bi-people'],
        ['Enseignants en classe', 'bi-easel'],
        ['Activités & catéchisme', 'bi-book'],
        ['Lauréats BAC / BEPC', 'bi-mortarboard'],
        ['Accueil & secrétariat', 'bi-door-open'],
    ];
    // Les 6 emplacements de départ sont créés sans image : ils s'affichent
    // avec une icône tant qu'aucune photo n'a été téléversée depuis l'admin.
    $stmt = $pdo->prepare('INSERT INTO gallery (caption, image, position, created_at) VALUES (?, ?, ?, ?)');
    foreach ($gallery as $i => $g) {
        $stmt->execute([$g[0], '__placeholder__:' . $g[1], $i, $now]);
    }

    $settings = [
        'announce_bar'       => "Rentrée 2026–2027 : inscriptions ouvertes",
        'ticker_items'       => "🏆 91,67% de réussite générale 2025–2026\n📜 Lettre de félicitation du ministère\n📣 Inscriptions 2026–2027 ouvertes depuis le 1er juin\n🥇 Trophée Africa Excellence Awards décerné au collège",
        'address'            => "Sènadé, Rue de la station Octogone, en face de la buvette Tabala, Cotonou, Bénin",
        'phone'              => "01 40 53 14 94",
        'phone_tel_link'     => "+2290140531494",
        'whatsapp_display'   => "01 97 13 43 44",
        'whatsapp_number'    => "2290197134344",
        'office_hours'       => "Lundi–vendredi, 7h–17h",
        'class_hours'        => "Lundi–vendredi : matin 7h–13h, soir 15h–17h. Samedi : TD 7h–13h (4e, 3e, 1ères, Tles).",
        'enrollment_note'    => "Les inscriptions et réinscriptions pour l'année scolaire 2026–2027 sont ouvertes depuis le 1er juin. Étude de dossier pour tout nouvel élève.",
        'footer_copyright'   => "© " . date('Y') . " Collège Privé Les Sauveurs — Cotonou, Bénin",
    ];
    $stmt = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    $headline = [
        ['bepc',    'BEPC 2026',                    72.73, 2],
        ['bac',     'BAC 2026',                     90.32, 2],
        ['general', 'Succès général 2025–2026',     91.67, 2],
    ];
    $stmt = $pdo->prepare('INSERT INTO exam_headline (skey, label, rate, decimals) VALUES (?, ?, ?, ?)');
    foreach ($headline as $r) {
        $stmt->execute($r);
    }

    $rows = [
        ['6ème', 95.74, 2], ['5ème', 94.87, 2], ['4ème', 91.98, 2], ['3ème', 81.13, 2],
        ['2nde AB', 100, 0], ['2nde C', 100, 0], ['2nde D', 94.74, 2],
        ['1ère AB', 100, 0], ['1ère C', 100, 0], ['1ère D', 100, 0],
        ['Terminale AB', 83.33, 2], ['Terminale D', 85.71, 2],
        ['Série B — BAC', 87.50, 2], ['Série D — BAC', 100, 0],
    ];
    $stmt = $pdo->prepare('INSERT INTO exam_rows (label, rate, decimals, position) VALUES (?, ?, ?, ?)');
    foreach ($rows as $i => $r) {
        $stmt->execute([$r[0], $r[1], $r[2], $i]);
    }
}
