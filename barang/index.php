<?php
require_once '../config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

$content = 'content.php';
include '../layout/main.php';
?>