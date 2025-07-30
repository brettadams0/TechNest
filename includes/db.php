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