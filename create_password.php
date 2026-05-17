<?php
require_once __DIR__ . '/db.php';

$message = '';
$messageType = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($email === '' || $password === '' || $confirmPassword === '') {
        $message = 'Merci de remplir tous les champs.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'L’adresse email saisie n’est pas valide.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Les mots de passe ne correspondent pas.';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $messageType = 'error';
    } else {
        try {
            $sql = 'SELECT id, est_active FROM etudiants WHERE email = :email LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $message = 'Aucun compte trouvé pour cette adresse email.';
                $messageType = 'error';
            } elseif ((int)$user['est_active'] !== 1) {
                $message = 'Ce compte n’est pas encore activé. Vérifiez votre code d’activation.';
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = 'UPDATE etudiants SET mot_de_passe = :mot_de_passe WHERE id = :id';
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':mot_de_passe' => $hashedPassword,
                    ':id' => $user['id'],
                ]);

                if ($updateStmt->rowCount() > 0) {
                    $message = 'Votre mot de passe a été enregistré. Vous pouvez maintenant vous connecter.';
                    $messageType = 'success';
                    $success = true;
                } else {
                    $message = 'Aucune modification n’a été effectuée. Votre mot de passe pourrait déjà être enregistré.';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Une erreur est survenue lors de l’enregistrement du mot de passe.';
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
    <title>Créer un mot de passe - Soumission de documents étudiants</title>
    <style>
        :root {
            --bg: #f4f7ff;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --error: #dc2626;
            --success: #16a34a;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #eef2ff 0%, #f4f7ff 100%);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        header, footer {
            text-align: center;
            padding: 1rem 1rem;
        }
        header {
            border-bottom: 1px solid rgba(37, 99, 235, 0.15);
        }
        footer {
            margin-top: auto;
            border-top: 1px solid rgba(37, 99, 235, 0.15);
            color: var(--muted);
            font-size: 0.95rem;
        }
        .container {
            width: min(100%, 960px);
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
            display: flex;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid rgba(37, 99, 235, 0.14);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(37, 99, 235, 0.08);
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: clamp(2rem, 3vw, 2.5rem);
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
        .message.success { background: rgba(22, 163, 74, 0.14); color: var(--success); border: 1px solid rgba(22, 163, 74, 0.24); }
        .message.error { background: rgba(220, 38, 38, 0.1); color: var(--error); border: 1px solid rgba(220, 38, 38, 0.2); }
        form { display: grid; gap: 1rem; }
        label { display: grid; gap: 0.5rem; color: var(--muted); font-size: 0.95rem; }
        input {
            width: 100%;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(75, 85, 99, 0.18);
            border-radius: 16px;
            background: #f8fbff;
            font-size: 1rem;
            color: var(--text);
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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
            font-weight: 600;
            text-decoration: none;
        }
        .button.primary { background: var(--primary); color: #ffffff; }
        .button.primary:hover { background: var(--primary-hover); }
        .button.secondary { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
        @media (max-width: 640px) { .actions { flex-direction: column; align-items: stretch; } .button { width: 100%; } }
    </style>
</head>
<body>
    <header>
        <p>Soumission de documents étudiants</p>
    </header>
    <main class="container">
        <div class="card">
            <h1>Créer votre mot de passe</h1>
            <p class="lead">Entrez votre adresse email et choisissez un mot de passe pour activer votre compte.</p>

            <?php if ($message !== ''): ?>
                <div class="message <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="actions">
                    <a class="button primary" href="login.php">Aller à la connexion</a>
                </div>
            <?php else: ?>
                <form method="post" novalidate>
                    <label>
                        Email
                        <input type="email" name="email" value="<?php echo oldValue('email'); ?>" required>
                    </label>
                    <label>
                        Mot de passe
                        <input type="password" name="password" required>
                    </label>
                    <label>
                        Confirmation du mot de passe
                        <input type="password" name="confirm_password" required>
                    </label>
                    <div class="actions">
                        <button class="button primary" type="submit">Enregistrer</button>
                        <a class="button secondary" href="login.php">Connexion</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
