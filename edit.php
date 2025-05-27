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

// Ambil nama tabel dan primary key
$table = $_GET['table'] ?? 'meja_reservasi';
$primaryKey = $tables[$table] ?? 'id';

// Ambil nilai primary key dari URL
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = "ID tidak ditemukan.";
    header("Location: index.php?table=$table");
    exit;
}

// Ambil struktur kolom dari tabel
class Column {
    public $name;
    public $type;
    public $enumValues = [];
    public $isImage = false;
}

$columns = [];
if (isset($tables[$table])) {
    $result = mysqli_query($koneksi, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        $col = new Column();
        $col->name = $row['Field'];
        $col->type = $row['Type'];

        // Deteksi tipe enum dan ambil opsi enum
        if (preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
            $enumStr = $matches[1];
            $col->enumValues = array_map(function($val) {
                return trim($val, "'");
            }, explode(',', $enumStr));
        }

        // Deteksi kolom gambar (khusus 'gambar_menu')
        if ($row['Field'] === 'gambar_menu') {
            $col->isImage = true;
        }

        $columns[] = $col;
    }
} else {
    $_SESSION['error'] = "Tabel tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Ambil data lama dari database
$query = "SELECT * FROM $table WHERE $primaryKey = '" . mysqli_real_escape_string($koneksi, $id) . "'";
$result = mysqli_query($koneksi, $query);
if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Data tidak ditemukan.";
    header("Location: index.php?table=$table");
    exit;
}
$data = mysqli_fetch_assoc($result);

// Proses form submission
if (isset($_POST['submit'])) {
    $updates = [];

    foreach ($columns as $col) {
        $name = $col->name;

        if ($col->isImage) {
            // Proses upload gambar jika ada file baru
            if (isset($_FILES[$name]) && $_FILES[$name]['error'] === 0) {
                $imageName = time() . '_' . basename($_FILES[$name]['name']);
                $targetPath = "uploads/" . $imageName;

                // Validasi tipe file gambar (basic)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($_FILES[$name]['tmp_name']);
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES[$name]['tmp_name'], $targetPath)) {
                        $escapedImageName = mysqli_real_escape_string($koneksi, $imageName);
                        $updates[] = "$name = '$escapedImageName'";
                    } else {
                        $_SESSION['error'] = "Gagal mengupload gambar.";
                    }
                } else {
                    $_SESSION['error'] = "Tipe file tidak diizinkan. Hanya JPG, PNG, GIF.";
                }
            }
            // Jika tidak upload gambar baru, biarkan gambar lama tetap
        } else {
            // Ambil nilai dari POST, escape untuk keamanan
            $value = $_POST[$name] ?? '';
            $escapedValue = mysqli_real_escape_string($koneksi, $value);
            $updates[] = "$name = '$escapedValue'";
        }
    }

    if (!isset($_SESSION['error'])) {
        $updateQuery = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $primaryKey = '" . mysqli_real_escape_string($koneksi, $id) . "'";
        if (mysqli_query($koneksi, $updateQuery)) {
            $_SESSION['success'] = "Data berhasil diperbarui.";
            header("Location: index.php?table=$table");
            exit;
        } else {
            $_SESSION['error'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Edit Data <?= htmlspecialchars(str_replace('_', ' ', $table)) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 150px 1fr;
            grid-gap: 15px 20px;
            align-items: center;
        }
        label {
            text-align: right;
            font-weight: bold;
            color: #555;
            padding-right: 10px;
        }
        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        input[type="file"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input[type="file"] {
            padding: 3px 10px;
        }
        .form-row {
            display: contents;
        }
        .buttons {
            grid-column: 1 / span 2;
            text-align: center;
            margin-top: 20px;
        }
        button, a.button-link {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            transition: background-color 0.3s ease;
        }
        button:hover, a.button-link:hover {
            background-color: #0056b3;
        }
        .message {
            max-width: 600px;
            margin: 10px auto;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        img.preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            margin-left: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<h1>Edit Data <?= htmlspecialchars(str_replace('_', ' ', $table)) ?></h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
    <?php foreach ($columns as $col): 
        $name = $col->name;
        $value = htmlspecialchars($data[$name] ?? '');
        $type = strtolower($col->type);
    ?>
    <div class="form-row">
        <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
        <div>
            <?php
            // Jika kolom gambar_menu, tampilkan input file dan preview gambar lama
            if ($col->isImage): ?>
                <input type="file" name="<?= $name ?>" id="<?= $name ?>" accept="image/*" />
                <?php if (!empty($value)): ?>
                    <img src="uploads/<?= $value ?>" alt="Gambar <?= $name ?>" class="preview-image" />
                <?php endif; ?>
            <?php 
            // Jika tipe enum, buat dropdown
            elseif (!empty($col->enumValues)): ?>
                <select name="<?= $name ?>" id="<?= $name ?>">
                    <?php foreach ($col->enumValues as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= ($option === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php 
            // Jika tipe date
            elseif (strpos($type, 'date') !== false): ?>
                <input type="date" name="<?= $name ?>" id="<?= $name ?>" value="<?= $value ?>" />
            <?php 
            // Jika tipe time
            elseif (strpos($type, 'time') !== false): ?>
                <input type="time" name="<?= $name ?>" id="<?= $name ?>" value="<?= $value ?>" />
            <?php 
            // Default input text
            else: ?>
                <input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= $value ?>" />
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="buttons">
        <button type="submit" name="submit">Simpan Perubahan</button>
        <a href="index.php?table=<?= htmlspecialchars($table) ?>" class="button-link">kembali ke daftar <?= htmlspecialchars(str_replace('_', ' ', $table)) ?></a>
    </div>
</form>

</body>
</html>
