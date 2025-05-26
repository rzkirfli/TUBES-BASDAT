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

// Ambil parameter tabel dan primary key dari URL
$table = $_GET['table'] ?? '';
if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}
$primaryKey = $tables[$table];

// Ambil nilai primary key dari URL
$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("ID primary key tidak ditemukan.");
}

// Ambil struktur tabel
$result = $koneksi->query("DESCRIBE `$table`");
if (!$result) {
    die("Gagal mengambil struktur tabel: " . $koneksi->error);
}

$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row;
}

// Ambil data yang akan diedit
$sql = "SELECT * FROM `$table` WHERE `$primaryKey` = '" . $koneksi->real_escape_string($id) . "'";
$dataResult = $koneksi->query($sql);
if ($dataResult->num_rows == 0) {
    die("Data dengan ID tersebut tidak ditemukan.");
}
$data = $dataResult->fetch_assoc();

// Fungsi untuk mendapatkan enum values
function getEnumValues($type) {
    preg_match("/^enum\('(.*)'\)$/", $type, $matches);
    if (!isset($matches[1])) return [];
    $vals = explode("','", $matches[1]);
    return $vals;
}

// Proses form submit update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    foreach ($fields as $field) {
        $name = $field['Field'];

        // Khusus untuk gambar_menu di tabel menu, proses upload file
        if ($table === 'menu' && $name === 'gambar_menu') {
            if (isset($_FILES[$name]) && $_FILES[$name]['error'] === 0) {
                $uploadDir = 'uploads/';
                // Buat nama file unik untuk menghindari overwrite
                $fileName = uniqid('menu_') . '_' . basename($_FILES[$name]['name']);
                $targetPath = $uploadDir . $fileName;

                // Validasi tipe file gambar
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($_FILES[$name]['tmp_name']);

                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES[$name]['tmp_name'], $targetPath)) {
                        $updates[] = "`$name` = '" . $koneksi->real_escape_string($fileName) . "'";
                    } else {
                        die("Gagal mengupload gambar.");
                    }
                } else {
                    die("Tipe file tidak diperbolehkan. Hanya JPEG, PNG, dan GIF yang diterima.");
                }
            } else {
                // Jika tidak upload gambar baru, jangan update kolom gambar_menu
                // Jadi gambar lama tetap dipertahankan
            }
        } else {
            // Untuk field lain, ambil dari POST
            $value = $_POST[$name] ?? '';
            $valueEscaped = $koneksi->real_escape_string($value);
            $updates[] = "`$name` = '$valueEscaped'";
        }
    }

    if (!empty($updates)) {
        $sqlUpdate = "UPDATE `$table` SET " . implode(',', $updates) . " WHERE `$primaryKey` = '" . $koneksi->real_escape_string($id) . "'";
        if ($koneksi->query($sqlUpdate)) {
            $_SESSION['notif'] = "Data berhasil diperbarui.";
            header("Location: index.php?table=$table");
            exit;
        } else {
            $error = "Gagal memperbarui data: " . $koneksi->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit <?php echo ucwords(str_replace('_', ' ', $table)); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background-color: #f9f9f9; }
        form { background: #fff; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], input[type="number"], select, textarea, input[type="file"] {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type="submit"] {
            margin-top: 20px; background-color: #28a745; color: white; border: none; padding: 10px 15px;
            border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .error { color: red; margin-top: 10px; }
        h2 { text-align: center; }
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            max-height: 150px;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h2>Edit <?php echo ucwords(str_replace('_', ' ', $table)); ?></h2>
    <?php if (!empty($error)) : ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php foreach ($fields as $field): 
            $name = $field['Field'];
            $type = $field['Type'];
            $value = $data[$name] ?? '';
            $isPrimary = ($name === $primaryKey);
        ?>
            <label for="<?php echo $name; ?>">
                <?php echo ucwords(str_replace('_', ' ', $name)); ?>
                <?php if ($isPrimary) echo " (Primary Key)"; ?>
            </label>
            <?php
            if ($isPrimary) {
                // Primary key tetap input text readonly agar tidak diubah
                echo "<input type='text' name='$name' id='$name' value='" . htmlspecialchars($value) . "' readonly>";
            } else {
                // Jika ini kolom gambar_menu di tabel menu, tampilkan input file dan preview gambar lama
                if ($table === 'menu' && $name === 'gambar_menu') {
                    echo "<input type='file' name='$name' id='$name' accept='image/*'>";
                    if (!empty($value)) {
                        echo "<img src='uploads/" . htmlspecialchars($value) . "' alt='Gambar Menu' class='image-preview'>";
                    }
                } elseif (preg_match('/^enum\((.*)\)$/', $type)) {
                    $enumValues = getEnumValues($type);
                    echo "<select name='$name' id='$name' required>";
                    echo "<option value=''>-- Pilih --</option>";
                    foreach ($enumValues as $enumVal) {
                        $selected = ($enumVal === $value) ? 'selected' : '';
                        $displayText = ucwords(strtolower($enumVal));
                        echo "<option value='" . htmlspecialchars($enumVal) . "' $selected>$displayText</option>";
                    }
                    echo "</select>";
                } elseif (strpos($type, 'int') !== false) {
                    echo "<input type='number' name='$name' id='$name' value='" . htmlspecialchars($value) . "'>";
                } elseif (strpos($type, 'text') !== false) {
                    echo "<textarea name='$name' id='$name' rows='4'>" . htmlspecialchars($value) . "</textarea>";
                } else {
                    echo "<input type='text' name='$name' id='$name' value='" . htmlspecialchars($value) . "'>";
                }
            }
            ?>
        <?php endforeach; ?>
        <input type="submit" value="Update Data">
    </form>
</body>
</html>
