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

// Ambil parameter tabel
$table = $_GET['table'] ?? '';
if (!array_key_exists($table, $tables)) {
    die("Tabel tidak valid.");
}
$primaryKey = $tables[$table];

// Ambil struktur tabel
$result = $koneksi->query("DESCRIBE `$table`");
if (!$result) {
    die("Gagal mengambil struktur tabel: " . $koneksi->error);
}

$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row;
}

// Fungsi untuk mendapatkan enum values
function getEnumValues($type) {
    preg_match("/^enum\('(.*)'\)$/", $type, $matches);
    if (!isset($matches[1])) return [];
    $vals = explode("','", $matches[1]);
    return $vals;
}

// Fungsi untuk mengubah string menjadi Title Case
function toTitleCase($string) {
    return ucwords(strtolower($string));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $columns = [];
    $values = [];

    foreach ($fields as $field) {
        $name = $field['Field'];

        if ($name === 'gambar_menu' && isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $tmpName = $_FILES['gambar_menu']['tmp_name'];
            $fileName = basename($_FILES['gambar_menu']['name']);
            $targetFile = $uploadDir . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

            if (move_uploaded_file($tmpName, $targetFile)) {
                $columns[] = "`$name`";
                $values[] = "'" . $koneksi->real_escape_string($targetFile) . "'";
            } else {
                $error = "Gagal mengupload gambar.";
                break;
            }
        } else {
            $val = $_POST[$name] ?? '';
            $columns[] = "`$name`";
            $values[] = "'" . $koneksi->real_escape_string($val) . "'";
        }
    }

    if (!$error) {
        $sql = "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
        if ($koneksi->query($sql)) {
            $_SESSION['notif'] = "Data berhasil ditambahkan.";
            header("Location: index.php?table=$table");
            exit;
        } else {
            $error = "Gagal menambahkan data: " . $koneksi->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Tambah Data - <?= htmlspecialchars($table) ?></title>
    <style>
        /* Reset & base */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 700px;
            background: #fff;
            margin: auto;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        .container:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        h2 {
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 28px;
            color: #007BFF;
            text-align: center;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            margin-top: 18px;
        }
        form input[type="text"],
        form input[type="date"],
        form input[type="time"],
        form select,
        form input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 1.8px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        form input[type="text"]:focus,
        form input[type="date"]:focus,
        form input[type="time"]:focus,
        form select:focus,
        form input[type="file"]:focus {
            border-color: #007BFF;
            box-shadow: 0 0 8px rgba(0,123,255,0.3);
            outline: none;
        }
        .btn-group {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        button {
            padding: 14px 28px;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.3s ease;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        button.submit-btn {
            background-color: #007BFF;
        }
        button.submit-btn:hover {
            background-color: #0056b3;
        }
        button.cancel-btn {
            background-color: #6c757d;
        }
        button.cancel-btn:hover {
            background-color: #5a6268;
        }
        .notif {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            font-weight: 600;
            text-align: center;
        }
        .error {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Tambah Data - <?= htmlspecialchars(ucwords(str_replace('_', ' ', $table))) ?></h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <?php foreach ($fields as $field):
            $name = $field['Field'];
            $type = $field['Type'];
            $null = $field['Null'] === 'NO' ? 'required' : '';

            // **Tampilkan semua field termasuk primary key untuk input manual**
            if (preg_match('/^enum\((.*)\)$/', $type)) {
                $options = getEnumValues($type);
                ?>
                <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
                <select name="<?= $name ?>" id="<?= $name ?>" <?= $null ?> >
                    <option value="">-- Pilih --</option>
                    <?php foreach ($options as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars(toTitleCase($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php
            } elseif (strpos($type, 'date') !== false) {
                ?>
                <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
                <input type="date" name="<?= $name ?>" id="<?= $name ?>" <?= $null ?> />
            <?php
            } elseif (strpos($type, 'time') !== false) {
                ?>
                <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
                <input type="time" name="<?= $name ?>" id="<?= $name ?>" <?= $null ?> />
            <?php
            } elseif ($name === 'gambar_menu') {
                ?>
                <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
                <input type="file" name="<?= $name ?>" id="<?= $name ?>" <?= $null ?> />
            <?php
            } else {
                ?>
                <label for="<?= $name ?>"><?= ucwords(str_replace('_', ' ', $name)) ?></label>
                <input type="text" name="<?= $name ?>" id="<?= $name ?>" <?= $null ?> />
            <?php
            }
        endforeach; ?>
        <div class="btn-group">
            <button type="submit" class="submit-btn">Tambah Data</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='index.php?table=<?= htmlspecialchars($table) ?>'">Batal</button>
        </div>
    </form>
</div>
</body>
</html>
