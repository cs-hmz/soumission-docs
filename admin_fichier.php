<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id  = (int) ($_GET['id'] ?? 0);
$pdo = getPDO();

$stmt = $pdo->prepare('SELECT * FROM etudiants WHERE id = ?');
$stmt->execute([$id]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    header('Location: admin_dashboard.php');
    exit;
}

$stmt2 = $pdo->prepare('SELECT * FROM fichiers WHERE etudiant_id = ? ORDER BY date_envoi DESC');
$stmt2->execute([$id]);
$fichiers = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de <?= htmlspecialchars($etudiant['prenom']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
    <span class="navbar-brand">Administration</span>
    <div class="navbar-right">
        <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">← Retour</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
    </div>
</nav>
<div class="container mt-2">
    <div class="card">
        <h3>Fichiers de <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h3>
        <p>Email : <?= htmlspecialchars($etudiant['email']) ?> | Apogée : <?= htmlspecialchars($etudiant['num_apogee']) ?></p>

        <?php if (empty($fichiers)): ?>
            <p class="text-muted">Aucun fichier pour cet étudiant.</p>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Nom</th><th>Type</th><th>Taille</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fichiers as $i => $f): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($f['nom_fichier']) ?></td>
                    <td><?= htmlspecialchars($f['type_mime']) ?></td>
                    <td><?= round($f['taille'] / 1024, 1) ?> Ko</td>
                    <td><?= htmlspecialchars($f['date_envoi']) ?></td>
                    <td><a href="uploads/<?= htmlspecialchars($f['nom_stockage']) ?>" target="_blank" class="btn btn-secondary btn-sm">Télécharger</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>