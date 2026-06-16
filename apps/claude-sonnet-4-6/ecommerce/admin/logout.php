<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['admin_id']);
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
