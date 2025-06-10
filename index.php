<?php
// Memulai session untuk menyimpan notifikasi
session_start();
// Menghubungkan ke database
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

// Mengambil tabel aktif dari parameter URL, default 'restoran'
$activeTable = $_GET['table'] ?? 'restoran';
// Validasi tabel (jika tidak ada dalam daftar, default ke restoran)
if (!array_key_exists($activeTable, $tables)) {
    $activeTable = 'restoran';
}

// Mengambil notifikasi dari session (jika ada)
$notif = $_SESSION['notif'] ?? '';
// Menghapus notifikasi setelah ditampilkan
unset($_SESSION['notif']);

// Fungsi untuk mengamankan output (mencegah serangan XSS)
function e($str) {
    return htmlspecialchars($str);
}

// Fungsi untuk mengkapitalisasi setiap kata dalam string
function capitalizeWords($str) {
    return ucwords(strtolower($str));
}

// Mengambil data dari tabel aktif
$primaryKey = $tables[$activeTable];  // Primary key tabel yang aktif
$dataRows = [];  // Menampung data baris dari tabel
$columns = [];   // Menampung nama kolom tabel

try {
    // Menyiapkan query untuk mengambil semua data dari tabel aktif
    $stmt = $koneksi->prepare("SELECT * FROM `$activeTable`");
    $stmt->execute();
    $result = $stmt->get_result();

    // Mengambil metadata kolom tabel
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        $columns[] = $field->name;  // Menyimpan nama kolom
    }

    // Mengambil data baris per baris
    while ($row = $result->fetch_assoc()) {
        $dataRows[] = $row;  // Menambahkan baris data ke array
    }
} catch (Exception $e) {
    // Menampilkan pesan error jika terjadi kesalahan
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cimehong Resto - Sistem Manajemen</title>
    <style>
        /* GLOBAL STYLES */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            padding: 20px;
            margin: 0;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
        }
        
        /* TAB NAVIGATION STYLES */
        .tabs {
            overflow: hidden;
            border-bottom: 2px solid #3498db;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }
        .tabs button {
            background-color: #ecf0f1;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 8px 8px 0 0;
            color: #34495e;
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tabs button:hover:not(.active) {
            background-color: #d6eaf8;
            color: #2980b9;
        }
        .tabs button.active {
            background-color: #3498db;
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 8px rgba(52,152,219,0.5);
        }
        
        /* NOTIFICATION STYLES */
        .notif {
            max-width: 900px;
            margin: 0 auto 25px auto;
            padding: 15px 20px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(40,167,69,0.3);
            text-align: center;
        }
        
        /* BUTTON STYLES */
        .btn {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 10px;
            font-weight: 600;
            transition: background-color 0.3s;
            user-select: none;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 3px 6px rgba(40,167,69,0.4);
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .btn-edit {
            background-color: #007bff;
            color: white;
            margin-right: 6px;
            box-shadow: 0 2px 5px rgba(0,123,255,0.4);
        }
        .btn-edit:hover {
            background-color: #0056b3;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 5px rgba(220,53,69,0.4);
        }
        .btn-delete:hover {
            background-color: #a71d2a;
        }
        
        /* TABLE STYLES */
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #e1e4e8;
        }
        th {
            background-color: #3498db;
            color: white;
            font-weight: 700;
            text-align: center;
            user-select: none;
        }
        tbody tr:nth-child(even) {
            background-color: #f6f8fa;
        }
        tbody tr:hover {
            background-color: #d6eaf8;
            cursor: default;
        }
        
        /* SPECIAL STYLES FOR MENU IMAGES */
        img.menu-image {
            max-width: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        img.menu-image:hover {
            transform: scale(1.05);
        }
        
        /* RESPONSIVE STYLES */
        @media (max-width: 768px) {
            .tabs {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            table, th, td {
                font-size: 13px;
            }
            .btn {
                font-size: 12px;
                padding: 6px 10px;
            }
            img.menu-image {
                max-width: 70px;
            }
        }
    </style>
    <script>
        // Fungsi konfirmasi penghapusan data
        function confirmDelete(id, table) {
            if (confirm('Apakah anda yakin menghapus data dengan id ' + id + ' pada tabel ' + table + '?')) {
                // Redirect ke halaman hapus dengan parameter
                window.location.href = 'hapus.php?table=' + table + '&id=' + encodeURIComponent(id);
            }
        }
        
        // Fungsi untuk berpindah tab tabel
        function openTab(tableName) {
            window.location.href = 'index.php?table=' + tableName;
        }
    </script>
</head>
<body>

<h1>Sistem Manajemen Restoran Cimehong</h1>

<?php if ($notif): ?>
    <!-- Menampilkan notifikasi jika ada -->
    <div class="notif"><?php echo e($notif); ?></div>
<?php endif; ?>

<!-- Navigasi tab untuk memilih tabel -->
<div class="tabs" role="tablist" aria-label="Navigasi Tabel">
    <?php foreach ($tables as $tableName => $pk): ?>
        <?php
            // Penyesuaian label tab khusus (bahan_baku jadi Bahan Baku)
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
        <!-- Tombol tab dengan status aktif(yang sedang dibuka)/non-aktif -->
        <button 
            class="<?php echo ($tableName === $activeTable) ? 'active' : ''; ?>" 
            onclick="openTab('<?php echo e($tableName); ?>')" 
            role="tab" 
            aria-selected="<?php echo ($tableName === $activeTable) ? 'true' : 'false'; ?>"
            tabindex="<?php echo ($tableName === $activeTable) ? '0' : '-1'; ?>"
        >
            <?php echo e($tabLabel); ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Judul tabel aktif(yang sedang dibuka) -->
<h2 style="text-align:center; margin-bottom: 15px;">Data Tabel: <?php echo ucfirst(str_replace('_', ' ', $activeTable)); ?></h2>
<!-- Tombol tambah data -->
<div style="text-align:center;">
    <a href="tambah.php?table=<?php echo e($activeTable); ?>" class="btn btn-add" aria-label="Tambah data pada tabel <?php echo e($activeTable); ?>">Tambah Data</a>
</div>

<!-- Tabel data utama -->
<table role="grid" aria-readonly="true" aria-label="Data tabel <?php echo e($activeTable); ?>">
    <thead>
        <tr>
            <!-- Header kolom: mengubah format judul_tab menjadi Judul Tab -->
            <?php foreach ($columns as $col): ?>
                <th scope="col"><?php echo e(capitalizeWords(str_replace('_', ' ', $col))); ?></th>
            <?php endforeach; ?>
            <th scope="col" style="text-align:center;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($dataRows) === 0): ?>
            <!-- Pesan jika tidak ada data -->
            <tr><td colspan="<?php echo count($columns) + 1; ?>" style="text-align:center; font-style: italic;">Tidak ada data.</td></tr>
        <?php else: ?>
            <!-- Loop untuk setiap baris data -->
            <?php foreach ($dataRows as $row): ?>
                <tr>
                    <!-- Loop untuk setiap kolom -->
                    <?php foreach ($columns as $col): ?>
                        <td>
                            <?php
                            // Format khusus untuk kolom gaji karyawan
                            if ($activeTable === 'karyawan' && $col === 'gaji_karyawan') {
                                echo 'Rp ' . number_format($row[$col], 0, ',', '.');
                            }
                            // Tampilkan gambar untuk menu
                            elseif ($activeTable === 'menu' && $col === 'gambar_menu') {
                                if (!empty($row[$col]) && file_exists('uploads/' . $row[$col])) {
                                    echo '<img src="uploads/' . e($row[$col]) . '" alt="Gambar Menu" class="menu-image">';
                                } else {
                                    echo '-';
                                }
                            }
                            // Default: tampilkan data biasa
                            else {
                                echo e($row[$col]);
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <!-- Tombol aksi edit dan hapus -->
                    <td style="text-align:center;">
                        <a href="edit.php?table=<?php echo e($activeTable); ?>&id=<?php echo urlencode($row[$primaryKey]); ?>" class="btn btn-edit" aria-label="Edit data dengan ID <?php echo e($row[$primaryKey]); ?>">Edit</a>
                        <a href="javascript:void(0);" onclick="confirmDelete('<?php echo e($row[$primaryKey]); ?>', '<?php echo e($activeTable); ?>')" class="btn btn-delete" aria-label="Hapus data dengan ID <?php echo e($row[$primaryKey]); ?>">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
