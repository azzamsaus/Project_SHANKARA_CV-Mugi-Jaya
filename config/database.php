<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tracking_proyek');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query Error: " . mysqli_error($conn) . "<br>SQL: $sql");
    }
    return $result;
}

function fetchAll($result) {
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function fetchOne($result) {
    return mysqli_fetch_assoc($result);
}

function getLastId() {
    global $conn;
    return mysqli_insert_id($conn);
}
?> 