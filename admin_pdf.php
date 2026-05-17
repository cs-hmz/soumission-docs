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

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $studentSql = 'SELECT id, nom, prenom, email, date_inscription FROM etudiants ORDER BY nom, prenom';
    $stmt = $pdo->query($studentSql);
    $students = $stmt->fetchAll();

    $filesSql = 'SELECT id_etudiant, nom_original, type_mime, taille, date_upload FROM fichiers ORDER BY date_upload DESC';
    $stmt = $pdo->query($filesSql);
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<h1>Erreur</h1><p>Impossible de récupérer les données.</p>';
    exit;
}

$fileMap = [];
foreach ($files as $file) {
    $fileMap[$file['id_etudiant']][] = $file;
}

function escape($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function formatSize($bytes) {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', ' ') . ' Mo';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', ' ') . ' Ko';
    return $bytes . ' o';
}
function formatDate($v) { try { return (new DateTime($v))->format('d/m/Y H:i'); } catch (Exception $e) { return ''; } }

$rows = '';
foreach ($students as $s) {
    $sid = $s['id'];
    $dateInscription = !empty($s['date_inscription']) ? formatDate($s['date_inscription']) : '-';
    $studentName = escape($s['prenom'] . ' ' . $s['nom']);
    $email = escape($s['email']);
    $studentFiles = $fileMap[$sid] ?? [];

    if (count($studentFiles) === 0) {
        $filesHtml = '<em>Aucun fichier</em>';
    } else {
        $list = [];
        foreach ($studentFiles as $f) {
            $list[] = escape($f['nom_original']) . ' (' . escape($f['type_mime']) . ', ' . escape(formatSize($f['taille'])) . ', ' . escape(formatDate($f['date_upload'])) . ')';
        }
        $filesHtml = implode('<br>', $list);
    }

    $rows .= '<tr>' .
        '<td>' . escape($sid) . '</td>' .
        '<td>' . escape($s['nom']) . '</td>' .
        '<td>' . escape($s['prenom']) . '</td>' .
        '<td>' . $email . '</td>' .
        '<td>' . $dateInscription . '</td>' .
        '<td>' . $filesHtml . '</td>' .
        '</tr>';
}

$html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>' .
    'body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:0;padding:16px}' .
    'table{width:100%;border-collapse:collapse;margin-top:12px}' .
    'th,td{border:1px solid #ddd;padding:8px;text-align:left}' .
    'th{background:#f3f4f6}' .
    '</style></head><body>' .
    '<h1>Récapitulatif global des étudiants</h1>' .
    '<table><thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Date d\'inscription</th><th>Fichiers</th></tr></thead><tbody>' .
    $rows .
    '</tbody></table></body></html>';

$pdfDir = __DIR__ . '/pdf';
if (!is_dir($pdfDir) && !mkdir($pdfDir, 0755, true) && !is_dir($pdfDir)) {
    echo '<p>Impossible de créer le dossier pdf/.</p>';
    exit;
}

$outPath = $pdfDir . DIRECTORY_SEPARATOR . 'admin_recap.pdf';
try {
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output($outPath, \Mpdf\Output\Destination::FILE);
} catch (\Mpdf\MpdfException $e) {
    echo '<p>Erreur lors de la génération du PDF: ' . escape($e->getMessage()) . '</p>';
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Export PDF - Administration</title>
<style>body{font-family:Arial,Helvetica,sans-serif;padding:24px;color:#222} .card{background:#fff;border:1px solid #e6e6e6;padding:18px;border-radius:12px;max-width:800px} a.button{display:inline-block;margin-top:12px;padding:10px 14px;background:#2563eb;color:#fff;border-radius:999px;text-decoration:none}</style>
</head>
<body>
<div class="card">
<h2>PDF généré avec succès</h2>
<p>Le fichier global a été enregistré : <strong>pdf/admin_recap.pdf</strong></p>
<p><a class="button" href="pdf/admin_recap.pdf" target="_blank" rel="noopener">Télécharger le PDF</a></p>
<p><a href="admin_dashboard.php">Retour au tableau de bord</a></p>
</div>
</body>
</html>
