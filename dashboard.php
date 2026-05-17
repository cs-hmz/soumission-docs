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

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'etudiant') {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Étudiant', ENT_QUOTES, 'UTF-8');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
    $allowedMimeTypes = [
        'application/pdf',
        'image/png',
        'image/jpeg',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Erreur lors de l’upload du fichier. Vérifiez le fichier et réessayez.';
        $messageType = 'error';
    } else {
        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
            $message = 'Format non autorisé. Seuls les PDF et les images sont acceptés.';
            $messageType = 'error';
        } else {
            $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $safeName = substr($safeName, 0, 120);
            $storedFileName = sprintf('%s_%s.%s', time(), bin2hex(random_bytes(6)), $extension);
            $uploadDir = __DIR__ . '/uploads';

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                $message = 'Impossible de créer le dossier de destination des fichiers.';
                $messageType = 'error';
            } else {
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedFileName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    try {
                        $sql = 'INSERT INTO fichiers (id_etudiant, nom_fichier, nom_original, type_mime, taille, date_upload) VALUES (:id_etudiant, :nom_fichier, :nom_original, :type_mime, :taille, NOW())';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':id_etudiant' => $userId,
                            ':nom_fichier' => $storedFileName,
                            ':nom_original' => $originalName,
                            ':type_mime' => $mimeType,
                            ':taille' => $file['size'],
                        ]);

                        $message = 'Fichier envoyé avec succès.';
                        $messageType = 'success';
                    } catch (PDOException $e) {
                        if (file_exists($targetPath)) {
                            unlink($targetPath);
                        }
                        $message = 'Impossible d’enregistrer le fichier dans la base de données.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Impossible de déplacer le fichier envoyé.';
                    $messageType = 'error';
                }
            }
        }
    }
}

$documents = [];
try {
    $query = 'SELECT nom_original, nom_fichier, type_mime, taille, date_upload FROM fichiers WHERE id_etudiant = :id_etudiant ORDER BY date_upload DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id_etudiant' => $userId]);
    $documents = $stmt->fetchAll();
} catch (PDOException $e) {
    $documents = [];
}

function formatDate($value) {
    $date = new DateTime($value);
    return $date->format('d/m/Y H:i');
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord étudiant - Soumission de documents</title>
    <style>
        :root {
            --bg: #eef4ff;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #525f7f;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #16a34a;
            --error: #dc2626;
            --border: rgba(59, 130, 246, 0.18);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f5f8ff 0%, #eef4ff 100%);
            color: var(--text);
        }
        header, footer {
            text-align: center;
            padding: 1rem 1rem;
        }
        header { border-bottom: 1px solid var(--border); }
        footer { border-top: 1px solid var(--border); color: var(--muted); font-size: 0.95rem; }
        .container {
            width: min(100%, 1100px);
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }
        .hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .hero h1 { margin: 0; font-size: clamp(2rem, 2.5vw, 2.6rem); }
        .hero p { margin: 0.5rem 0 0; color: var(--muted); }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 18px 40px rgba(59, 130, 246, 0.08);
            margin-bottom: 1.5rem;
        }
        .message {
            padding: 1rem 1.1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message.success { background: rgba(22, 163, 74, 0.14); color: var(--success); border: 1px solid rgba(22, 163, 74, 0.24); }
        .message.error { background: rgba(220, 38, 38, 0.1); color: var(--error); border: 1px solid rgba(220, 38, 38, 0.2); }
        form { display: grid; gap: 1rem; }
        label { display: grid; gap: 0.5rem; color: var(--muted); font-weight: 600; }
        input[type="file"] { width: 100%; border: 1px solid rgba(75, 85, 99, 0.18); border-radius: 14px; padding: 0.85rem 1rem; background: #f8faff; }
        .actions { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 0.95rem 1.4rem; border-radius: 999px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .button.primary { background: var(--primary); color: #ffffff; }
        .button.primary:hover { background: var(--primary-hover); }
        .button.outline { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem 0.9rem; border-bottom: 1px solid rgba(59, 130, 246, 0.12); text-align: left; }
        th { background: #f3f6ff; color: var(--muted); font-weight: 700; }
        tbody tr:nth-child(even) { background: #f8fbff; }
        @media (max-width: 860px) { .container { padding: 1.25rem; } .hero, .actions { flex-direction: column; align-items: stretch; } }
        @media (max-width: 640px) { html { font-size: 15px; } th, td { padding: 0.85rem 0.75rem; } }
    </style>
</head>
<body>
    <header>
        <p>Soumission de documents étudiants</p>
    </header>
    <main class="container">
        <section class="hero">
            <div>
                <h1>Bienvenue, <?php echo $userName; ?></h1>
                <p>Envoyez vos documents et consultez l’historique de vos soumissions.</p>
            </div>
            <div class="actions">
                <a href="logout.php" class="button outline">Se déconnecter</a>
                <a href="generate_pdf.php" class="button primary">Générer le récapitulatif PDF</a>
            </div>
        </section>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Envoyer un nouveau document</h2>
            <p>Formats acceptés : PDF, PNG, JPG, JPEG.</p>
            <form method="post" enctype="multipart/form-data" novalidate>
                <label>
                    Choisir un fichier
                    <input type="file" name="document" accept="application/pdf,image/png,image/jpeg" required>
                </label>
                <button type="submit" class="button primary">Télécharger</button>
            </form>
        </section>

        <section class="card">
            <h2>Documents envoyés</h2>
            <?php if (count($documents) === 0): ?>
                <p>Vous n’avez encore envoyé aucun document.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom du fichier</th>
                            <th>Date d’envoi</th>
                            <th>Type</th>
                            <th>Taille</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo escape($document['nom_original']); ?></td>
                                <td><?php echo escape(formatDate($document['date_upload'])); ?></td>
                                <td><?php echo escape($document['type_mime']); ?></td>
                                <td><?php echo number_format((int)$document['taille'] / 1024, 2, ',', ' '); ?> Ko</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p>© 2026 Soumission Docs • Projet étudiant</p>
    </footer>
</body>
</html>
