<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la réception du fichier.']);
    exit;
}

$fichier     = $_FILES['fichier'];
$taille_max  = 5 * 1024 * 1024; // 5 Mo
$types_ok    = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];

// Vérification taille
if ($fichier['size'] > $taille_max) {
    echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 5 Mo).']);
    exit;
}

// Vérification MIME réelle (pas juste l'extension)
$finfo     = new finfo(FILEINFO_MIME_TYPE);
$type_mime = $finfo->file($fichier['tmp_name']);

if (!in_array($type_mime, $types_ok, true)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (PDF, JPG, PNG, GIF uniquement).']);
    exit;
}

// Dossier uploads
$dossier = __DIR__ . '/uploads/';
if (!is_dir($dossier)) {
    mkdir($dossier, 0755, true);
}

// Nom de stockage unique
$ext          = pathinfo($fichier['name'], PATHINFO_EXTENSION);
$nom_stockage = uniqid('file_', true) . '.' . strtolower($ext);
$chemin       = $dossier . $nom_stockage;

if (!move_uploaded_file($fichier['tmp_name'], $chemin)) {
    echo json_encode(['success' => false, 'message' => 'Impossible de sauvegarder le fichier.']);
    exit;
}

// Enregistrement en base
$pdo  = getPDO();
$stmt = $pdo->prepare('INSERT INTO fichiers (etudiant_id, nom_fichier, nom_stockage, type_mime, taille) VALUES (?,?,?,?,?)');
$stmt->execute([
    $_SESSION['user_id'],
    $fichier['name'],
    $nom_stockage,
    $type_mime,
    $fichier['size'],
]);

echo json_encode(['success' => true, 'message' => 'Fichier envoyé avec succès !']);