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

// Ambil tabel aktif dari query string, default restoran
$activeTable = $_GET['table'] ?? 'restoran';
if (!array_key_exists($activeTable, $tables)) {
    $activeTable = 'restoran';
}

// Ambil notifikasi dari session
$notif = $_SESSION['notif'] ?? '';
unset($_SESSION['notif']);

// Fungsi escape output
function e($str) {
    return htmlspecialchars($str);
}

// Fungsi untuk kapitalisasi huruf pertama setiap kata
function capitalizeWords($str) {
    return ucwords(strtolower($str));
}

// Ambil data dari tabel aktif
$primaryKey = $tables[$activeTable];
$dataRows = [];
$columns = [];

try {
    $stmt = $koneksi->prepare("SELECT * FROM `$activeTable`");
    $stmt->execute();
    $result = $stmt->get_result();

    // Ambil kolom
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        $columns[] = $field->name;
    }

    // Ambil data
    while ($row = $result->fetch_assoc()) {
        $dataRows[] = $row;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cimehong Resto</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .tabs { overflow: hidden; border-bottom: 1px solid #ccc; margin-bottom: 20px; }
        .tabs button {
            background-color: #f1f1f1; border: none; outline: none; cursor: pointer;
            padding: 10px 20px; float: left; transition: 0.3s; font-size: 16px;
            border-top-left-radius: 5px; border-top-right-radius: 5px;
            margin-right: 2px;
        }
        .tabs button.active { background-color: #ddd; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: middle; }
        th { background-color: #eee; text-align: center; } /* Rata tengah judul kolom */
        .notif {
            padding: 10px; background-color: #d4edda; color: #155724;
            border: 1px solid #c3e6cb; margin-bottom: 20px; border-radius: 5px;
        }
        .btn {
            padding: 6px 12px; text-decoration: none;
            border-radius: 4px; font-size: 14px;
            display: block;          /* Membuat tombol tampil vertikal */
            margin-bottom: 5px;      /* Memberi jarak antar tombol */
            width: 70px;
            text-align: center;
        }
        .btn-add { background-color: #28a745; color: white; display: inline-block; margin-bottom: 10px; width: auto; }
        .btn-edit { background-color: #007bff; color: white; }
        .btn-delete { background-color: #dc3545; color: white; }
        img.menu-image {
            max-width: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
    </style>
    <script>
        function confirmDelete(id, table) {
            if (confirm('Apakah anda yakin menghapus data dengan id ' + id + ' pada tabel ' + table + '?')) {
                window.location.href = 'hapus.php?table=' + table + '&id=' + encodeURIComponent(id);
            }
        }
        function openTab(tableName) {
            window.location.href = 'index.php?table=' + tableName;
        }
    </script>
</head>
<body>

<h1>Sistem Manajemen Restoran Cimehong</h1>

<?php if ($notif): ?>
    <div class="notif"><?php echo e($notif); ?></div>
<?php endif; ?>

<div class="tabs">
    <?php foreach ($tables as $tableName => $pk): ?>`
        <?php
            // Penyesuaian nama tab khusus sesuai permintaan
            if ($tableName === 'bahan_baku') {
                $tabLabel = 'Bahan Baku';
            } elseif ($tableName === 'detail_pesanan') {
                $tabLabel = 'Detail Pesanan';
            } elseif ($tableName === 'meja_reservasi') {
                $tabLabel = 'Meja Reservasi';
            } else {
                $tabLabel = ucfirst(str_replace('_', ' ', $tableName));
            }
        ?>
        <button class="<?php echo ($tableName === $activeTable) ? 'active' : ''; ?>" onclick="openTab('<?php echo e($tableName); ?>')">
            <?php echo e($tabLabel); ?>
        </button>
    <?php endforeach; ?>
</div>

<h2>Data Tabel: <?php echo ucfirst(str_replace('_', ' ', $activeTable)); ?></h2>
<a href="tambah.php?table=<?php echo e($activeTable); ?>" class="btn btn-add">Tambah Data</a>

<table>
    <thead>
        <tr>
            <?php foreach ($columns as $col): ?>
                <th><?php echo e(capitalizeWords(str_replace('_', ' ', $col))); ?></th>
            <?php endforeach; ?>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($dataRows) === 0): ?>
            <tr><td colspan="<?php echo count($columns) + 1; ?>">Tidak ada data.</td></tr>
        <?php else: ?>
            <?php foreach ($dataRows as $row): ?>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <td>
                            <?php
                            // Jika tabel karyawan dan kolom gaji_karyawan, tambahkan prefix Rp dan format angka
                            if ($activeTable === 'karyawan' && $col === 'gaji_karyawan') {
                                echo 'Rp ' . number_format($row[$col], 0, ',', '.');
                            }
                            // Jika kolom adalah gambar_menu, tampilkan gambar dari folder uploads
                            elseif ($activeTable === 'menu' && $col === 'gambar_menu') {
                                if (!empty($row[$col]) && file_exists('uploads/' . $row[$col])) {
                                    echo '<img src="uploads/' . e($row[$col]) . '" alt="Gambar Menu" class="menu-image">';
                                } else {
                                    echo '-'; // atau bisa diganti dengan teks "Tidak ada gambar"
                                }
                            }
                            else {
                                echo e($row[$col]);
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <a href="edit.php?table=<?php echo e($activeTable); ?>&id=<?php echo urlencode($row[$primaryKey]); ?>" class="btn btn-edit">Edit</a>
                        <a href="javascript:void(0);" onclick="confirmDelete('<?php echo e($row[$primaryKey]); ?>', '<?php echo e($activeTable); ?>')" class="btn btn-delete">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
