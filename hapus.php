<?php
session_start();
include 'koneksi.php';

$tables = [
    'restoran' => 'id_restoran',
    'bahan_baku' => 'id_bahan',
    'karyawan' => 'id_karyawan',
    'menu' => 'id_menu',
    'meja_reservasi' => 'id_meja',
    'pelanggan' => 'id_pelanggan',
    'inventaris' => 'id_inventaris',
    'member' => 'id_member',
    'pesanan' => 'id_pesanan',
    'detail_pesanan' => 'id_detail_pesanan',
    'resep' => 'id_resep',
    'transaksi' => 'id_transaksi',
    'ulasan' => 'id_ulasan',
];

function formatTableName($table) {
    return ucwords(str_replace('_', ' ', $table));
}

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';

if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}
if (!$id) {
    die("ID tidak ditemukan.");
}

$primaryKey = $tables[$table];
$formattedTableName = formatTableName($table);

// Jika belum ada konfirmasi, tampilkan halaman konfirmasi
if (!isset($_POST['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Konfirmasi Hapus Data</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .container { max-width: 500px; margin: auto; text-align: center; }
            button { padding: 10px 20px; margin: 10px; cursor: pointer; }
            .btn-yes { background-color: #dc3545; color: white; border: none; }
            .btn-no { background-color: #6c757d; color: white; border: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Konfirmasi Hapus Data</h2>
            <p>Apakah Anda yakin ingin menghapus id <strong><?php echo htmlspecialchars($id); ?></strong> pada tabel <strong><?php echo $formattedTableName; ?></strong>?</p>
            <form method="post">
                <button type="submit" name="confirm" value="yes" class="btn-yes">Ya</button>
                <button type="submit" name="confirm" value="no" class="btn-no">Batal</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Jika user memilih batal, redirect ke halaman index
if ($_POST['confirm'] === 'no') {
    header("Location: index.php?table=$table");
    exit;
}

// Jika user memilih ya, lakukan penghapusan
$stmt = $koneksi->prepare("DELETE FROM `$table` WHERE `$primaryKey` = ?");
$stmt->bind_param('s', $id);

if ($stmt->execute()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Data Berhasil Dihapus</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
            a { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; }
        </style>
    </head>
    <body>
        <h2>Data Berhasil Dihapus</h2>
        <p>Id <strong><?php echo htmlspecialchars($id); ?></strong> pada tabel <strong><?php echo $formattedTableName; ?></strong> berhasil dihapus.</p>
        <a href="index.php?table=<?php echo htmlspecialchars($table); ?>">&laquo; Kembali ke Daftar <?php echo $formattedTableName; ?></a>
    </body>
    </html>
    <?php
    exit;
} else {
    die("Gagal menghapus data: " . $stmt->error);
}
