<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Impor namespace PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// URL of the Flask API endpoint
$api_url = 'http://localhost:5000/pembayaran';

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);
$error = null;
if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $error = 'Gagal mengambil data pembayaran: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk memperbarui data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $data_update = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'keterangan' => $_POST['keterangan'],
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    $api_url_put = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_update));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil diperbarui.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal memperbarui data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk menghapus data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $api_url_delete = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_delete);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil dihapus.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal menghapus data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk generate Excel
if (isset($_GET['generate_excel']) && !$error && !empty($data)) {
    require 'vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Jumlah Jiwa');
    $sheet->setCellValue('C1', 'Jenis Zakat');
    $sheet->setCellValue('D1', 'Nama');
    $sheet->setCellValue('E1', 'Metode Pembayaran');
    $sheet->setCellValue('F1', 'Total Bayar');
    $sheet->setCellValue('G1', 'Nominal Dibayar');
    $sheet->setCellValue('H1', 'Kembalian');
    $sheet->setCellValue('I1', 'Keterangan');
    $sheet->setCellValue('J1', 'Tanggal Bayar');

    // Data
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $record['jumlah_jiwa']);
        $sheet->setCellValue('C' . $row, $record['jenis_zakat']);
        $sheet->setCellValue('D' . $row, $record['nama']);
        $sheet->setCellValue('E' . $row, $record['metode_pembayaran']);
        $sheet->setCellValue('F' . $row, $record['total_bayar']);
        $sheet->setCellValue('G' . $row, $record['nominal_dibayar']);
        $sheet->setCellValue('H' . $row, $record['kembalian']);
        $sheet->setCellValue('I' . $row, $record['keterangan']);
        $sheet->setCellValue('J' . $row, $record['tanggal_bayar']);
        $row++;
    }

    // Styling
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Unduh file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="pembayaran_zakat_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pembayaran Zakat</title>
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
    </style>
    <script>
        function openEditModal(id, nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, nominal_dibayar, kembalian, keterangan, tanggal_bayar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jumlah_jiwa').value = jumlah_jiwa;
            document.getElementById('edit_jenis_zakat').value = jenis_zakat;
            document.getElementById('edit_metode_pembayaran').value = metode_pembayaran;
            document.getElementById('edit_total_bayar').value = total_bayar;
            document.getElementById('edit_nominal_dibayar').value = nominal_dibayar;
            document.getElementById('edit_kembalian').value = kembalian;
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_tanggal_bayar').value = tanggal_bayar.replace(' ', 'T');
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('opacity-100', 'scale-100');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 150);
        }
    </script>
</head>
<body class="bg-blue-50 min-h-screen font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-xl rounded-xl w-full max-w-7xl mx-auto overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-6 bg-teal-600 text-white">
                <div class="flex items-center">
                    <i class="fas fa-history text-3xl mr-3 text-teal-200"></i>
                    <h1 class="text-2xl font-bold">History Pembayaran Zakat</h1>
                </div>
                <div class="flex space-x-4">
                    <a href="?generate_excel=1" class="bg-teal-500 text-white px-4 py-2 rounded-lg hover:bg-teal-600 transition flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> Generate Excel
                    </a>
                    <a href="dashboard.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">Kembali</a>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-800 border border-red-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php elseif (empty($data)): ?>
                    <p class="text-center text-gray-500 py-6">Tidak ada data pembayaran ditemukan.</p>
                <?php else: ?>
                    <?php if (isset($success)): ?>
                        <div class="bg-teal-100 text-teal-800 border border-teal-300 rounded-lg p-4 mb-6">
                            <?= htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <div class="overflow-x-auto">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Jumlah Jiwa</th>
                                    <th>Jenis Zakat</th>
                                    <th>Nama</th>
                                    <th>Metode Pembayaran</th>
                                    <th class="text-right">Total Bayar (Rp)</th>
                                    <th class="text-right">Nominal Dibayar (Rp)</th>
                                    <th class="text-right">Kembalian (Rp)</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php foreach ($data as $record): ?>
                                    <tr class="border-b hover:bg-teal-50 transition">
                                        <td><?= htmlspecialchars($record['id']); ?></td>
                                        <td><?= htmlspecialchars($record['jumlah_jiwa']); ?></td>
                                        <td><?= htmlspecialchars($record['jenis_zakat']); ?></td>
                                        <td><?= htmlspecialchars($record['nama']); ?></td>
                                        <td><?= htmlspecialchars($record['metode_pembayaran']); ?></td>
                                        <td class="text-right">Rp <?= number_format($record['total_bayar'], 2, ',', '.'); ?></td>
                                        <td class="text-right">Rp <?= number_format($record['nominal_dibayar'], 2, ',', '.'); ?></td>
                                        <td class="text-right">Rp <?= number_format($record['kembalian'], 2, ',', '.'); ?></td>
                                        <td><?= htmlspecialchars($record['keterangan']); ?></td>
                                        <td><?= htmlspecialchars($record['tanggal_bayar']); ?></td>
                                        <td class="flex items-center space-x-2">
                                            <button onclick="openEditModal(
                                                <?= $record['id']; ?>,
                                                '<?= htmlspecialchars(addslashes($record['nama'])); ?>',
                                                <?= $record['jumlah_jiwa']; ?>,
                                                '<?= htmlspecialchars(addslashes($record['jenis_zakat'])); ?>',
                                                '<?= htmlspecialchars(addslashes($record['metode_pembayaran'])); ?>',
                                                <?= $record['total_bayar']; ?>,
                                                <?= $record['nominal_dibayar']; ?>,
                                                <?= $record['kembalian']; ?>,
                                                '<?= htmlspecialchars(addslashes($record['keterangan'])); ?>',
                                                '<?= $record['tanggal_bayar']; ?>'
                                            )" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 text-sm transition">Edit</button>
                                            <form action="" method="POST" class="inline-block ml-2">
                                                <input type="hidden" name="delete_id" value="<?= $record['id']; ?>">
                                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 text-sm transition">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk Edit -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden opacity-0 scale-95 transition-opacity transition-transform duration-200 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-2xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Data Pembayaran</h2>
            <form id="editForm" method="POST" action="" class="space-y-4">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="edit_nama" name="nama" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="edit_jumlah_jiwa" class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                        <input type="number" id="edit_jumlah_jiwa" name="jumlah_jiwa" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required min="1">
                    </div>
                    <div>
                        <label for="edit_jenis_zakat" class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                        <select id="edit_jenis_zakat" name="jenis_zakat" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                            <option value="">Pilih Jenis Zakat</option>
                            <option value="beras">Beras</option>
                            <option value="uang">Uang</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_metode_pembayaran" class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <input type="text" id="edit_metode_pembayaran" name="metode_pembayaran" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="edit_total_bayar" class="block text-sm font-medium text-gray-700">Total Bayar (Rp)</label>
                        <input type="number" step="0.01" id="edit_total_bayar" name="total_bayar" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="edit_nominal_dibayar" class="block text-sm font-medium text-gray-700">Nominal Dibayar (Rp)</label>
                        <input type="number" step="0.01" id="edit_nominal_dibayar" name="nominal_dibayar" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="edit_kembalian" class="block text-sm font-medium text-gray-700">Kembalian (Rp)</label>
                        <input type="number" step="0.01" id="edit_kembalian" name="kembalian" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="edit_keterangan" name="keterangan" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" rows="4"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_tanggal_bayar" class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                        <input type="datetime-local" id="edit_tanggal_bayar" name="tanggal_bayar" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                </div>
                <div class="flex justify-end gap-4 mt-4">
                    <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition font-medium">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition font-medium">Batal</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>