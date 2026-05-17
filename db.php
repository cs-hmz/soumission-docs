<?php
/**
 * Database connection helper using PDO.
 *
 * Configure your database credentials below.
 */
function getConnection(): PDO
{
    $host = 'localhost';
    $dbname = 'soumission_docs';
    $user = 'root';
    $password = '';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Erreur de connexion à la base de données. Vérifiez la configuration de db.php.';
        exit;
    }
}

/** @var PDO $pdo */
$pdo = getConnection();
