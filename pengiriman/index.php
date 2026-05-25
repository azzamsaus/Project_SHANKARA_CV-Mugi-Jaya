<?php
session_start();
require_once '../config/database.php';

// TAMBAHKAN BARIS INI UNTUK MENGUNCI HALAMAN[cite: 1]
include '../config/security_check.php'; 

$content = 'content.php';
include '../layout/main.php';
?>