<?php
session_start();
include 'dbconnect.php';

// Periksa apakah siswa sudah masuk
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Ambil detail siswa
$stmt = $conn->prepare("SELECT Roll_No, Name FROM Students WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($roll_no, $name);
$stmt->fetch();
$stmt->close();

// Pertama, mari kita lihat kolom apa saja yang ada di tabel Penalty
$test_query = "DESCRIBE Penalty";
$test_result = $conn->query($test_query);
$penalty_columns = [];
if ($test_result) {
    while ($row = $test_result->fetch_assoc()) {
        $penalty_columns[] = $row['Field'];
    }
}

// Tangani pengiriman pembayaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['issued_id'])) {
    $issued_id = (int)$_POST['issued_id'];
    
    // Perbarui status denda menjadi lunas - kita akan menggunakan nama kolom yang benar setelah kita mengetahuinya
    $stmt = $conn->prepare("UPDATE Penalty SET status = 'Paid' WHERE issued_id = ?");
    $stmt->bind_param("i", $issued_id);
    $stmt->execute();
    $stmt->close();
    
    $success = "Pembayaran berhasil diproses!";
}

// Ambil catatan denda untuk siswa ini - gunakan kueri sederhana terlebih dahulu
$penalties = [];
if (!empty($penalty_columns)) {
    // Gunakan kolom pertama yang sepertinya merupakan pengenal siswa
    $student_column = null;
    foreach ($penalty_columns as $col) {
        if (strpos(strtolower($col), 'student') !== false || strpos(strtolower($col), 'user') !== false || strpos(strtolower($col), 'roll') !== false) {
            $student_column = $col;
            break;
        }
    }
    
    if ($student_column) {
        $stmt = $conn->prepare("SELECT * FROM Penalty WHERE $student_column = ?");
        $stmt->bind_param("i", $roll_no);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $penalties[] = $row;
        }
        $stmt->close();
    }
}

// Hitung total jumlah yang tertunda
$total_pending = 0;
foreach ($penalties as $penalty) {
    if (strtolower($penalty['status'] ?? '') === 'pending') {
        $total_pending += $penalty['amount'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Denda Saya - Sistem Manajemen Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-100 to-cyan-100 min-h-screen text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors">
    <div class="flex flex-col items-center min-h-screen py-10">
        <div class="w-full max-w-6xl p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl animate-fadeInUp">
            <div class="flex justify-between items-center mb-6">
                <a href="student_dashboard.php" class="inline-flex items-center gap-2 text-blue-600 dark:text-cyan-400 font-semibold hover:underline px-4 py-2 rounded-lg bg-blue-50 dark:bg-cyan-900 hover:bg-blue-100 dark:hover:bg-cyan-800 transition">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dasbor
                </a>
                <button onclick="document.body.classList.toggle('dark')" title="Alihkan mode gelap" class="text-blue-400 hover:text-blue-600 text-xl focus:outline-none px-3 py-2 rounded-lg bg-blue-50 dark:bg-cyan-900 hover:bg-blue-100 dark:hover:bg-cyan-800 transition">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="flex flex-col items-center mb-8">
                <span class="text-4xl text-red-500 dark:text-red-400 mb-2"><i class="fas fa-exclamation-triangle"></i></span>
                <h1 class="text-3xl font-bold text-red-700 dark:text-red-400 mb-2 text-center">Denda Saya</h1>
                <p class="text-gray-600 dark:text-gray-300 text-center">Lihat dan bayar denda keterlambatan buku Anda</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg text-center font-semibold dark:bg-green-900 dark:text-green-200">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-blue-50 dark:bg-gray-700 rounded-xl p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-300">Total Denda</h3>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400"><?php echo count($penalties); ?></p>
                        </div>
                        <span class="text-3xl text-blue-500"><i class="fas fa-list"></i></span>
                    </div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-gray-700 rounded-xl p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-yellow-600 dark:text-yellow-300">Jumlah Tertunda</h3>
                            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">Rp<?php echo $total_pending; ?></p>
                        </div>
                        <span class="text-3xl text-yellow-500"><i class="fas fa-clock"></i></span>
                    </div>
                </div>
                
                <div class="bg-green-50 dark:bg-gray-700 rounded-xl p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-green-600 dark:text-green-300">Telah Dibayar</h3>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-400">
                                Rp<?php echo array_sum(array_column(array_filter($penalties, function($p) { return strtolower($p['status'] ?? '') === 'paid'; }), 'amount')); ?>
                            </p>
                        </div>
                        <span class="text-3xl text-green-500"><i class="fas fa-check-circle"></i></span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl shadow mb-8">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-red-600 dark:bg-red-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">ID Denda</th>
                            <th class="px-4 py-3 text-left font-semibold">ID Pengguna</th>
                            <th class="px-4 py-3 text-left font-semibold">Info Buku</th>
                            <th class="px-4 py-3 text-left font-semibold">Jumlah</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (count($penalties) > 0): ?>
                            <?php foreach ($penalties as $penalty): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($penalty['issued_id'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($penalty['student_id'] ?? $penalty['user_id'] ?? $penalty['roll_no'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-semibold">ID Buku: <?php echo htmlspecialchars($penalty['issued_id'] ?? 'N/A'); ?></span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Referensi Buku Pinjaman</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-red-600 dark:text-red-400">Rp<?php echo htmlspecialchars($penalty['amount'] ?? '0'); ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (strtolower($penalty['status'] ?? '') === 'pending'): ?>
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold dark:bg-yellow-900 dark:text-yellow-200">
                                                Tertunda
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold dark:bg-green-900 dark:text-green-200">
                                                Lunas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (strtolower($penalty['status'] ?? '') === 'pending'): ?>
                                            <form method="post" action="penalty.php" onsubmit="return confirm('Apakah Anda yakin ingin membayar Rp<?php echo $penalty['amount'] ?? 0; ?> untuk denda ini?');" class="inline">
                                                <input type="hidden" name="issued_id" value="<?php echo $penalty['issued_id'] ?? ''; ?>">
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2">
                                                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-green-600 dark:text-green-400 font-semibold flex items-center gap-2">
                                                <i class="fas fa-check-circle"></i> Lunas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <span class="text-4xl mb-2">🎉</span>
                                        <p class="text-lg font-semibold">Tidak ada denda yang ditemukan!</p>
                                        <p class="text-sm">Anda telah menyelesaikan semua kewajiban perpustakaan Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pending > 0): ?>
                <div class="bg-yellow-50 dark:bg-gray-700 rounded-xl p-6 shadow-md">
                    <h3 class="text-lg font-semibold text-yellow-700 dark:text-yellow-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i> Informasi Pembayaran
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-300 mb-2">
                                <strong>Total Jumlah Tertunda:</strong> Rp<?php echo $total_pending; ?>
                            </p>
                            <p class="text-gray-600 dark:text-gray-300">
                                • Klik "Bayar Sekarang" di sebelah setiap denda untuk membayar secara individual<br>
                                • Pembayaran diproses secara instan<br>
                                • Simpan konfirmasi pembayaran Anda sebagai catatan
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-300 mb-2">
                                <strong>Metode Pembayaran:</strong>
                            </p>
                            <p class="text-gray-600 dark:text-gray-300">
                                • Pembayaran daring (simulasi)<br>
                                • Pembayaran tunai di loket perpustakaan<br>
                                • Hubungi staf perpustakaan untuk bantuan
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(40px);} to { opacity: 1; transform: translateY(0);} }
        .animate-fadeInUp { animation: fadeInUp 0.7s; }
    </style>
</body>
</html>