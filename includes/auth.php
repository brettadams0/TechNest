<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($username, $password) {
    $db = require_once __DIR__ . '/db.php';
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM admin WHERE user_id = ?');
    $stmt->execute([current_user_id()]);
    return $stmt->fetch() !== false;
}