<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit();
}

// Jika belum login, redirect ke halaman login
header("Location: auth/login.php");
exit();
?> 