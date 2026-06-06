<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!file_exists('dbconnect.php')) {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'><b>Waduh!</b> File 'dbconnect.php' belum ada nih. Coba dicek lagi ya.</div>");
}
include 'dbconnect.php';

// Redirect jika sudah login
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit();
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/admin_dashboard.php");
    exit();
}

$admin_error = '';
$user_error = '';

// Proses Login Admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_id'])) {
    $admin_id = $conn->real_escape_string($_POST['admin_id']);
    $password = $_POST['password'];
    $sql = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($password === $row['password']) {
            $_SESSION['admin_id'] = $row['admin_id'];
            header("Location: admin/admin_dashboard.php");
            exit();
        } else {
            $admin_error = "ID Admin atau kata sandinya salah, coba cek lagi deh.";
        }
    } else {
        $admin_error = "ID Admin atau kata sandinya salah, coba cek lagi deh.";
    }
}

// Proses Login Pengguna
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $user_error = "Nama pengguna atau kata sandi salah.";
        }
    } else {
        $user_error = "Nama pengguna atau kata sandi salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Perpustakaan - Masuk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FIX BUG 1: Aktifkan darkMode: 'class' agar dark mode Tailwind bisa bekerja -->
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Cek dark mode sebelum render biar tidak kedip -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp { animation: fadeInUp 0.7s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-cyan-100 min-h-screen flex flex-col dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300">

<div class="flex-1 flex flex-col items-center justify-center py-8 px-4">
    <div class="w-full max-w-3xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl animate-fadeInUp relative p-6 md:p-10 backdrop-blur-sm">

        <!-- Tombol Dark Mode -->
        <button id="theme-toggle" title="Alihkan mode gelap" class="absolute top-6 right-6 text-cyan-500 hover:text-blue-600 text-2xl focus:outline-none transition-colors">
            <i id="theme-icon" class="fas fa-moon"></i>
        </button>

        <!-- Header -->
        <div class="flex flex-col items-center mb-8">
            <span class="text-6xl text-blue-500 dark:text-cyan-400 mb-3"><i class="fas fa-book-open-reader"></i></span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-center text-blue-700 dark:text-cyan-400 mb-3 tracking-tight">Sistem Perpustakaan</h1>
            <p class="text-base text-gray-600 dark:text-gray-300 text-center max-w-xl">
                Kelola perpustakaan jadi lebih gampang! Mulai dari pinjam-kembali buku sampai pantau notifikasi terbaru. Praktis, aman, dan sat-set buat pengguna maupun admin.
            </p>
        </div>

        <!-- Grid Fitur -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="flex flex-col items-center bg-blue-50 dark:bg-gray-700/50 rounded-xl p-4 shadow-sm border border-blue-100 dark:border-gray-600">
                <span class="text-2xl text-blue-500 mb-2"><i class="fas fa-user-shield"></i></span>
                <span class="font-bold text-blue-700 dark:text-cyan-300 text-sm">Panel Admin</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">Atur katalog buku, data pengguna, dan validasi pinjaman.</span>
            </div>
            <div class="flex flex-col items-center bg-green-50 dark:bg-gray-700/50 rounded-xl p-4 shadow-sm border border-green-100 dark:border-gray-600">
                <span class="text-2xl text-green-500 mb-2"><i class="fas fa-users"></i></span>
                <span class="font-bold text-green-700 dark:text-green-300 text-sm">Portal Pengguna</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">Cari referensi, ajukan pinjaman, dan cek riwayat bukumu.</span>
            </div>
            <div class="flex flex-col items-center bg-yellow-50 dark:bg-gray-700/50 rounded-xl p-4 shadow-sm border border-yellow-100 dark:border-gray-600">
                <span class="text-2xl text-yellow-500 mb-2"><i class="fas fa-bell"></i></span>
                <span class="font-bold text-yellow-700 dark:text-yellow-300 text-sm">Notifikasi Pintar</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">Pengingat otomatis biar kamu nggak telat balikin buku.</span>
            </div>
        </div>

        <!-- Form Login -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Login Admin -->
            <!-- FIX BUG 2: action="" supaya submit ke index.php sendiri, bukan ke login.php -->
            <div class="bg-blue-50/60 dark:bg-gray-700/30 rounded-xl p-6 border border-blue-100 dark:border-gray-600 flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-blue-600 dark:text-cyan-300 mb-4 text-center flex items-center justify-center gap-2">
                        <i class="fas fa-user-shield"></i> Masuk Admin
                    </h2>
                    <form action="" method="post" class="space-y-4">
                        <input type="text" name="admin_id" placeholder="ID Admin" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-800 dark:text-gray-100 transition" />
                        <input type="password" name="password" placeholder="Kata Sandi" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-800 dark:text-gray-100 transition" />
                        <button type="submit"
                            class="w-full py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-semibold rounded-lg shadow hover:from-blue-600 hover:to-cyan-600 transition tracking-wide flex items-center justify-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Login Admin
                        </button>
                    </form>
                </div>
                <?php if ($admin_error): ?>
                <div class="mt-4 p-2.5 bg-red-100 text-red-700 rounded-lg text-center text-sm font-medium dark:bg-red-900/50 dark:text-red-200 border border-red-200 dark:border-red-800 animate-pulse">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($admin_error); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Login Pengguna -->
            <!-- FIX BUG 2: action="" supaya submit ke index.php sendiri, bukan ke login.php -->
            <div class="bg-green-50/60 dark:bg-gray-700/30 rounded-xl p-6 border border-green-100 dark:border-gray-600 flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-green-600 dark:text-green-300 mb-4 text-center flex items-center justify-center gap-2">
                        <i class="fas fa-user"></i> Masuk Pengguna
                    </h2>
                    <form action="" method="post" class="space-y-4">
                        <input type="text" name="username" placeholder="ID Pengguna" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 dark:bg-gray-800 dark:text-gray-100 transition" />
                        <input type="password" name="password" placeholder="Kata Sandi" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 dark:bg-gray-800 dark:text-gray-100 transition" />
                        <button type="submit"
                            class="w-full py-2.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-lg shadow hover:from-green-600 hover:to-emerald-600 transition tracking-wide flex items-center justify-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Login Pengguna
                        </button>
                    </form>
                </div>
                <?php if ($user_error): ?>
                <div class="mt-4 p-2.5 bg-red-100 text-red-700 rounded-lg text-center text-sm font-medium dark:bg-red-900/50 dark:text-red-200 border border-red-200 dark:border-red-800 animate-pulse">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($user_error); ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Tombol Daftar -->
        <div class="flex justify-center mt-8">
            <a href="signup.php" class="py-2.5 px-8 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-lg shadow-md hover:from-emerald-600 hover:to-teal-600 transition tracking-wide text-center flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Belum punya akun? Daftar di sini
            </a>
        </div>

    </div>
</div>

<footer class="w-full text-center py-4 text-gray-500 dark:text-gray-400 text-xs mt-auto">
    &copy; <?php echo date('Y'); ?> Sistem Manajemen Perpustakaan. Hak Cipta Dilindungi.
</footer>

<script>
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    function updateIcon() {
        if (document.documentElement.classList.contains('dark')) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }

    updateIcon();

    themeToggleBtn.addEventListener('click', function () {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        updateIcon();
    });
</script>
</body>
</html>