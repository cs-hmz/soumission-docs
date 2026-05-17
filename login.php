<?php
require_once __DIR__ . '/db.php';

$sessionOptions = [
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
];
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $sessionOptions['cookie_secure'] = true;
}
session_start($sessionOptions);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Veuillez saisir votre email et votre mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Veuillez saisir une adresse email valide.';
    } else {
        try {
            $sql = 'SELECT id, nom, prenom, email, mot_de_passe FROM etudiants WHERE email = :email LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            $role = 'etudiant';

            if (!$user) {
                $sql = 'SELECT id, nom, prenom, email, mot_de_passe FROM administrateurs WHERE email = :email LIMIT 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                $role = $user ? 'admin' : null;
            }

            if (!$user || !isset($user['mot_de_passe']) || !password_verify($password, $user['mot_de_passe'])) {
                $message = 'Email ou mot de passe incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));

                if ($role === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                }
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $message = 'Une erreur est survenue lors de la connexion. Veuillez réessayer plus tard.';
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
    <title>Connexion - Soumission de documents étudiants</title>
    <style>
        :root {
            --bg: #f3f6ff;
            --card: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --error: #dc2626;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #eef2ff 0%, #f8fbff 100%);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        header, footer {
            text-align: center;
            padding: 1rem 1rem;
        }
        header { border-bottom: 1px solid rgba(59, 130, 246, 0.15); }
        footer { margin-top: auto; border-top: 1px solid rgba(59, 130, 246, 0.15); color: var(--muted); font-size: 0.95rem; }
        .page {
            width: min(100%, 920px);
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
            display: flex;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: var(--card);
            border: 1px solid rgba(59, 130, 246, 0.14);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(59, 130, 246, 0.08);
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: clamp(2rem, 3vw, 2.5rem);
        }
        p.lead {
            margin: 0 0 1.5rem;
            color: var(--muted);
            line-height: 1.75;
        }
        .message {
            margin-bottom: 1.5rem;
            padding: 1rem 1.1rem;
            border-radius: 14px;
            background: rgba(220, 38, 38, 0.1);
            color: var(--error);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
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
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }
        .actions { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
        .button { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 0.95rem 1.4rem; border: none; cursor: pointer; font-weight: 600; text-decoration: none; }
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
    <main class="page">
        <div class="card">
            <h1>Connexion</h1>
            <p class="lead">Connectez-vous avec votre adresse email et votre mot de passe.</p>
            <?php if ($message !== ''): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <label>
                    Email
                    <input type="email" name="email" value="<?php echo oldValue('email'); ?>" required>
                </label>
                <label>
                    Mot de passe
                    <input type="password" name="password" required>
                </label>
                <div class="actions">
                    <button type="submit" class="button primary">Se connecter</button>
                    <a class="button secondary" href="register.php">Inscription</a>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
