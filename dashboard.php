<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();
$id  = $_SESSION['user_id'];

$erreur_upload = '';
$succes_upload = '';

// Traitement upload PHP classique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
    $fichier    = $_FILES['fichier'];
    $taille_max = 5 * 1024 * 1024;
    $types_ok   = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreur_upload = 'Erreur lors de la réception du fichier.';
    } elseif ($fichier['size'] > $taille_max) {
        $erreur_upload = 'Fichier trop volumineux (max 5 Mo).';
    } else {
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $type_mime = $finfo->file($fichier['tmp_name']);

        if (!in_array($type_mime, $types_ok, true)) {
            $erreur_upload = 'Type non autorisé (PDF, JPG, PNG, GIF uniquement).';
        } else {
            $dossier = __DIR__ . '/uploads/';
            if (!is_dir($dossier)) mkdir($dossier, 0755, true);

            $ext          = pathinfo($fichier['name'], PATHINFO_EXTENSION);
            $nom_stockage = uniqid('file_', true) . '.' . strtolower($ext);

            if (move_uploaded_file($fichier['tmp_name'], $dossier . $nom_stockage)) {
                $stmt = $pdo->prepare('INSERT INTO fichiers (etudiant_id, nom_fichier, nom_stockage, type_mime, taille) VALUES (?,?,?,?,?)');
                $stmt->execute([$id, $fichier['name'], $nom_stockage, $type_mime, $fichier['size']]);
                $succes_upload = 'Fichier envoyé avec succès !';
            } else {
                $erreur_upload = 'Impossible de sauvegarder le fichier.';
            }
        }
    }
}

// Récupérer les fichiers de l'étudiant
$stmt = $pdo->prepare('SELECT * FROM fichiers WHERE etudiant_id = ? ORDER BY date_envoi DESC');
$stmt->execute([$id]);
$fichiers = $stmt->fetchAll();

// Récupérer infos étudiant
$stmt2 = $pdo->prepare('SELECT nom, prenom, email, num_apogee, date_inscription FROM etudiants WHERE id = ?');
$stmt2->execute([$id]);
$etudiant = $stmt2->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace — Plateforme Étudiante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
    <span class="navbar-brand">Plateforme Étudiante</span>
    <div class="navbar-right">
        <span>Bonjour, <?= htmlspecialchars($_SESSION['nom']) ?></span>
        <a href="generate_pdf.php" class="btn btn-secondary btn-sm">Mon PDF</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container mt-2">

    <!-- Infos étudiant -->
    <div class="card mb-2">
        <h3>Mes informations</h3>
        <table class="info-table">
            <tr><th>Nom complet</th><td><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></td></tr>
            <tr><th>Numéro Apogée</th><td><?= htmlspecialchars($etudiant['num_apogee']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($etudiant['email']) ?></td></tr>
            <tr><th>Inscrit le</th><td><?= htmlspecialchars($etudiant['date_inscription']) ?></td></tr>
        </table>
    </div>

    <!-- Upload fichiers -->
    <div class="card mb-2">
        <h3>Envoyer un fichier</h3>

        <?php if ($erreur_upload): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur_upload) ?></div>
        <?php endif; ?>
        <?php if ($succes_upload): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succes_upload) ?></div>
        <?php endif; ?>

        <form method="POST" action="dashboard.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fichier">Fichier (PDF, image — max 5 Mo)</label>
                <input type="file" id="fichier" name="fichier" accept=".pdf,.jpg,.jpeg,.png,.gif" required>
            </div>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </form>
    </div>

    <!-- Liste des fichiers -->
    <div class="card">
        <h3>Mes fichiers envoyés</h3>
        <?php if (empty($fichiers)): ?>
            <p class="text-muted">Aucun fichier envoyé pour le moment.</p>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom du fichier</th>
                    <th>Type</th>
                    <th>Taille</th>
                    <th>Date</th>
                    <th>Action</th>
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