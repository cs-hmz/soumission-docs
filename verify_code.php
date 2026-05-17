<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$response = ['status' => 'error'];

$email = trim($_POST['email'] ?? '');
$codeActivation = trim($_POST['code_activation'] ?? '');

if ($email === '' || $codeActivation === '') {
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode($response);
    exit;
}

try {
    $sql = 'SELECT id, est_active FROM etudiants WHERE email = :email AND code_activation = :code_activation LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':code_activation' => $codeActivation,
    ]);
    $etudiant = $stmt->fetch();

    if (!$etudiant) {
        echo json_encode($response);
        exit;
    }

    if ((int)$etudiant['est_active'] === 1) {
        $response['status'] = 'success';
        echo json_encode($response);
        exit;
    }

    $update = 'UPDATE etudiants SET est_active = 1 WHERE id = :id';
    $stmt = $pdo->prepare($update);
    $stmt->execute([':id' => $etudiant['id']]);

    if ($stmt->rowCount() > 0) {
        $response['status'] = 'success';
    }
} catch (PDOException $e) {
    // Logged error in a real application
}

echo json_encode($response);
