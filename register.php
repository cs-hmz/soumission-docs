<?php
require_once 'db.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$erreur  = '';
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom']       ?? '');
    $prenom    = trim($_POST['prenom']    ?? '');
    $apogee    = trim($_POST['num_apogee']?? '');
    $email     = trim($_POST['email']     ?? '');

    // Validation basique
    if (!$nom || !$prenom || !$apogee || !$email) {
        $erreur = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } else {
        $pdo  = getPDO();

        // Vérifier doublon
        $stmt = $pdo->prepare('SELECT id FROM etudiants WHERE email = ? OR num_apogee = ?');
        $stmt->execute([$email, $apogee]);
        if ($stmt->fetch()) {
            $erreur = 'Cet email ou numéro Apogée est déjà utilisé.';
        } else {
            // Générer code activation 8 chiffres
            $code = (string) random_int(10000000, 99999999);

            $ins = $pdo->prepare('INSERT INTO etudiants (nom, prenom, num_apogee, email, code_activation) VALUES (?,?,?,?,?)');
            $ins->execute([$nom, $prenom, $apogee, $email, $code]);

            // Envoi email avec PHPMailer
            if (envoyerCodeEmail($email, $nom . ' ' . $prenom, $code)) {
                $succes = 'Inscription réussie ! Un code d\'activation a été envoyé à ' . htmlspecialchars($email);
            } else {
                $succes = 'Inscription réussie ! (Erreur envoi email — code : <strong>' . $code . '</strong>)';
            }
        }
    }
}

/**
 * Envoie le code d'activation par email via PHPMailer.
 * Installez PHPMailer : composer require phpmailer/phpmailer
 */
function envoyerCodeEmail(string $email, string $nomComplet, string $code): bool
{
    // Vérifier si PHPMailer est disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer non installé — exécutez : composer install');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Configuration SMTP — modifiez selon votre serveur
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hamzasbusacc1@gmail.com';   // ex: hamza123@gmail.com
        $mail->Password   = 'zica clbu bbbg kijn';              // ex: abcd efgh ijkl mnop
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('hamzasbusacc1@gmail.com', 'Plateforme Étudiante');
        $mail->addAddress($email, $nomComplet);

        $mail->isHTML(true);
        $mail->Subject = 'Votre code d\'activation';
        $mail->Body    = "
            <h2>Bienvenue, {$nomComplet} !</h2>
            <p>Votre code d'activation est :</p>
            <h1 style='letter-spacing:4px;color:#2563eb'>{$code}</h1>
            <p>Rendez-vous sur la page d'activation pour activer votre compte.</p>
        ";
        $mail->AltBody = "Votre code d'activation : {$code}";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        // DEBUG TEMPORAIRE — à supprimer après résolution
        file_put_contents(__DIR__ . '/mail_debug.txt', date('Y-m-d H:i:s') . ' | ' . $mail->ErrorInfo . "\n", FILE_APPEND);
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Plateforme Étudiante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Inscription Étudiant</h2>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success"><?= $succes ?></div>
            <p><a href="verify_code.php">→ Activer mon compte</a></p>
        <?php else: ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required
                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="num_apogee">Numéro Apogée</label>
                <input type="text" id="num_apogee" name="num_apogee" required
                       value="<?= htmlspecialchars($_POST['num_apogee'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>
        </form>
        <p class="text-center mt-1">Déjà inscrit ? <a href="verify_code.php">Activer mon compte</a> | <a href="login.php">Se connecter</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>