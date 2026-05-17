<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumission de documents étudiants</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #1a1a1a;
            --muted: #58607c;
            --accent: #3366ff;
            --accent-hover: #254fcc;
            --border: rgba(50, 84, 255, 0.15);
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #eef3ff 0%, #f9fbff 100%);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        header, footer {
            text-align: center;
            padding: 1.25rem 1rem;
            background: transparent;
        }
        header {
            border-bottom: 1px solid var(--border);
        }
        footer {
            border-top: 1px solid var(--border);
            margin-top: auto;
            font-size: 0.95rem;
            color: var(--muted);
        }
        .container {
            width: min(100%, 1024px);
            margin: 0 auto;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
        }
        .hero {
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 24px 60px rgba(38, 71, 164, 0.08);
            display: grid;
            gap: 1.75rem;
        }
        .hero h1 {
            margin: 0;
            font-size: clamp(2.25rem, 3vw, 3rem);
            line-height: 1.05;
        }
        .hero p {
            margin: 0;
            max-width: 42rem;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.9;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            padding: 0.95rem 1.4rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }
        .button.primary {
            background: var(--accent);
            color: #ffffff;
            box-shadow: 0 16px 32px rgba(51, 102, 255, 0.18);
        }
        .button.primary:hover,
        .button.primary:focus-visible {
            background: var(--accent-hover);
            transform: translateY(-1px);
            outline: none;
        }
        .button.secondary {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
        }
        .button.secondary:hover,
        .button.secondary:focus-visible {
            background: rgba(51, 102, 255, 0.08);
            transform: translateY(-1px);
            outline: none;
        }
        .highlight {
            color: var(--accent);
        }
        @media (max-width: 640px) {
            .hero {
                padding: 1.75rem;
            }
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <p>Application de soumission de documents étudiants</p>
    </header>
    <main class="container">
        <section class="hero" aria-labelledby="hero-title">
            <div>
                <p class="highlight">Bienvenue</p>
                <h1 id="hero-title">Simplifiez la remise de vos travaux et documents.</h1>
                <p>Envoyez facilement vos documents, gérez vos soumissions et accédez à votre espace étudiant sécurisé. Cette plateforme est conçue pour être claire, rapide et disponible depuis tous vos appareils.</p>
            </div>
            <div class="actions">
                <a class="button primary" href="register.php">Inscription</a>
                <a class="button secondary" href="login.php">Connexion</a>
            </div>
        </section>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
