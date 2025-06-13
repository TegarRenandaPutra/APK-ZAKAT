<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ambil data beras dari API
$beras_data = [];
$beras_error = null;
$api_url_beras = 'http://localhost:5000/beras';
$ch_beras = curl_init($api_url_beras);
curl_setopt($ch_beras, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_beras, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response_beras = curl_exec($ch_beras);
$http_code_beras = curl_getinfo($ch_beras, CURLINFO_HTTP_CODE);
curl_close($ch_beras);
if ($http_code_beras === 200) {
    $beras_data = json_decode($response_beras, true);
} else {
    $beras_error = 'Gagal mengambil data beras: HTTP ' . $http_code_beras;
}

// Proses pengiriman pembayaran
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    // Tambahkan keterangan otomatis
    if ($_POST['jenis_zakat'] === 'beras' && isset($_POST['beras_pilihan']) && !empty($_POST['beras_pilihan'])) {
        $id_beras = $_POST['beras_pilihan'];
        $harga_beras = null;
        foreach ($beras_data as $beras) {
            if ($beras['id'] == $id_beras) {
                $harga_beras = $beras['harga'];
                break;
            }
        }
        if (!$harga_beras) {
            $error = "Error: ID beras tidak valid!";
        } else {
            $total_bayar_beras = 3.5 * floatval($harga_beras) * $data['jumlah_jiwa'];
            $data['total_bayar'] = $total_bayar_beras;
            $data['keterangan'] = "Beras ID $id_beras: " . (3.5 * $data['jumlah_jiwa']) . " Liter";
        }
    } elseif ($_POST['jenis_zakat'] === 'uang' && isset($_POST['pendapatan_tahunan'])) {
        $pendapatan = floatval($_POST['pendapatan_tahunan']);
        $total_bayar_uang = $pendapatan * 0.025;
        $data['total_bayar'] = $total_bayar_uang;
        $data['keterangan'] = "Uang: 2.5% dari Rp " . number_format($pendapatan, 2);
    }

    if (!$error) {
        $api_url = 'http://localhost:5000/pembayaran';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            file_put_contents('debug.log', "HTTP Code: $http_code\nResponse: $response\nData Sent: " . json_encode($data) . "\n\n", FILE_APPEND);
        }
        curl_close($ch);

        if ($http_code === 201) {
            $success = "Pembayaran berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan pembayaran: HTTP $http_code\nResponse: $response";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melakukan Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit-id.js" crossorigin="anonymous"></script> <!-- Ganti dengan ID Kit FontAwesome Anda -->
    <style>
        .input-prefix {
            @apply absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 pointer-events-none;
        }
        .input-with-prefix {
            @apply pl-10;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const jenisZakat = document.getElementById('jenis_zakat');
            const berasSection = document.getElementById('beras_section');
            const pendapatanSection = document.getElementById('pendapatan_section');
            const berasPilihan = document.getElementById('beras_pilihan');
            const totalBayar = document.getElementById('total_bayar');
            const kembalian = document.getElementById('kembalian');

            if (berasPilihan.options.length > 1) {
                berasPilihan.removeAttribute('disabled');
            }

            function updateTotal() {
                const jumlahJiwa = parseFloat(document.getElementById('jumlah_jiwa').value) || 0;
                const nominalDibayar = parseFloat(document.getElementById('nominal_dibayar').value) || 0;

                if (jenisZakat.value === 'beras' && berasPilihan.value) {
                    const hargaBeras = parseFloat(berasPilihan.options[berasPilihan.selectedIndex].dataset.harga) || 0;
                    const total = jumlahJiwa * 3.5 * hargaBeras;
                    totalBayar.value = total.toFixed(2);
                } else if (jenisZakat.value === 'uang') {
                    const pendapatan = parseFloat(document.getElementById('pendapatan_tahunan').value) || 0;
                    const total = pendapatan * 0.025;
                    totalBayar.value = total.toFixed(2);
                } else {
                    totalBayar.value = '0.00';
                }
                kembalian.value = (nominalDibayar - parseFloat(totalBayar.value) || 0).toFixed(2);
            }

            jenisZakat.addEventListener('change', function() {
                berasSection.classList.toggle('hidden', this.value !== 'beras');
                pendapatanSection.classList.toggle('hidden', this.value !== 'uang');
                ..

                if (this.value === 'beras' && berasPilihan.options.length > 1) {
                    berasPilihan.removeAttribute('disabled');
                } else {
                    berasPilihan.setAttribute('disabled', 'disabled');
                }
                updateTotal();
            });

            document.getElementById('jumlah_jiwa').addEventListener('input', updateTotal);
            berasPilihan.addEventListener('change', updateTotal);
            document.getElementById('pendapatan_tahunan').addEventListener('input', updateTotal);
            document.getElementById('nominal_dibayar').addEventListener('input', updateTotal);
        });
    </script>
</head>
<body class="bg-blue-50 min-h-screen font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-xl rounded-xl w-full max-w-2xl mx-auto overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-6 bg-teal-600 text-white">
                <div class="flex items-center">
                    <i class="fas fa-hand-holding-heart text-3xl mr-3 text-teal-200"></i>
                    <h1 class="text-2xl font-bold">Pembayaran Zakat</h1>
                </div>
                <a href="dashboard.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">Kembali</a>
            </div>

            <!-- Content -->
            <div class="p-8">
                <?php if ($beras_error):702
                    <div class="bg-red-100 text-red-800 border border-red-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($beras_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-800 border border-red-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-teal-100 text-teal-800 border border-teal-300 rounded-lg p-4 mb-6">
                        <?= htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form id="paymentForm" method="post" action="" class="space-y-6">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="nama" name="nama" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="jumlah_jiwa" class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                        <input type="number" id="jumlah_jiwa" name="jumlah_jiwa" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required min="1">
                    </div>
                    <div>
                        <label for="jenis_zakat" class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                        <select id="jenis_zakat" name="jenis_zakat" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                            <option value="">Pilih Jenis Zakat</option>
                            <option value="beras">Beras</option>
                            <option value="uang">Uang</option>
                        </select>
                    </div>
                    <div id="beras_section" class="hidden">
                        <label for="beras_pilihan" class="block text-sm font-medium text-gray-700">Pilih Jenis Beras</label>
                        <select id="beras_pilihan" name="beras_pilihan" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Pilih Ber    as</option>
                            <?php if (!empty($beras_data)): ?>
                                <?php foreach ($beras_data as $beras): ?>
                                    <option value="<?= htmlspecialchars($beras['id']); ?>" data-harga="<?= htmlspecialchars($beras['harga']); ?>">
                                        ID <?= htmlspecialchars($beras['id']) ?> - Rp <?= number_format($beras['harga'], 2, ',', '.'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada data beras</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div id="pendapatan_section" class="hidden">
                        <label for="pendapatan_tahunan" class="block text-sm font-medium text-gray-700">Pendapatan Tahunan</label>
                        <div class="mt-1 relative">
                            <span class="input-prefix">Rp</span>
                            <input type="number" id="pendapatan_tahunan" name="pendapatan_tahunan" class="input-with-prefix w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" min="0">
                        </div>
                    </div>
                    <div>
                        <label for="metode_pembayaran" class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <input type="text" id="metode_pembayaran" name="metode_pembayaran" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div>
                        <label for="total_bayar" class="block text-sm font-medium text-gray-700">Total Bayar</label>
                        <div class="mt-1 relative">
                            <span class="input-prefix">Rp</span>
                            <input type="number" step="0.01" id="total_bayar" name="total_bayar" class="input-with-prefix w-full p-3 border border-gray-300 rounded-lg bg-gray-100" readonly required>
                        </div>
                    </div>
                    <div>
                        <label for="nominal_dibayar" class="block text-sm font-medium text-gray-700">Nominal Dibayar</label>
                        <div class="mt-1 relative">
                            <span class="input-prefix">Rp</span>
                            <input type="number" step="0.01" id="nominal_dibayar" name="nominal_dibayar" class="input-with-prefix w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required min="0">
                        </div>
                    </div>
                    <div>
                        <label for="kembalian" class="block text-sm font-medium text-gray-700">Kembalian</label>
                        <div class="mt-1 relative">
                            <span class="input-prefix">Rp</span>
                            <input type="number" step="0.01" id="kembalian" name="kembalian" class="input-with-prefix w-full p-3 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                    </div>
                    <div>
                        <label for="tanggal_bayar" class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                        <input type="datetime-local" id="tanggal_bayar" name="tanggal_bayar" class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                    </div>
                    <div class="flex justify-end space-x-4 mt-8">
                        <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition font-medium">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>