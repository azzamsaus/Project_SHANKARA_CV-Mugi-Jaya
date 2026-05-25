<?php
require_once dirname(__DIR__) . '/config/database.php';
if(!isLoggedIn()) redirect('../auth/login.php');

$id = $_GET['id'];
$pengiriman = fetchOne(query("SELECT * FROM pengiriman WHERE id=$id"));
$detail = fetchAll(query("SELECT * FROM detail_pengiriman WHERE pengiriman_id=$id"));
?>

<!-- Form edit dengan preview foto yang sudah ada -->
<form action="update.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    
    <div class="mb-3">
        <label>Foto Sopir Saat Ini</label>
        <?php if($pengiriman['foto_sopir']): ?>
            <img src="<?php echo $pengiriman['foto_sopir']; ?>" style="max-width: 100px;">
        <?php endif; ?>
        <input type="file" name="foto_sopir" class="form-control">
    </div>
    
    <!-- field lainnya -->
</form>