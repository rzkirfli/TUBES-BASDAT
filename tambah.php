<?php
session_start();
include 'koneksi.php';

function capitalizeWords($str) {
    return ucwords(str_replace('_', ' ', strtolower($str)));
}

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

// Definisikan foreign key dan tabel referensinya
$foreignKeys = [
    'id_restoran' => 'restoran',
    'id_bahan' => 'bahan_baku',
    'id_karyawan' => 'karyawan',
    'id_menu' => 'menu',
    'id_meja' => 'meja_reservasi',
    'id_pelanggan' => 'pelanggan',
    'id_member' => 'member',
    'id_pesanan' => 'pesanan',
    'id_detail_pesanan' => 'detail_pesanan',
    'id_resep' => 'resep',
    'id_transaksi' => 'transaksi',
    'id_ulasan' => 'ulasan',
];

$table = $_GET['table'] ?? '';
if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}

$primaryKey = $tables[$table];

// Ambil struktur kolom
$columns = [];
$stmt = $koneksi->prepare("SHOW COLUMNS FROM `$table`");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi foreign key
    foreach ($foreignKeys as $fkField => $refTable) {
        if (isset($_POST[$fkField]) && $_POST[$fkField] !== '') {
            $fkValue = $_POST[$fkField];
            $checkStmt = $koneksi->prepare("SELECT COUNT(*) as count FROM `$refTable` WHERE `$fkField` = ?");
            $checkStmt->bind_param('s', $fkValue);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result()->fetch_assoc();
            if ($checkResult['count'] == 0) {
                $error = capitalizeWords($fkField) . " tidak ditemukan atau belum dibuat.";
                break;
            }
        }
    }

    // Validasi dan proses upload gambar jika ada kolom gambar_menu
    if (empty($error)) {
        foreach ($columns as $col) {
            if ($col['Field'] === 'gambar_menu') {
                if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowedTypes = ['image/jpeg', 'image/png'];
                    $fileType = $_FILES['gambar_menu']['type'];
                    if (!in_array($fileType, $allowedTypes)) {
                        $error = "Format gambar harus JPG atau PNG.";
                        break;
                    }
                    if ($_FILES['gambar_menu']['size'] > 2 * 1024 * 1024) { // 2MB limit
                        $error = "Ukuran gambar maksimal 2MB.";
                        break;
                    }
                    $ext = pathinfo($_FILES['gambar_menu']['name'], PATHINFO_EXTENSION);
                    $newFileName = uniqid('img_') . '.' . $ext;
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $uploadPath = $uploadDir . $newFileName;
                    if (!move_uploaded_file($_FILES['gambar_menu']['tmp_name'], $uploadPath)) {
                        $error = "Gagal mengupload gambar.";
                        break;
                    }
                    $_POST['gambar_menu'] = $newFileName;
                } else {
                    // Jika kolom gambar_menu wajib, bisa tambahkan validasi di sini
                    $_POST['gambar_menu'] = null;
                }
            }
        }
    }

    if (empty($error)) {
        $fields = [];
        $placeholders = [];
        $values = [];
        foreach ($columns as $col) {
            $field = $col['Field'];
            if ($field === $primaryKey && $col['Extra'] === 'auto_increment') {
                continue; // skip auto increment primary key
            }
            $fields[] = "`$field`";
            $placeholders[] = "?";

            // Untuk input datetime-local, ubah format ke MySQL DATETIME
            if (strpos($col['Type'], 'datetime') !== false && isset($_POST[$field]) && $_POST[$field] !== '') {
                // Format input datetime-local: yyyy-mm-ddThh:mm
                // Ubah ke yyyy-mm-dd hh:mm:ss
                $dt = str_replace('T', ' ', $_POST[$field]) . ':00';
                $values[] = $dt;
            } else {
                $values[] = $_POST[$field] ?? null;
            }
        }
        $sql = "INSERT INTO `$table` (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        if ($stmt->execute()) {
            $_SESSION['notif'] = "Data berhasil ditambahkan pada tabel " . capitalizeWords($table) . ".";
            header("Location: index.php?table=$table");
            exit;
        } else {
            $error = $stmt->error;
        }
    }
}

function e($str) {
    return htmlspecialchars($str);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Data - <?php echo capitalizeWords($table); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 600px; }
        label { display: block; margin-top: 10px; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; }
        button { margin-top: 15px; padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        .error { color: red; margin-top: 10px; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

<h1>Tambah Data - <?php echo capitalizeWords($table); ?></h1>
<a href="index.php?table=<?php echo e($table); ?>">&laquo; Kembali</a>

<?php if (!empty($error)): ?>
    <div class="error"><?php echo e($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?php foreach ($columns as $col): 
        $field = $col['Field'];
        $type = $col['Type'];
        $null = $col['Null'] === 'YES';
        $extra = $col['Extra'];
        if ($field === $primaryKey && $extra === 'auto_increment') continue;
    ?>
        <label for="<?php echo e($field); ?>"><?php echo capitalizeWords($field); ?><?php echo $null ? '' : ' *'; ?></label>

        <?php if ($field === 'gambar_menu'): ?>
            <input type="file" name="gambar_menu" id="gambar_menu" accept=".jpg,.jpeg,.png" <?php echo $null ? '' : 'required'; ?>>
        <?php elseif (strpos($type, 'enum') === 0): 
            preg_match("/enum\((.*)\)/", $type, $matches);
            $options = explode(',', str_replace("'", "", $matches[1]));
        ?>
            <select name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>>
                <option value="">-- Pilih --</option>
                <?php foreach ($options as $opt): ?>
                    <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif (strpos($type, 'date') === 0): ?>
            <input type="date" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>>
        <?php elseif (strpos($type, 'datetime') === 0): ?>
            <input type="datetime-local" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>>
        <?php elseif (strpos($type, 'text') !== false): ?>
            <textarea name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>></textarea>
        <?php else: ?>
            <input type="text" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>>
        <?php endif; ?>
    <?php endforeach; ?>
    <button type="submit">Simpan</button>
</form>

</body>
</html>
