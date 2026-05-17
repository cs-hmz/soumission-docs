<?php
require_once 'db.php';

$email  = trim($_GET['email'] ?? '');
$erreur = '';
$succes = '';

if (!$email) {
    header('Location: verify_code.php');
    exit;
}

// Vérifier que l'étudiant est activé
$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT id, est_active, mot_de_passe FROM etudiants WHERE email = ?');
$stmt->execute([$email]);
$etudiant = $stmt->fetch();

if (!$etudiant || !$etudiant['est_active']) {
    header('Location: verify_code.php');
    exit;
}

if ($etudiant['mot_de_passe']) {
    // Mot de passe déjà défini
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdp     = $_POST['mot_de_passe']     ?? '';
    $confirm = $_POST['confirmation']     ?? '';

    if (strlen($mdp) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($mdp !== $confirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        $upd  = $pdo->prepare('UPDATE etudiants SET mot_de_passe = ? WHERE id = ?');
        $upd->execute([$hash, $etudiant['id']]);
        $succes = 'Mot de passe créé avec succès !';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un mot de passe</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Créer votre mot de passe</h2>
        <p>Email : <strong><?= htmlspecialchars($email) ?></strong></p>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succes) ?></div>
            <a href="login.php" class="btn btn-primary btn-full">Se connecter</a>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe (min. 8 caractères)</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Enregistrer le mot de passe</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>