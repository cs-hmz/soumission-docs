<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header('Location: login.php');
    exit;
}

// Vérifier mPDF disponible
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('mPDF non installé. Exécutez : composer require mpdf/mpdf');
}

require_once __DIR__ . '/vendor/autoload.php';

$pdo = getPDO();
$id  = $_SESSION['user_id'];

// Récupérer étudiant
$stmt = $pdo->prepare('SELECT * FROM etudiants WHERE id = ?');
$stmt->execute([$id]);
$etudiant = $stmt->fetch();

// Récupérer fichiers
$stmt2 = $pdo->prepare('SELECT * FROM fichiers WHERE etudiant_id = ? ORDER BY date_envoi DESC');
$stmt2->execute([$id]);
$fichiers = $stmt2->fetchAll();

// Construire le HTML du PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: dejavusans, sans-serif; font-size: 12px; color: #1e293b; }
    h1 { color: #2563eb; font-size: 20px; text-align: center; margin-bottom: 4px; }
    h2 { color: #2563eb; font-size: 14px; border-bottom: 1px solid #2563eb; padding-bottom: 4px; margin-top: 20px; }
    .subtitle { text-align: center; color: #64748b; font-size: 11px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #2563eb; color: white; padding: 6px 8px; text-align: left; font-size: 11px; }
    td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .info-grid td:first-child { font-weight: bold; width: 35%; color: #475569; }
    .badge { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
</style>
</head>
<body>
<h1>Plateforme Étudiante de Soumission de Documents</h1>
<p class="subtitle">Récapitulatif personnel — Généré le <?= date('d/m/Y à H:i') ?></p>

<h2>Informations personnelles</h2>
<table class="info-grid">
    <tr><td>Nom complet</td><td><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></td></tr>
    <tr><td>Numéro Apogée</td><td><?= htmlspecialchars($etudiant['num_apogee']) ?></td></tr>
    <tr><td>Email</td><td><?= htmlspecialchars($etudiant['email']) ?></td></tr>
    <tr><td>Date d'inscription</td><td><?= htmlspecialchars($etudiant['date_inscription']) ?></td></tr>
    <tr><td>Statut</td><td><span class="badge">Compte activé</span></td></tr>
</table>

<h2>Fichiers envoyés (<?= count($fichiers) ?>)</h2>
<?php if (empty($fichiers)): ?>
    <p style="color:#64748b;font-style:italic">Aucun fichier envoyé.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nom du fichier</th>
            <th>Type</th>
            <th>Taille</th>
            <th>Date d'envoi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($fichiers as $i => $f): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($f['nom_fichier']) ?></td>
            <td><?= htmlspecialchars($f['type_mime']) ?></td>
            <td><?= round($f['taille'] / 1024, 1) ?> Ko</td>
            <td><?= htmlspecialchars($f['date_envoi']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

// Générer le PDF avec mPDF
$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 15,
    'margin_bottom' => 15,
    'margin_left'   => 15,
    'margin_right'  => 15,
]);

$mpdf->SetTitle('Récapitulatif — ' . $etudiant['prenom'] . ' ' . $etudiant['nom']);
$mpdf->SetAuthor('Plateforme Étudiante');
$mpdf->WriteHTML($html);
$mpdf->Output('recapitulatif_' . $etudiant['num_apogee'] . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);