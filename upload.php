<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$response = ['status' => 'error', 'message' => ''];

if (empty($_SESSION['user_id'])) {
    $response['message'] = 'Session non active. Veuillez vous connecter.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    $response['message'] = 'Aucun fichier envoyé.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];
$maxSize = 10 * 1024 * 1024; // 10 Mo
$allowedMimeTypes = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'image/jpg',
];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Erreur lors de l’upload du fichier.';
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $response['message'] = 'Le fichier est trop volumineux.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $response['message'] = 'Le fichier n’a été que partiellement téléchargé.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $response['message'] = 'Aucun fichier n’a été sélectionné.';
            break;
    }
    echo json_encode($response);
    exit;
}

if ($file['size'] > $maxSize) {
    $response['message'] = 'Le fichier dépasse la taille maximale autorisée de 10 Mo.';
    echo json_encode($response);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if ($mimeType === false || !in_array($mimeType, $allowedMimeTypes, true)) {
    $response['message'] = 'Type de fichier non pris en charge. Seuls les PDF et les images sont autorisés.';
    echo json_encode($response);
    exit;
}

$originalName = basename($file['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$safeBaseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName);
$safeBaseName = substr($safeBaseName, 0, 100);
$storedName = sprintf('%s_%s.%s', time(), bin2hex(random_bytes(6)), $extension);
$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $response['message'] = 'Impossible de créer le dossier uploads.';
    echo json_encode($response);
    exit;
}

$destination = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $response['message'] = 'Impossible de déplacer le fichier téléchargé.';
    echo json_encode($response);
    exit;
}

try {
    $sql = 'INSERT INTO fichiers (id_etudiant, nom_fichier, nom_original, type_mime, taille, date_upload) VALUES (:id_etudiant, :nom_fichier, :nom_original, :type_mime, :taille, NOW())';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_etudiant' => $_SESSION['user_id'],
        ':nom_fichier' => $storedName,
        ':nom_original' => $originalName,
        ':type_mime' => $mimeType,
        ':taille' => $file['size'],
    ]);

    $response['status'] = 'success';
    $response['message'] = 'Fichier téléversé avec succès.';
    $response['file'] = [
        'original_name' => $originalName,
        'stored_name' => $storedName,
        'type' => $mimeType,
        'size' => $file['size'],
    ];
} catch (PDOException $e) {
    if (file_exists($destination)) {
        unlink($destination);
    }
    $response['message'] = 'Impossible d’enregistrer le fichier en base de données.';
}

echo json_encode($response);
