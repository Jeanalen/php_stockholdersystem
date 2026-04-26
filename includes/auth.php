<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>