<?php
require_once __DIR__ . '/../db.php';
unset($_SESSION['user']);
redirect(BASE_URL . '/index.php');
