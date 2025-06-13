<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data pembayaran dari API
$pembayaran_data = [];
$pembayaran_error = null;
$api_url_pembayaran = 'http://localhost:5000/pembayaran';
$ch = curl_init($api_url_pembayaran);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $pembayaran_data = json_decode($response, true);
    // Urutkan data berdasarkan tanggal_bayar secara descending
    usort($pembayaran_data, function($a, $b) {
        return strtotime($b['tanggal_bayar']) - strtotime($a['tanggal_bayar']);
    });
} else {
    $pembayaran_error = 'Gagal mengambil data: HTTP ' . $http_code;
}

$total_pembayaran = 0;
$jumlah_transaksi = count($pembayaran_data);
$tanggal_terbaru = '-';

if ($jumlah_transaksi > 0) {
    foreach ($pembayaran_data as $item) {
        $total_pembayaran += floatval($item['total_bayar']);
    }
    $tanggal_terbaru = $pembayaran_data[0]['tanggal_bayar']; // Ambil tanggal terbaru setelah diurutkan
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script> <!-- Ganti dengan ID Kit FontAwesome Anda -->
    <style>
        .table-custom {
            @apply min-w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden;
        }
        .table-custom th {
            @apply px-6 py-3 font-semibold text-gray-700 bg-teal-50;
        }
        .table-custom td {
            @apply px-6 py-3 text-gray-800;
        }
        .card-icon {
            @apply text-2xl p-3 rounded-full;
        }
    </style>
</head>
<body class="bg-blue-50 min-h-screen font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-xl rounded-xl w-full max-w-6xl mx-auto overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-6 bg-teal-600 text-white">
                <div class="flex items-center">
                    <i class="fas fa-mosque text-3xl mr-3 text-teal-200"></i>
                    <h1 class="text-2xl font-bold">Dashboard Pembayaran Zakat</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <?php if ($pembayaran_error): ?>
                    <div class="bg-red-100 text-red-800 border border-red-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($pembayaran_error); ?>
                    </div>
                <?php endif; ?>

                <!-- Ringkasan -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="flex items-center bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition">
                        <div class="bg-teal-100 card-icon text-teal-600">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Pembayaran</p>
                            <p class="text-xl font-bold text-gray-800">Rp <?= number_format($total_pembayaran, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition">
                        <div class="bg-teal-100 card-icon text-teal-600">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Jumlah Transaksi</p>
                            <p class="text-xl font-bold text-gray-800"><?= $jumlah_transaksi; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition">
                        <div class="bg-teal-100 card-icon text-teal-600">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Tanggal Terbaru</p>
                            <p class="text-xl font-bold text-gray-800"><?= htmlspecialchars($tanggal_terbaru); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Daftar Pembayaran Terbaru</h2>
                    <?php if ($jumlah_transaksi === 0): ?>
                        <p class="text-center text-gray-500 py-6">Belum ada data pembayaran.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jenis Zakat</th>
                                        <th class="text-right">Total Bayar (Rp)</th>
                                        <th>Tanggal Bayar</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php foreach (array_slice($pembayaran_data, 0, 5) as $data): ?>
                                        <tr class="border-b hover:bg-teal-50 transition">
                                            <td><?= htmlspecialchars($data['nama']); ?></td>
                                            <td><?= htmlspecialchars($data['jenis_zakat']); ?></td>
                                            <td class="text-right">Rp <?= number_format($data['total_bayar'], 2, ',', '.'); ?></td>
                                            <td><?= htmlspecialchars($data['tanggal_bayar']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigasi -->
                <div class="text-center mt-8 space-x-4">
                    <a href="pembayaran.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-full text-sm font-medium transition">
                        <i class="fas fa-plus mr-2"></i> Tambah Pembayaran
                    </a>
                    <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-medium transition">
                        <i class="fas fa-history mr-2"></i> History Pembayaran
                    </a>
                    <a href="beras.php" class="inline-block bg-teal-500 hover:bg-teal-600 text-white px-6 py-3 rounded-full text-sm font-medium transition">
                        <i class="fas fa-seedling mr-2"></i> Data Beras
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>