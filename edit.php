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

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';
if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}
if (!$id) {
    die("ID tidak ditemukan.");
}

$primaryKey = $tables[$table];

// Daftar foreign key dan tabel referensi untuk validasi FK
// Format: 'nama_kolom_fk' => ['referensi_tabel', 'referensi_kolom']
$foreignKeys = [
    'id_restoran' => ['restoran', 'id_restoran'],
    'id_bahan' => ['bahan_baku', 'id_bahan'],
    'id_karyawan' => ['karyawan', 'id_karyawan'],
    'id_menu' => ['menu', 'id_menu'],
    'id_meja' => ['meja_reservasi', 'id_meja'],
    'id_pelanggan' => ['pelanggan', 'id_pelanggan'],
    'id_inventaris' => ['inventaris', 'id_inventaris'],
    'id_member' => ['member', 'id_member'],
    'id_pesanan' => ['pesanan', 'id_pesanan'],
    'id_detail_pesanan' => ['detail_pesanan', 'id_detail_pesanan'],
    'id_resep' => ['resep', 'id_resep'],
    'id_transaksi' => ['transaksi', 'id_transaksi'],
    'id_ulasan' => ['ulasan', 'id_ulasan'],
];

// Ambil struktur kolom
$columns = [];
$stmt = $koneksi->prepare("SHOW COLUMNS FROM `$table`");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
}

// Ambil data lama
$stmt = $koneksi->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$oldData = $result->fetch_assoc();
if (!$oldData) {
    die("Data tidak ditemukan.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi FK
    foreach ($foreignKeys as $fkField => $ref) {
        if (array_key_exists($fkField, $_POST)) {
            $fkValue = $_POST[$fkField];
            if ($fkValue !== '') {
                $refTable = $ref[0];
                $refColumn = $ref[1];
                $stmtCheck = $koneksi->prepare("SELECT COUNT(*) as cnt FROM `$refTable` WHERE `$refColumn` = ?");
                $stmtCheck->bind_param('s', $fkValue);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result()->fetch_assoc();
                if ($resCheck['cnt'] == 0) {
                    $error = formatLabel($fkField) . " Tidak Ditemukan Atau Belum Dibuat";
                    break;
                }
            }
        }
    }

    if (!$error) {
        $fields = [];
        $values = [];
        $types = '';
        $gambarMenuBaru = null;

        // Handle file upload khusus untuk gambar_menu
        if (in_array('gambar_menu', array_column($columns, 'Field'))) {
            if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['gambar_menu'];
                $allowedExt = ['jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt)) {
                    $error = "Format gambar harus JPG atau PNG.";
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = "Terjadi kesalahan saat upload gambar.";
                } else {
                    // Simpan file ke folder 'uploads' (buat folder jika belum ada)
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = uniqid('img_') . '.' . $ext;
                    $uploadPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $gambarMenuBaru = $newFileName;
                    } else {
                        $error = "Gagal menyimpan gambar.";
                    }
                }
            }
        }

        if (!$error) {
            foreach ($columns as $col) {
                $field = $col['Field'];
                if ($field === $primaryKey) continue;

                if ($field === 'gambar_menu') {
                    // Jika ada gambar baru, pakai gambar baru, jika tidak pakai gambar lama
                    if ($gambarMenuBaru !== null) {
                        $fields[] = "`$field` = ?";
                        $values[] = $gambarMenuBaru;
                        $types .= 's';
                    } else {
                        // Jika tidak upload gambar baru, tetap pakai gambar lama
                        $fields[] = "`$field` = ?";
                        $values[] = $oldData[$field];
                        $types .= 's';
                    }
                    continue;
                }

                // Ambil nilai dari POST
                $val = $_POST[$field] ?? null;

                // Untuk tipe datetime-local, ubah format ke format MySQL DATETIME
                if (strpos($col['Type'], 'datetime') !== false && $val) {
                    // Format input datetime-local: yyyy-mm-ddThh:mm
                    // Ubah ke yyyy-mm-dd hh:mm:ss
                    $val = str_replace('T', ' ', $val) . ':00';
                }

                $fields[] = "`$field` = ?";
                $values[] = $val;
                $types .= 's';
            }
            $values[] = $id;
            $types .= 's';

            $sql = "UPDATE `$table` SET " . implode(',', $fields) . " WHERE `$primaryKey` = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                $_SESSION['notif'] = "Data berhasil diperbarui pada tabel " . formatLabel($table) . ".";
                header("Location: index.php?table=$table");
                exit;
            } else {
                $error = $stmt->error;
            }
        }
    }
}

function e($str) {
    return htmlspecialchars($str);
}

function formatLabel($str) {
    // Ubah "id_bahan_baku" menjadi "Id Bahan Baku"
    $str = str_replace('_', ' ', $str);
    $str = ucwords($str);
    return $str;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Data - <?php echo e(formatLabel($table)); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 600px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { margin-top: 15px; padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; margin-top: 10px; font-weight: bold; }
        a { text-decoration: none; color: #007bff; }
        img.preview { max-width: 200px; margin-top: 10px; display: block; }
    </style>
</head>
<body>

<h1>Edit Data - <?php echo e(formatLabel($table)); ?></h1>
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
        if ($field === $primaryKey) continue;
        $value = $oldData[$field] ?? '';

        // Format label
        $label = formatLabel($field);

        // Tentukan input type
        if ($field === 'gambar_menu') {
            // Input file untuk gambar_menu
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <?php if ($value): ?>
                <img src="uploads/<?php echo e($value); ?>" alt="Gambar Menu" class="preview">
            <?php endif; ?>
            <input type="file" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" accept=".jpg,.jpeg,.png" <?php echo $null ? '' : 'required'; ?>>
            <?php
        } elseif (strpos($type, 'enum') === 0) {
            preg_match("/enum\((.*)\)/", $type, $matches);
            $options = explode(',', str_replace("'", "", $matches[1]));
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <select name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>>
                <option value="">-- Pilih --</option>
                <?php foreach ($options as $opt): ?>
                    <option value="<?php echo e($opt); ?>" <?php echo ($opt === $value) ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        } elseif (strpos($type, 'text') !== false) {
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <textarea name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" <?php echo $null ? '' : 'required'; ?>><?php echo e($value); ?></textarea>
            <?php
        } elseif (strpos($type, 'date') === 0) {
            // Input type date
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <input type="date" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" value="<?php echo e($value); ?>" <?php echo $null ? '' : 'required'; ?>>
            <?php
        } elseif (strpos($type, 'datetime') === 0) {
            // Input type datetime-local, ubah format value ke yyyy-mm-ddThh:mm
            $valDatetime = '';
            if ($value && $value !== '0000-00-00 00:00:00') {
                $valDatetime = date('Y-m-d\TH:i', strtotime($value));
            }
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <input type="datetime-local" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" value="<?php echo e($valDatetime); ?>" <?php echo $null ? '' : 'required'; ?>>
            <?php
        } else {
            ?>
            <label for="<?php echo e($field); ?>"><?php echo e($label); ?><?php echo $null ? '' : ' *'; ?></label>
            <input type="text" name="<?php echo e($field); ?>" id="<?php echo e($field); ?>" value="<?php echo e($value); ?>" <?php echo $null ? '' : 'required'; ?>>
            <?php
        }
    endforeach; ?>
    <button type="submit">Simpan Perubahan</button>
</form>

</body>
</html>
