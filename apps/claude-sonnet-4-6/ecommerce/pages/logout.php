<?php
// pages/logout.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_destroy();
header('Location: ' . SITE_URL . '/');
exit;
