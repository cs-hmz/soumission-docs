<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $apogee = trim($_POST['apogee'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nom === '' || $prenom === '' || $apogee === '' || $email === '') {
        $message = 'Merci de remplir tous les champs du formulaire.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'L’adresse email saisie n’est pas valide.';
        $messageType = 'error';
    } elseif (!preg_match('/^[0-9]+$/', $apogee)) {
        $message = 'Le numéro Apogée doit contenir uniquement des chiffres.';
        $messageType = 'error';
    } else {
        try {
            $codeActivation = random_int(10000000, 99999999);

            $sql = 'INSERT INTO etudiants (nom, prenom, apogee, email, code_activation, est_active) VALUES (:nom, :prenom, :apogee, :email, :code_activation, 0)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':apogee' => $apogee,
                ':email' => $email,
                ':code_activation' => $codeActivation,
            ]);

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'no-reply@example.com';
            $mail->Password = 'votre_mot_de_passe_smtp';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('no-reply@example.com', 'Soumission Docs');
            $mail->addAddress($email, $prenom . ' ' . $nom);
            $mail->Subject = 'Activation de votre compte étudiant';
            $mail->isHTML(true);
            $mail->Body = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>' .
                '<p>Merci de vous être inscrit. Votre code d’activation est :</p>' .
                '<p><strong>' . $codeActivation . '</strong></p>' .
                '<p>Utilisez ce code pour activer votre compte dans la plateforme.</p>' .
                '<p>Cordialement,<br>Équipe Soumission Docs</p>';
            $mail->AltBody = 'Bonjour ' . $prenom . ",\n\nVotre code d’activation est : " . $codeActivation . "\n\nCordialement,\nÉquipe Soumission Docs";
            $mail->send();

            $message = 'Inscription réussie ! Un email d’activation a été envoyé à ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'L’inscription a échoué lors de l’envoi de l’email : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $messageType = 'error';
        } catch (PDOException $e) {
            $message = 'Impossible d’enregistrer votre inscription. Vérifiez vos informations et réessayez.';
            $messageType = 'error';
        }
    }
}

function oldValue($key) {
    return htmlspecialchars($_POST[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Soumission de documents étudiants</title>
    <style>
        :root {
            --bg: #eef2fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #5b6b82;
            --primary: #2f65ff;
            --primary-hover: #244eca;
            --error: #d64545;
            --success: #2a9d8f;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f7fbff 0%, #eef2fb 100%);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        header, footer {
            text-align: center;
            padding: 1rem 1rem;
        }
        header {
            border-bottom: 1px solid rgba(47, 101, 255, 0.14);
        }
        footer {
            margin-top: auto;
            border-top: 1px solid rgba(47, 101, 255, 0.14);
            color: var(--muted);
            font-size: 0.95rem;
        }
        .page {
            width: min(100%, 900px);
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }
        .card {
            background: var(--card);
            border: 1px solid rgba(47, 101, 255, 0.12);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(47, 101, 255, 0.08);
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: clamp(2rem, 3vw, 2.6rem);
        }
        p.lead {
            margin: 0 0 1.75rem;
            color: var(--muted);
            line-height: 1.75;
        }
        .message {
            padding: 1rem 1.1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message.success { background: rgba(42, 157, 143, 0.16); color: var(--success); border: 1px solid rgba(42, 157, 143, 0.25); }
        .message.error { background: rgba(214, 69, 69, 0.1); color: var(--error); border: 1px solid rgba(214, 69, 69, 0.2); }
        form {
            display: grid;
            gap: 1rem;
        }
        label {
            display: grid;
            gap: 0.5rem;
            font-size: 0.95rem;
            color: var(--muted);
        }
        input {
            width: 100%;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(79, 95, 132, 0.18);
            border-radius: 16px;
            background: #f8fbff;
            font-size: 1rem;
            color: var(--text);
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(47, 101, 255, 0.12);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.95rem 1.4rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        .button.primary {
            background: var(--primary);
            color: #ffffff;
        }
        .button.primary:hover { background: var(--primary-hover); }
        .button.secondary {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        @media (max-width: 640px) {
            .actions { flex-direction: column; align-items: stretch; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    <header>
        <p>Soumission de documents étudiants</p>
    </header>
    <main class="page">
        <div class="card">
            <h1>Créer votre compte</h1>
            <p class="lead">Remplissez le formulaire ci-dessous pour vous inscrire sur la plateforme. Un code d’activation vous sera envoyé par email.</p>

            <?php if ($message !== ''): ?>
                <div class="message <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <label>
                    Nom
                    <input type="text" name="nom" value="<?php echo oldValue('nom'); ?>" required>
                </label>
                <label>
                    Prénom
                    <input type="text" name="prenom" value="<?php echo oldValue('prenom'); ?>" required>
                </label>
                <label>
                    Numéro Apogée
                    <input type="text" name="apogee" value="<?php echo oldValue('apogee'); ?>" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="<?php echo oldValue('email'); ?>" required>
                </label>
                <div class="actions">
                    <button class="button primary" type="submit">Envoyer</button>
                    <a class="button secondary" href="login.php">Connexion</a>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
