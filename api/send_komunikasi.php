<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dari_user = $_SESSION['user_id'];
    $untuk_divisi = $_POST['untuk_divisi'];
    $pesan = addslashes($_POST['pesan']);
    
    $sql = "INSERT INTO komunikasi (dari_user, untuk_divisi, pesan) 
            VALUES ($dari_user, '$untuk_divisi', '$pesan')";
    
    if(query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Pesan terkirim']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan']);
    }
}
?>