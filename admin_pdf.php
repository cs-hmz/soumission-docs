<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('mPDF non installé. Exécutez : composer require mpdf/mpdf');
}

require_once __DIR__ . '/vendor/autoload.php';

$pdo = getPDO();

// Récupérer tous les étudiants avec leurs fichiers
$stmt = $pdo->query('
    SELECT e.id, e.nom, e.prenom, e.num_apogee, e.email, e.date_inscription
    FROM etudiants e
    ORDER BY e.nom, e.prenom
');
$etudiants = $stmt->fetchAll();

// Pour chaque étudiant, récupérer ses fichiers
foreach ($etudiants as &$e) {
    $s = $pdo->prepare('SELECT nom_fichier, type_mime, taille, date_envoi FROM fichiers WHERE etudiant_id = ? ORDER BY date_envoi DESC');
    $s->execute([$e['id']]);
    $e['fichiers'] = $s->fetchAll();
}
unset($e);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body         { font-family: dejavusans, sans-serif; font-size: 11px; color: #1e293b; }
    h1           { color: #1d4ed8; font-size: 18px; text-align: center; margin-bottom: 4px; }
    .subtitle    { text-align: center; color: #64748b; font-size: 10px; margin-bottom: 16px; }
    h2           { color: #1d4ed8; font-size: 12px; background: #eff6ff; padding: 5px 8px; margin: 16px 0 4px; border-left: 3px solid #2563eb; }
    table        { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th           { background: #1d4ed8; color: white; padding: 5px 6px; text-align: left; font-size: 10px; }
    td           { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .no-files    { color: #94a3b8; font-style: italic; font-size: 10px; padding: 4px 0; }
    .count       { font-weight: bold; color: #2563eb; }
</style>
</head>
<body>
<h1>Plateforme Étudiante — Récapitulatif Global</h1>
<p class="subtitle">Exporté par l'administrateur le <?= date('d/m/Y à H:i') ?> | <?= count($etudiants) ?> étudiant(s)</p>

<!-- Résumé global -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom Complet</th>
            <th>Email</th>
            <th>Apogée</th>
            <th>Inscription</th>
            <th>Nb fichiers</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($etudiants as $e): ?>
        <tr>
            <td><?= $e['id'] ?></td>
            <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
            <td><?= htmlspecialchars($e['email']) ?></td>
            <td><?= htmlspecialchars($e['num_apogee']) ?></td>
            <td><?= htmlspecialchars($e['date_inscription']) ?></td>
            <td class="count"><?= count($e['fichiers']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Détail par étudiant -->
<?php foreach ($etudiants as $e): ?>
<h2><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?> — <?= htmlspecialchars($e['email']) ?></h2>
<?php if (empty($e['fichiers'])): ?>
    <p class="no-files">Aucun fichier envoyé.</p>
<?php else: ?>
<table>
    <thead>
        <tr><th>#</th><th>Nom du fichier</th><th>Type</th><th>Taille</th><th>Date d'envoi</th></tr>
    </thead>
    <tbody>
        <?php foreach ($e['fichiers'] as $i => $f): ?>
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
<?php endforeach; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 12,
    'margin_bottom' => 12,
    'margin_left'   => 12,
    'margin_right'  => 12,
]);
$mpdf->SetTitle('Récapitulatif Global — Plateforme Étudiante');
$mpdf->SetAuthor('Administration');
$mpdf->WriteHTML($html);
$mpdf->Output('recapitulatif_global_' . date('Ymd') . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);