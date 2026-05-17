<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['role'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']        ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');

    if (!$email || !$mdp) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $pdo = getPDO();

        // Essai admin
        $stmt = $pdo->prepare('SELECT id, nom, mot_de_passe FROM administrateurs WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($mdp, $admin['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['role']     = 'admin';
            $_SESSION['user_id']  = $admin['id'];
            $_SESSION['nom']      = $admin['nom'];
            header('Location: admin_dashboard.php');
            exit;
        }

        // Essai étudiant
        $stmt = $pdo->prepare('SELECT id, nom, prenom, mot_de_passe, est_active FROM etudiants WHERE email = ?');
        $stmt->execute([$email]);
        $etudiant = $stmt->fetch();

        if ($etudiant && password_verify($mdp, $etudiant['mot_de_passe'] ?? '')) {
            if (!$etudiant['est_active']) {
                $erreur = 'Compte non activé. Vérifiez votre email.';
            } else {
                session_regenerate_id(true);
                $_SESSION['role']     = 'etudiant';
                $_SESSION['user_id']  = $etudiant['id'];
                $_SESSION['nom']      = $etudiant['nom'] . ' ' . $etudiant['prenom'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Plateforme Étudiante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Connexion</h2>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
        </form>
        <p class="text-center mt-1">
            Pas de compte ? <a href="register.php">S'inscrire</a> |
            <a href="verify_code.php">Activer mon compte</a>
        </p>
    </div>
</div>
</body>
</html>