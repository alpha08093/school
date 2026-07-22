<?php
/**
 * Reçoit le formulaire "Nous contacter / préinscription" de la page Admission
 * et l'enregistre en base. Répond en JSON (appelé en AJAX depuis assets/site.js).
 */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $message): void {
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Méthode non autorisée.');
}

// Piège à robots : ce champ doit toujours rester vide pour un humain.
if (!empty($_POST['website'])) {
    respond(true, 'Préinscription enregistrée.'); // on répond "ok" sans rien enregistrer
}

$studentName  = trim($_POST['student_name'] ?? '');
$targetClass  = trim($_POST['target_class'] ?? '');
$guardianName = trim($_POST['guardian_name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$message      = trim($_POST['message'] ?? '');

if ($studentName === '' || $targetClass === '' || $guardianName === '' || $phone === '') {
    http_response_code(422);
    respond(false, 'Merci de renseigner tous les champs obligatoires.');
}

$stmt = get_db()->prepare('
    INSERT INTO admissions (student_name, target_class, guardian_name, phone, message, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([$studentName, $targetClass, $guardianName, $phone, $message ?: null, 'nouveau', date('c')]);

respond(true, 'Préinscription enregistrée — le secrétariat vous recontactera prochainement.');
