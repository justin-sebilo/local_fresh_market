<?php
include 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header("Location: ../dashboard.php");
        exit();
    }
}

function redirectIfAdmin() {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
        exit();
    }
}
?>