<?php

function auth_start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = get_db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function require_login(): array {
    $admin = current_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

/** Tente une connexion. Retourne true si réussie, false sinon (avec un léger délai anti brute-force). */
function attempt_login(string $username, string $password): bool {
    $stmt = get_db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password_hash'])) {
        usleep(400000); // 0.4s : ralentit les tentatives automatisées
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $row['id'];
    return true;
}

function do_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
