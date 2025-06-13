<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// URL endpoint API Flask
$api_url = 'http://localhost:5000/beras';

// Inisialisasi variabel
$data = []; // Inisialisasi sebagai array kosong
$error = null;

// Inisialisasi cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Eksekusi permintaan cURL
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dekode respons JSON
if ($response !== false && $http_code === 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Gagal mendekode data JSON: ' . json_last_error_msg();
    }
} else {
    $error = 'Gagal mengambil data beras: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk menambah data beras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harga'])) {
    $data_to_send = ['harga' => floatval($_POST['add_harga'])];
    $api_url_post = 'http://localhost:5000/beras';
    $ch = curl_init($api_url_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $success = "Data beras berhasil ditambahkan.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Gagal mendekode data JSON setelah penambahan: ' . json_last_error_msg();
            }
        } else {
            $error = 'Gagal mengambil data beras setelah penambahan: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
        }
    } else {
        $error = "Gagal menambahkan data beras: HTTP $http_code";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Harga Beras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script> <!-- Ganti dengan ID Kit FontAwesome Anda -->
    <style>
        .header-icon {
            @apply text-3xl mr-3 text-teal-500;
        }
        .table-custom {
            @apply min-w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden;
        }
        .table-custom th {
            @apply px-6 py-3 font-semibold text-gray-700 bg-teal-50;
        }
        .table-custom td {
            @apply px-6 py-3 text-gray-800;
        }
        .modal-transition {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-blue-50 min-h-screen font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-xl rounded-xl w-full max-w-5xl mx-auto overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-6 bg-teal-600 text-white">
                <div class="flex items-center">
                    <i class="fas fa-sack header-icon"></i>
                    <h1 class="text-2xl font-bold">Data Harga Beras</h1>
                </div>
                <div class="space-x-3">
                    <a href="dashboard.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">Kembali</a>
                    <button onclick="openAddModal()" class="bg-teal-500 text-white px-4 py-2 rounded-lg hover:bg-teal-600 transition">Tambah Data</button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-800 border border-red-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif (isset($success)): ?>
                    <div class="bg-teal-100 text-teal-800 border border-teal-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Harga</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php if (!empty($data) && is_array($data)): ?>
                                <?php foreach ($data as $record): ?>
                                    <tr class="border-b hover:bg-teal-50 transition">
                                        <td><?= htmlspecialchars($record['id']) ?></td>
                                        <td>Rp <?= number_format($record['harga'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">Tidak ada data ditemukan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="addModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 modal-transition">
            <div class="bg-white rounded-xl p-8 shadow-2xl w-full max-w-lg transform scale-95 modal-transition">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Tambah Data Beras</h2>
                <form id="addForm" action="" method="POST" class="space-y-6">
                    <div>
                        <label for="add_harga" class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                        <div class="mt-1 relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                            <input type="number" id="add_harga" name="add_harga" class="pl-10 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Masukkan harga" step="0.01" required>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition">Simpan</button>
                        <button type="button" onclick="closeAddModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('add_harga').value = '';
            document.getElementById('addModal').classList.remove('hidden');
            setTimeout(() => {
                document.querySelector('#addModal > div').classList.remove('scale-95');
                document.querySelector('#addModal > div').classList.add('scale-100');
            }, 10);
        }

        function closeAddModal() {
            document.querySelector('#addModal > div').classList.remove('scale-100');
            document.querySelector('#addModal > div').classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('addModal').classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>