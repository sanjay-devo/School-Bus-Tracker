<?php
require_once 'config.php';
require_once 'includes/auth.php';

logoutUser();
header('Location: /index.php');
exit;
?>
