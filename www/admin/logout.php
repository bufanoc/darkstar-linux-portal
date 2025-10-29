<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->logoutAdmin();

header('Location: login.php');
exit;
