<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

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

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'etudiant') {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $studentSql = 'SELECT nom, prenom, email, apogee FROM etudiants WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($studentSql);
    $stmt->execute([':id' => $userId]);
    $student = $stmt->fetch();

    if (!$student) {
        throw new Exception('Étudiant introuvable.');
    }

    $filesSql = 'SELECT nom_original, type_mime, taille, date_upload FROM fichiers WHERE id_etudiant = :id_etudiant ORDER BY date_upload DESC';
    $stmt = $pdo->prepare($filesSql);
    $stmt->execute([':id_etudiant' => $userId]);
    $files = $stmt->fetchAll();
} catch (Exception $e) {
    echo '<h1>Erreur</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

$pdfDir = __DIR__ . '/pdf';
if (!is_dir($pdfDir) && !mkdir($pdfDir, 0755, true) && !is_dir($pdfDir)) {
    echo '<h1>Erreur</h1><p>Impossible de créer le dossier de stockage PDF.</p>';
    exit;
}

$fileName = sprintf('recap_%d.pdf', $userId);
$filePath = $pdfDir . DIRECTORY_SEPARATOR . $fileName;

$studentName = htmlspecialchars(trim($student['prenom'] . ' ' . $student['nom']), ENT_QUOTES, 'UTF-8');
$studentEmail = htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8');
$studentApogee = htmlspecialchars($student['apogee'], ENT_QUOTES, 'UTF-8');

$rowsHtml = '';
if (count($files) === 0) {
    $rowsHtml = '<tr><td colspan="4" style="text-align:center; padding:1rem; color:#555;">Aucun fichier envoyé pour le moment.</td></tr>';
} else {
    foreach ($files as $file) {
        $rowsHtml .= '<tr>' .
            '<td>' . htmlspecialchars($file['nom_original'], ENT_QUOTES, 'UTF-8') . '</td>' .
            '<td>' . htmlspecialchars($file['type_mime'], ENT_QUOTES, 'UTF-8') . '</td>' .
            '<td>' . number_format((int)$file['taille'] / 1024, 2, ',', ' ') . ' Ko</td>' .
            '<td>' . (new DateTime($file['date_upload']))->format('d/m/Y H:i') . '</td>' .
            '</tr>';
    }
}

$html = '<!DOCTYPE html>' .
    '<html lang="fr">' .
    '<head>' .
    '<meta charset="UTF-8">' .
    '<style>' .
    'body { font-family: Arial, sans-serif; color: #222; margin: 0; padding: 0; }' .
    '.container { padding: 24px; }' .
    'h1 { margin-bottom: 0.5rem; }' .
    '.meta { margin-bottom: 24px; font-size: 0.95rem; color: #555; }' .
    'table { width: 100%; border-collapse: collapse; margin-top: 18px; }' .
    'th, td { border: 1px solid #d6d6d6; padding: 10px 12px; }' .
    'th { background: #f3f4f6; text-align: left; }' .
    'tbody tr:nth-child(even) { background: #fbfbfb; }' .
    '</style>' .
    '</head>' .
    '<body>' .
    '<div class="container">' .
    '<h1>Récapitulatif des fichiers</h1>' .
    '<div class="meta">' .
    '<strong>Étudiant :</strong> ' . $studentName . '<br>' .
    '<strong>Email :</strong> ' . $studentEmail . '<br>' .
    '<strong>Apogée :</strong> ' . $studentApogee . '<br>' .
    '<strong>Date :</strong> ' . date('d/m/Y H:i') .
    '</div>' .
    '<table>' .
    '<thead><tr><th>Nom du fichier</th><th>Type</th><th>Taille</th><th>Date d’envoi</th></tr></thead>' .
    '<tbody>' .
    $rowsHtml .
    '</tbody>' .
    '</table>' .
    '</div>' .
    '</body>' .
    '</html>';

try {
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);
    header('Location: pdf/' . $fileName);
    exit;
} catch (\Mpdf\MpdfException $e) {
    echo '<h1>Erreur lors de la génération du PDF</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}
