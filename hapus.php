<?php
session_start();
include 'koneksi.php';

// Daftar tabel dan primary key-nya
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

// Ambil parameter dari query string
$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';

if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}

$primaryKey = $tables[$table];

// Jika belum ada konfirmasi, tampilkan form konfirmasi
if (!isset($_POST['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo ucwords(str_replace('_', ' ', $table)); ?> - Hapus Data</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; }
            .container { max-width: 500px; margin: auto; padding: 1rem; border: 1px solid #ccc; border-radius: 8px; }
            h1 { color: #d9534f; }
            button { padding: 0.5rem 1rem; margin-right: 1rem; border: none; border-radius: 4px; cursor: pointer; }
            .btn-danger { background-color: #d9534f; color: white; }
            .btn-secondary { background-color: #6c757d; color: white; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Hapus <?php echo ucwords(str_replace('_', ' ', $table)); ?></h1>
            <p>Apakah Anda yakin ingin menghapus data dengan <strong><?php echo htmlspecialchars($primaryKey); ?></strong> = <strong><?php echo htmlspecialchars($id); ?></strong>?</p>
            <form method="post">
                <button type="submit" name="confirm" value="yes" class="btn-danger">Ya, Hapus</button>
                <a href="index.php?table=<?php echo urlencode($table); ?>" class="btn-secondary" style="text-decoration:none; padding:0.5rem 1rem; border-radius:4px;">Batal</a>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Jika sudah konfirmasi hapus
if ($_POST['confirm'] === 'yes') {
    // Escape id untuk keamanan
    $idEscaped = $koneksi->real_escape_string($id);

    // Query hapus data
    $sql = "DELETE FROM `$table` WHERE `$primaryKey` = '$idEscaped' LIMIT 1";

    if ($koneksi->query($sql)) {
        $_SESSION['notif'] = "Data berhasil dihapus.";
    } else {
        $_SESSION['notif'] = "Gagal menghapus data: " . $koneksi->error;
    }

    // Redirect ke index.php
    header("Location: index.php?table=$table");
    exit;
} else {
    // Jika konfirmasi tidak 'yes', redirect ke index.php
    header("Location: index.php?table=$table");
    exit;
}
?>
