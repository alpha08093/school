<?php

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------- Réglages du site (clé/valeur) ---------- */

function get_setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (get_db()->query('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = $row['svalue'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void {
    $stmt = get_db()->prepare('
        INSERT INTO settings (skey, svalue) VALUES (:k, :v)
        ON CONFLICT(skey) DO UPDATE SET svalue = :v
    ');
    $stmt->execute(['k' => $key, 'v' => $value]);
}

/* ---------- Jeton anti-CSRF ---------- */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Jeton de sécurité invalide. Merci de recharger la page et réessayer.');
    }
}

/* ---------- Téléversement / redimensionnement d'images ---------- */

/**
 * Traite un fichier envoyé via <input type="file" name="$field">.
 * Redimensionne l'image (plus grand côté = $maxDim) et l'enregistre en JPEG
 * dans $subdir (relatif à /uploads). Retourne le chemin relatif stocké en
 * base, ou null si aucun fichier n'a été fourni.
 *
 * Lève une Exception si un fichier a été fourni mais est invalide.
 */
function handle_image_upload(string $field, string $subdir, int $maxDim = 1280, int $quality = 80): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Le téléversement de l'image a échoué (code {$file['error']}).");
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new Exception("L'image dépasse la taille maximale autorisée (8 Mo).");
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new Exception("Le fichier envoyé n'est pas une image valide.");
    }

    switch ($info[2]) {
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($file['tmp_name']); break;
        case IMAGETYPE_PNG:  $src = imagecreatefrompng($file['tmp_name']);  break;
        case IMAGETYPE_WEBP: $src = imagecreatefromwebp($file['tmp_name']); break;
        case IMAGETYPE_GIF:  $src = imagecreatefromgif($file['tmp_name']);  break;
        default:
            throw new Exception("Format d'image non pris en charge (utilisez JPG, PNG, WEBP ou GIF).");
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w > $maxDim || $h > $maxDim) {
        if ($w >= $h) { $newW = $maxDim; $newH = (int) round($h * ($maxDim / $w)); }
        else          { $newH = $maxDim; $newW = (int) round($w * ($maxDim / $h)); }
        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);
        $src = $resized;
    }

    $uploadsRoot = __DIR__ . '/../uploads/' . trim($subdir, '/');
    if (!is_dir($uploadsRoot)) {
        mkdir($uploadsRoot, 0775, true);
    }
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    $fullPath = $uploadsRoot . '/' . $filename;
    imagejpeg($src, $fullPath, $quality);
    imagedestroy($src);

    return trim($subdir, '/') . '/' . $filename;
}

/** Supprime un fichier précédemment stocké via handle_image_upload(), s'il existe. */
function delete_uploaded_image(?string $relativePath): void {
    if (!$relativePath || str_starts_with($relativePath, '__placeholder__:')) {
        return;
    }
    $full = __DIR__ . '/../uploads/' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}

/** URL publique d'une image stockée en base (ou null si emplacement vide/placeholder). */
function image_url(?string $relativePath): ?string {
    if (!$relativePath || str_starts_with($relativePath, '__placeholder__:')) {
        return null;
    }
    return 'uploads/' . $relativePath;
}

/** Icône Bootstrap associée à un emplacement de galerie encore vide. */
function placeholder_icon(?string $relativePath): string {
    if ($relativePath && str_starts_with($relativePath, '__placeholder__:')) {
        return substr($relativePath, strlen('__placeholder__:'));
    }
    return 'bi-image';
}

function category_label(string $cat): string {
    return match ($cat) {
        'evenement' => 'Événement',
        'resultat'  => 'Résultat',
        default     => 'Actualité',
    };
}

function category_class(string $cat): string {
    return match ($cat) {
        'evenement' => 'tag-evenement',
        'resultat'  => 'tag-resultat',
        default     => 'tag-actualite',
    };
}

function category_icon(string $cat): string {
    return match ($cat) {
        'evenement' => 'bi-calendar-event',
        'resultat'  => 'bi-trophy',
        default     => 'bi-megaphone',
    };
}

function format_date_fr(string $iso): string {
    $months = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
               7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
    $ts = strtotime($iso);
    if (!$ts) return h($iso);
    return sprintf('%02d %s %d', (int)date('d', $ts), $months[(int)date('n', $ts)], (int)date('Y', $ts));
}

/** Tronque un texte UTF-8 à $len caractères sans dépendre de l'extension mbstring. */
function truncate_text(string $s, int $len): string {
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false || count($chars) <= $len) {
        return $s;
    }
    return implode('', array_slice($chars, 0, $len)) . '…';
}
