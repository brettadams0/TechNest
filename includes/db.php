<?php
// db.php
// Database connection helper for TechNest
require_once __DIR__ . '/config.php';

/**
 * Get a PDO connection to the database.
 *
 * @return PDO
 */
function get_db(): PDO
{
    // Fail with a message that names the cause. Without this, an unset
    // environment produces a bare "connection failed" that looks like the
    // database is down rather than unconfigured.
    if (DB_NAME === '' || DB_USER === '') {
        die('Database is not configured. Set DB_NAME, DB_USER and DB_PASS '
            . 'in the environment — see INSTALLATION_GUIDE.md.');
    }

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // In production, log this error instead of displaying it
        die('Database connection failed.');
    }
} 