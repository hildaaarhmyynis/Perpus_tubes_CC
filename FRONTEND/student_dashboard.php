<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Ambil detail pengguna dari tabel users
$stmt = $conn->prepare("SELECT id, name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $name);
$stmt->fetch();
$stmt->close();

// Ambil Roll_No dari tabel students berdasarkan username
$roll_no = null;
$stmt = $conn->prepare("SELECT Roll_No FROM Students WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

// Ambil notifikasi (pakai Roll_No)
$notifications = [];
if ($roll_no) {
    $stmt = $conn->prepare("SELECT Message, sent_date FROM Notifications WHERE Roll_No = ? ORDER BY sent_date DESC LIMIT 5");
    $stmt->bind_param("i", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $notifications[] = $row;
    $stmt->close();
}

// Ambil buku yang sedang dipinjam (pakai Roll_No)
$issued_books = [];
if ($roll_no) {
    $stmt = $conn->prepare("SELECT c.title, c.author, ib.issue_date, ib.due_date FROM IssuedBooks ib JOIN Catalog c ON ib.book_id = c.book_id WHERE ib.student_id = ? AND ib.due_date >= NOW()");
    $stmt->bind_param("i", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $issued_books[] = $row;
    $stmt->close();
}

// Ambil buku yang pernah dipinjam (pakai Roll_No)
$prev_books = [];
if ($roll_no) {
    $stmt = $conn->prepare("SELECT c.title, c.author, ib.issue_date, ib.due_date FROM IssuedBooks ib JOIN Catalog c ON ib.book_id = c.book_id WHERE ib.student_id = ? AND ib.due_date < NOW()");
    $stmt->bind_param("i", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $prev_books[] = $row;
    $stmt->close();
}

// Jumlah total buku di katalog
$books_count = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM Catalog");
if ($res && $row = $res->fetch_assoc()) $books_count = $row['cnt'];

$display_name = $name ?: $username;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Pengguna - Sistem Manajemen Perpustakaan</title>
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
<body class="bg-gradient-to-br from-blue-100 to-cyan-100 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-100 transition-colors duration-300">
<div class="flex flex-col items-center min-h-screen py-10 px-4">
<div class="w-full max-w-4xl p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl animate-fadeInUp relative">

    <!-- Tombol Dark Mode -->
    <button id="theme-toggle" title="Alihkan mode gelap"
        class="absolute top-6 right-6 text-cyan-500 hover:text-blue-600 text-2xl focus:outline-none transition-colors">
        <i id="theme-icon" class="fas fa-moon"></i>
    </button>

    <!-- Header Profil -->
    <div class="flex flex-col items-center mb-8">
        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-cyan-400 flex items-center justify-center text-white text-4xl font-bold shadow-lg border-4 border-blue-300 dark:border-cyan-700 mb-3">
            <?php echo strtoupper(mb_substr($display_name, 0, 1)); ?>
        </div>
        <h1 class="text-3xl font-bold text-blue-700 dark:text-cyan-400 mb-1">
            Selamat datang, <?php echo htmlspecialchars($display_name); ?>!
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">@<?php echo htmlspecialchars($username); ?></p>
    </div>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-blue-100 dark:border-gray-600 flex items-center gap-4">
            <span class="text-3xl text-blue-400"><i class="fas fa-book-open"></i></span>
            <div>
                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?php echo count($issued_books); ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Sedang Dipinjam</div>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-green-100 dark:border-gray-600 flex items-center gap-4">
            <span class="text-3xl text-green-400"><i class="fas fa-books"></i></span>
            <div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-300"><?php echo $books_count; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Buku</div>
            </div>
        </div>
        <div class="bg-yellow-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-yellow-100 dark:border-gray-600 flex items-center gap-4">
            <span class="text-3xl text-yellow-400"><i class="fas fa-bell"></i></span>
            <div>
                <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300"><?php echo count($notifications); ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Notifikasi</div>
            </div>
        </div>
    </div>

    <!-- Notifikasi & Navigasi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl p-6 shadow border dark:border-gray-600">
            <h2 class="text-lg font-semibold text-blue-600 dark:text-cyan-300 mb-3 flex items-center gap-2">
                <i class="fas fa-bell"></i> Notifikasi
            </h2>
            <ul class="space-y-2 max-h-40 overflow-y-auto">
                <?php if (count($notifications)): foreach ($notifications as $n): ?>
                    <li class="p-2 bg-white dark:bg-gray-800 rounded shadow text-sm flex flex-col">
                        <span><?php echo htmlspecialchars($n['Message']); ?></span>
                        <span class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($n['sent_date']); ?></span>
                    </li>
                <?php endforeach; else: ?>
                    <li class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada notifikasi.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="flex flex-col gap-4">
            <a href="view_books.php" class="flex items-center gap-3 bg-green-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-green-100 dark:border-gray-600 hover:bg-green-100 dark:hover:bg-gray-600 transition">
                <span class="text-2xl text-green-500"><i class="fas fa-search"></i></span>
                <div>
                    <div class="font-semibold text-green-700 dark:text-green-300">Jelajahi Buku</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Cari dan pinjam buku</div>
                </div>
            </a>
            <a href="issue_requests.php" class="flex items-center gap-3 bg-purple-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-purple-100 dark:border-gray-600 hover:bg-purple-100 dark:hover:bg-gray-600 transition">
                <span class="text-2xl text-purple-500"><i class="fas fa-hand-holding-heart"></i></span>
                <div>
                    <div class="font-semibold text-purple-700 dark:text-purple-300">Ajukan Pinjaman</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Ajukan permintaan buku baru</div>
                </div>
            </a>
            <a href="recommended_books.php" class="flex items-center gap-3 bg-yellow-50 dark:bg-gray-700/50 rounded-xl p-5 shadow border border-yellow-100 dark:border-gray-600 hover:bg-yellow-100 dark:hover:bg-gray-600 transition">
                <span class="text-2xl text-yellow-500"><i class="fas fa-star"></i></span>
                <div>
                    <div class="font-semibold text-yellow-700 dark:text-yellow-300">Buku Rekomendasi</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Lihat dan rekomendasikan buku</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Buku Sedang Dipinjam -->
    <div class="bg-cyan-50 dark:bg-gray-700/50 rounded-xl p-6 shadow border dark:border-gray-600 mb-6">
        <h2 class="text-lg font-semibold text-cyan-600 dark:text-cyan-300 mb-3 flex items-center gap-2">
            <i class="fas fa-book-reader"></i> Buku yang Sedang Dipinjam
        </h2>
        <ul class="space-y-2 max-h-48 overflow-y-auto">
            <?php if (count($issued_books)): foreach ($issued_books as $b): ?>
                <li class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow text-sm flex flex-col">
                    <span class="font-semibold"><?php echo htmlspecialchars($b['title']); ?></span>
                    <span class="text-xs text-gray-400 mt-1">
                        Oleh <?php echo htmlspecialchars($b['author']); ?> &bull;
                        Tenggat: <span class="text-red-500 font-semibold"><?php echo htmlspecialchars($b['due_date']); ?></span>
                    </span>
                </li>
            <?php endforeach; else: ?>
                <li class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada buku yang sedang dipinjam.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Riwayat Pinjaman -->
    <div class="bg-purple-50 dark:bg-gray-700/50 rounded-xl p-6 shadow border dark:border-gray-600 mb-6">
        <h2 class="text-lg font-semibold text-purple-600 dark:text-purple-300 mb-3 flex items-center gap-2">
            <i class="fas fa-history"></i> Riwayat Pinjaman
        </h2>
        <ul class="space-y-2 max-h-48 overflow-y-auto">
            <?php if (count($prev_books)): foreach ($prev_books as $b): ?>
                <li class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow text-sm flex flex-col">
                    <span class="font-semibold"><?php echo htmlspecialchars($b['title']); ?></span>
                    <span class="text-xs text-gray-400 mt-1">
                        Oleh <?php echo htmlspecialchars($b['author']); ?> &bull;
                        Dikembalikan: <?php echo htmlspecialchars($b['due_date']); ?>
                    </span>
                </li>
            <?php endforeach; else: ?>
                <li class="text-gray-500 dark:text-gray-400 text-sm">Belum ada riwayat pinjaman.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Denda & Keluar -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between mt-4">
        <a href="penalty.php" class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white font-semibold rounded-lg shadow transition flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> Lihat Denda
        </a>
        <a href="logout.php" class="text-blue-600 dark:text-cyan-400 font-semibold hover:underline flex items-center gap-2">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>

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