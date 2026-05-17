<?php
require_once 'db.php';

$erreur = '';
$succes = '';
$redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code']  ?? '');

    if (!$email || !$code) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT id, est_active FROM etudiants WHERE email = ? AND code_activation = ?');
            $stmt->execute([$email, $code]);
            $etudiant = $stmt->fetch();

            if (!$etudiant) {
                $erreur = 'Email ou code incorrect.';
            } elseif ($etudiant['est_active']) {
                $erreur = 'Compte déjà activé. <a href="login.php">Se connecter</a>';
            } else {
                $upd = $pdo->prepare('UPDATE etudiants SET est_active = 1 WHERE id = ?');
                $upd->execute([$etudiant['id']]);
                $succes = 'Compte activé avec succès !';
                $redirect = 'create_password.php?email=' . urlencode($email);
            }
        } catch (Exception $e) {
            $erreur = 'Erreur serveur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if ($redirect): ?>
    <meta http-equiv="refresh" content="2;url=<?= htmlspecialchars($redirect) ?>">
    <?php endif; ?>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Activation du compte</h2>
        <p>Entrez l'email utilisé lors de l'inscription et le code reçu.</p>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= $erreur ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success"><?= htmlspecialchars($succes) ?> Redirection en cours...</div>
        <?php endif; ?>

        <?php if (!$succes): ?>
        <form method="POST" action="verify_code.php">
            <div class="form-group">
                <label for="email">Adresse Email</label>
                <input type="email" id="email" name="email" required
                       placeholder="votre@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="code">Code d'activation (8 chiffres)</label>
                <input type="text" id="code" name="code" required
                       maxlength="8" placeholder="12345678"
                       value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Activer le compte</button>
        </form>
        <?php endif; ?>

        <p class="text-center mt-1"><a href="login.php">← Retour connexion</a></p>
    </div>
</div>
</body>
</html>
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Activation du compte</h2>
        <p>Entrez l'email et le code reçu par email.</p>

        <div id="message"></div>

        <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" placeholder="votre@email.com">
        </div>
        <div class="form-group">
            <label for="code">Code d'activation (8 chiffres)</label>
            <input type="text" id="code" maxlength="8" placeholder="12345678">
        </div>
        <button class="btn btn-primary btn-full" onclick="activerCompte()">Activer le compte</button>
        <p class="text-center mt-1"><a href="login.php">← Retour connexion</a></p>
    </div>
</div>

<script src="js/main.js"></script>
</body>
</html>