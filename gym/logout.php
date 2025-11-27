<?php
require_once 'helpers/functions.php';
require_once 'models/Admin.php';

$admin = new Admin();
$admin->logout();

redirect('login.php', 'You have been logged out successfully.', 'success');
?>
