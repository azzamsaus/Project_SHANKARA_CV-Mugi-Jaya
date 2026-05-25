<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if($user_role == 'engineering') {
    $sql = "SELECT k.*, u.nama_lengkap as dari_user 
            FROM komunikasi k 
            JOIN users u ON k.dari_user = u.id 
            WHERE k.untuk_divisi = 'engineering' OR k.untuk_divisi = 'semua'
            ORDER BY k.created_at DESC LIMIT 20";
} else {
    $sql = "SELECT k.*, u.nama_lengkap as dari_user 
            FROM komunikasi k 
            JOIN users u ON k.dari_user = u.id 
            WHERE k.untuk_divisi = 'produksi' OR k.untuk_divisi = 'semua' OR k.untuk_user = $user_id
            ORDER BY k.created_at DESC LIMIT 20";
}

$messages = fetchAll(query($sql));

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>