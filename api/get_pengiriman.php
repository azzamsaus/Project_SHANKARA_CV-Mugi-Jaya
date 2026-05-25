<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

$id = $_GET['id'] ?? null;

if($id) {
    $pengiriman = fetchOne(query("SELECT * FROM pengiriman WHERE id = $id"));
    echo json_encode(['success' => true, 'pengiriman' => $pengiriman]);
} else {
    echo json_encode(['success' => false]);
}
?>