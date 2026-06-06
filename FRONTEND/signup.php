<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'dbconnect.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: admin/admin_dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($name) || empty($phone) || empty($username) || empty($password) || empty($confirm)) {
        $error = "Semua kolom wajib diisi ya, jangan sampai ada yang kosong.";
    } elseif ($password !== $confirm) {
        $error = "Kata sandinya nggak cocok nih, coba dicek lagi.";
    } else {
        // Cek username sudah dipakai belum
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Nama pengguna sudah dipakai orang lain. Cari nama yang lain ya!";
            $stmt->close();
        } else {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Mulai transaksi agar kedua insert berhasil atau sama-sama gagal
            $conn->begin_transaction();
            try {
                // Insert ke tabel users (tambah kolom name & phone)
                $s1 = $conn->prepare("INSERT INTO users (username, name, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
                $s1->bind_param("ssss", $username, $name, $phone, $hashed);
                $s1->execute();
                $s1->close();

                // Insert ke tabel students agar bisa login ke dashboard
                $s2 = $conn->prepare("INSERT INTO students (Name, Phone, Username) VALUES (?, ?, ?)");
                $s2->bind_param("sss", $name, $phone, $username);
                $s2->execute();
                $s2->close();

                $conn->commit();
                $success = "Akun berhasil dibuat! Sekarang kamu sudah bisa login.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal membuat akun: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna - Sistem Manajemen Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp { animation: fadeInUp 0.7s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-cyan-100 dark:bg-gray-900 min-h-screen flex items-center justify-center transition-colors duration-300 px-4">

    <div class="w-full max-w-md p-8 bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl animate-fadeInUp backdrop-blur-sm relative">

        <!-- Tombol Dark Mode -->
        <button id="theme-toggle" title="Alihkan mode gelap"
            class="absolute top-6 right-6 text-cyan-500 hover:text-blue-600 text-xl focus:outline-none transition-colors">
            <i id="theme-icon" class="fas fa-moon"></i>
        </button>

        <h2 class="text-2xl font-bold text-blue-700 dark:text-cyan-400 mb-1 text-center">Daftar Pengguna Baru</h2>
        <p class="text-gray-500 dark:text-gray-300 text-sm mb-6 text-center">Isi data di bawah ini buat bikin akun perpus kamu ya.</p>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-200 border border-red-200 dark:border-red-800 rounded-lg text-center text-sm font-semibold">
                <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-200 border border-green-200 dark:border-green-800 rounded-lg text-center text-sm font-semibold">
                <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($success); ?>
                <div class="mt-2">
                    <a href="index.php" class="underline font-bold">Klik di sini untuk login</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="signup.php" class="space-y-4">
            <div>
                <label class="block font-semibold mb-1 text-sm text-gray-700 dark:text-gray-200">Nama Lengkap:</label>
                <input type="text" name="name" required placeholder="Masukkan nama lengkapmu"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:text-gray-100 transition" />
            </div>
            <div>
                <label class="block font-semibold mb-1 text-sm text-gray-700 dark:text-gray-200">Nomor Telepon / WA:</label>
                <input type="text" name="phone" required placeholder="Contoh: 081234567890"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:text-gray-100 transition" />
            </div>
            <div>
                <label class="block font-semibold mb-1 text-sm text-gray-700 dark:text-gray-200">Nama Pengguna (Username):</label>
                <input type="text" name="username" required placeholder="Buat username login"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:text-gray-100 transition" />
            </div>
            <div>
                <label class="block font-semibold mb-1 text-sm text-gray-700 dark:text-gray-200">Kata Sandi:</label>
                <input type="password" name="password" required placeholder="Buat kata sandi baru"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:text-gray-100 transition" />
            </div>
            <div>
                <label class="block font-semibold mb-1 text-sm text-gray-700 dark:text-gray-200">Konfirmasi Kata Sandi:</label>
                <input type="password" name="confirm_password" required placeholder="Ulangi kata sandi"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:text-gray-100 transition" />
            </div>

            <button type="submit"
                class="w-full py-2.5 mt-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-lg shadow transition tracking-wide flex items-center justify-center gap-2">
                <i class="fas fa-user-plus"></i> Buat Akun
            </button>
        </form>

        <div class="text-center mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
            <a href="index.php" class="text-blue-600 dark:text-cyan-400 text-sm font-semibold hover:underline">
                <i class="fas fa-arrow-left text-xs mr-1"></i> Sudah punya akun? Masuk di sini
            </a>
        </div>
    </div>

    <script>
        const btn  = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');

        function updateIcon() {
            icon.className = document.documentElement.classList.contains('dark') ? 'fas fa-sun' : 'fas fa-moon';
        }
        updateIcon();

        btn.addEventListener('click', function () {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateIcon();
        });
    </script>
</body>
</html>