<?php
// ============================================
// db.php — Connexion à la base de données
// Utilise PDO avec sécurité renforcée
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'etudiants_app');
define('DB_USER', 'root');          // ← à changer en production
define('DB_PASS', '');              // ← à changer en production
define('DB_CHAR', 'utf8mb4');

/**
 * Retourne une connexion PDO singleton.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHAR
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // requêtes préparées réelles
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, ne jamais afficher le message brut
            error_log('Erreur PDO : ' . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données.'
            ]));
        }
    }

    return $pdo;
}