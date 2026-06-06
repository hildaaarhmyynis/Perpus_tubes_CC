<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
// Query disesuaikan dengan skema tabel users kamu
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $reason = trim($_POST['reason']);
    if (empty($title)) {
        $error = "Judul buku wajib diisi.";
    } else {
        $stmt = $conn->prepare("INSERT INTO recommendations (roll_no, title, author, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $author, $reason);
        if ($stmt->execute()) {
            $success = "Buku berhasil direkomendasikan!";
        } else {
            $error = "Kesalahan: " . $conn->error;
        }
        $stmt->close();
    }
}

$recs = [];
$res = $conn->query("SELECT * FROM recommendations ORDER BY recommended_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) { $recs[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekomendasi Buku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-gray-900 dark:to-gray-900 min-h-screen flex flex-col items-center py-10 text-gray-800 dark:text-gray-100 transition-colors">
    
    <div class="w-full max-w-2xl p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl mb-8 relative">
        <button id="theme-toggle" class="absolute top-6 right-6 text-yellow-600 dark:text-yellow-300 text-xl"><i id="theme-icon" class="fas fa-moon"></i></button>
        <h2 class="text-2xl font-bold text-yellow-700 dark:text-yellow-300 mb-4 text-center">Rekomendasikan Buku</h2>
        
        <?php if ($error): ?><div class="mb-4 p-2 bg-red-100 text-red-700 rounded"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-2 bg-green-100 text-green-700 rounded"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        
        <form method="post" class="space-y-4">
            <div>
                <label class="block font-semibold mb-1">Judul Buku <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="w-full px-3 py-2 border-2 border-yellow-200 dark:border-gray-600 rounded-lg dark:bg-gray-700" />
            </div>
            <div>
                <label class="block font-semibold mb-1">Penulis</label>
                <input type="text" name="author" class="w-full px-3 py-2 border-2 border-yellow-200 dark:border-gray-600 rounded-lg dark:bg-gray-700" />
            </div>
            <div>
                <label class="block font-semibold mb-1">Alasan Rekomendasi</label>
                <textarea name="reason" rows="2" class="w-full px-3 py-2 border-2 border-yellow-200 dark:border-gray-600 rounded-lg dark:bg-gray-700"></textarea>
            </div>
            <button type="submit" class="w-full py-2 px-4 bg-gradient-to-r from-yellow-500 to-yellow-400 text-white font-semibold rounded-lg shadow hover:from-yellow-600 text-lg">Kirim Rekomendasi</button>
        </form>
    </div>

    <div class="w-full max-w-3xl p-6 bg-yellow-50 dark:bg-gray-700/50 rounded-2xl shadow-xl border dark:border-gray-600">
        <h2 class="text-xl font-bold text-yellow-700 dark:text-yellow-300 mb-4 text-center">Semua Buku Rekomendasi</h2>
        <div class="grid gap-4">
            <?php if (count($recs)): foreach ($recs as $rec): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="font-semibold text-lg text-yellow-800 dark:text-yellow-200"><?php echo htmlspecialchars($rec['title']); ?></div>
                        <div class="text-yellow-600 dark:text-yellow-300 text-sm mb-1">oleh <?php echo htmlspecialchars($rec['author']); ?></div>
                        <div class="text-gray-600 dark:text-gray-300 text-sm"><?php echo htmlspecialchars($rec['reason']); ?></div>
                    </div>
                    <div class="text-right text-xs text-gray-400 mt-2 md:mt-0">
                        Direkomendasikan oleh ID: <span class="font-bold text-yellow-700 dark:text-yellow-300"><?php echo htmlspecialchars($rec['roll_no']); ?></span>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="text-gray-500 dark:text-gray-400 text-center">Belum ada rekomendasi.</div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-8">
            <a href="student_dashboard.php" class="text-yellow-700 dark:text-yellow-300 font-semibold hover:underline">Kembali ke Dasbor</a>
        </div>
    </div>
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        function updateIcon() {
            if (document.documentElement.classList.contains('dark')) { themeIcon.className = 'fas fa-sun'; } 
            else { themeIcon.className = 'fas fa-moon'; }
        }
        updateIcon();
        themeToggleBtn.addEventListener('click', function() {
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