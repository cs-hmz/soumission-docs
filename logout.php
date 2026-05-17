<?php
session_start();

// Détruire toutes les données de session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url=accueil.php">
    <title>Déconnexion</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f0f4ff 0%, #e9efff 100%);
            color: #1f2937;
        }
        .card {
            background: #ffffff;
            padding: 2rem 2.2rem;
            border-radius: 24px;
            border: 1px solid rgba(59, 130, 246, 0.18);
            box-shadow: 0 20px 50px rgba(59, 130, 246, 0.08);
            max-width: 420px;
            text-align: center;
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.75rem;
        }
        p {
            margin: 0;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Déconnexion réussie</h1>
        <p>Vous êtes redirigé vers la page d’accueil...</p>
    </div>
</body>
</html>
