<?php
require_once 'config/db.php';

if (isLoggedIn()) {
    logAction($_SESSION['user_id'], 'Logout', 'User logged out');
}

session_destroy();
redirect('login.php');
