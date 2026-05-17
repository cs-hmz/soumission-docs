<?php
require_once __DIR__ . '/db.php';

$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $studentSql = 'SELECT id, nom, prenom, email, apogee, est_active FROM etudiants ORDER BY nom, prenom';
    $stmt = $pdo->query($studentSql);
    $students = $stmt->fetchAll();

    $filesSql = 'SELECT id_etudiant, nom_original, type_mime, taille, date_upload FROM fichiers ORDER BY date_upload DESC';
    $stmt = $pdo->query($filesSql);
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<h1>Erreur</h1><p>Impossible de récupérer les données.</p>';
    exit;
}

$fileMap = [];
foreach ($files as $file) {
    $fileMap[$file['id_etudiant']][] = $file;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', ' ') . ' Mo';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', ' ') . ' Ko';
    }
    return $bytes . ' o';
}

function formatDate($value) {
    return (new DateTime($value))->format('d/m/Y H:i');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tableau de bord</title>
    <style>
        :root {
            --bg: #f4f6ff;
            --card: #ffffff;
            --text: #111827;
            --muted: #4b5563;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --border: rgba(37, 99, 235, 0.18);
            --success: #16a34a;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: linear-gradient(180deg, #eef2ff 0%, #f4f6ff 100%); color: var(--text); }
        header, footer { text-align: center; padding: 1rem 1rem; }
        header { border-bottom: 1px solid var(--border); }
        footer { border-top: 1px solid var(--border); color: var(--muted); font-size: 0.95rem; }
        .container { width: min(100%, 1180px); margin: 0 auto; padding: 2rem 1rem 3rem; }
        .hero { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
        .hero h1 { margin: 0; font-size: clamp(2rem, 2.5vw, 2.6rem); }
        .hero p { margin: 0.5rem 0 0; color: var(--muted); }
        .actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 0.95rem 1.4rem; border-radius: 999px; color: #ffffff; background: var(--primary); border: none; text-decoration: none; font-weight: 600; }
        .button:hover { background: var(--primary-hover); }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 1.75rem; box-shadow: 0 18px 40px rgba(37, 99, 235, 0.08); margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; border-bottom: 1px solid rgba(75, 85, 99, 0.12); text-align: left; vertical-align: top; }
        th { background: #f3f4f6; color: var(--muted); }
        tbody tr:nth-child(even) { background: #fbfbfb; }
        .file-list { display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none; }
        .file-item { background: #f8fbff; border-radius: 14px; padding: 0.75rem 0.95rem; border: 1px solid rgba(37, 99, 235, 0.08); font-size: 0.95rem; }
        .badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.8rem; color: #fff; background: var(--success); }
        .status { font-weight: 700; color: #1f2937; }
        .status.inactive { color: #f59e0b; }
        .status.active { color: var(--success); }
        @media (max-width: 860px) { .hero { flex-direction: column; align-items: flex-start; } table, th, td { display: block; width: 100%; } th { border-bottom: none; } td { border-bottom: 1px solid rgba(75, 85, 99, 0.12); } td::before { content: attr(data-label); font-weight: 700; display: block; margin-bottom: 0.35rem; } }
    </style>
</head>
<body>
    <header>
        <p>Administration - Soumission de documents étudiants</p>
    </header>
    <main class="container">
        <section class="hero">
            <div>
                <h1>Tableau de bord administrateur</h1>
                <p>Liste des étudiants inscrits et de leurs fichiers téléversés.</p>
            </div>
            <div class="actions">
                <a class="button" href="admin_pdf.php">Exporter en PDF global</a>
            </div>
        </section>

        <section class="card">
            <h2>Étudiants et documents</h2>
            <table>
                <thead>
                    <tr>
                        <th>Apogée</th>
                        <th>Étudiant</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Fichiers</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php $studentFiles = $fileMap[$student['id']] ?? []; ?>
                        <tr>
                            <td data-label="Apogée"><?php echo escape($student['apogee']); ?></td>
                            <td data-label="Étudiant"><?php echo escape($student['prenom'] . ' ' . $student['nom']); ?></td>
                            <td data-label="Email"><?php echo escape($student['email']); ?></td>
                            <td data-label="Statut"><span class="status <?php echo (int)$student['est_active'] === 1 ? 'active' : 'inactive'; ?>"><?php echo (int)$student['est_active'] === 1 ? 'Actif' : 'Inactif'; ?></span></td>
                            <td data-label="Fichiers">
                                <?php if (count($studentFiles) === 0): ?>
                                    <span>Aucun fichier</span>
                                <?php else: ?>
                                    <ul class="file-list">
                                        <?php foreach ($studentFiles as $file): ?>
                                            <li class="file-item">
                                                <strong><?php echo escape($file['nom_original']); ?></strong><br>
                                                <?php echo escape($file['type_mime']); ?> — <?php echo escape(formatSize($file['taille'])); ?><br>
                                                <small><?php echo escape(formatDate($file['date_upload'])); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
