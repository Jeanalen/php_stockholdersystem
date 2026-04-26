<?php
include 'includes/config.php';
include 'includes/auth.php';
logActivity($conn, 'LOGOUT', "Admin logged out of the system.");
session_destroy();
header("Location: login.php");
?>