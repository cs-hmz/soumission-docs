<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();

// Récupérer tous les étudiants avec leurs fichiers
$stmt = $pdo->query('
    SELECT e.id, e.nom, e.prenom, e.num_apogee, e.email, e.date_inscription,
           COUNT(f.id) AS nb_fichiers
    FROM etudiants e
    LEFT JOIN fichiers f ON f.etudiant_id = e.id
    GROUP BY e.id
    ORDER BY e.date_inscription DESC
');
$etudiants = $stmt->fetchAll();

// Stats globales
$total_etudiants = count($etudiants);
$stmt2 = $pdo->query('SELECT COUNT(*) FROM fichiers');
$total_fichiers  = $stmt2->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Plateforme Étudiante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
    <span class="navbar-brand">Administration</span>
    <div class="navbar-right">
        <span>Admin : <?= htmlspecialchars($_SESSION['nom']) ?></span>
        <a href="admin_pdf.php" class="btn btn-primary btn-sm">Exporter PDF global</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container mt-2">

    <!-- Statistiques -->
    <div class="stats-grid mb-2">
        <div class="stat-card">
            <div class="stat-number"><?= $total_etudiants ?></div>
            <div class="stat-label">Étudiants inscrits</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $total_fichiers ?></div>
            <div class="stat-label">Fichiers envoyés</div>
        </div>
    </div>

    <!-- Liste des étudiants -->
    <div class="card">
        <h3>Liste des Étudiants et Fichiers</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Apogée</th>
                    <th>Date d'inscription</th>
                    <th>Fichiers</th>
                    <th>Action</th>
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
                    <td><span class="badge-count"><?= $e['nb_fichiers'] ?></span></td>
                    <td>
                        <a href="admin_fichiers.php?id=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">Voir fichiers</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>