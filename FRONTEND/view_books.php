<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql = "SELECT * FROM Catalog WHERE title LIKE '%$search_safe%' OR author LIKE '%$search_safe%' OR publisher LIKE '%$search_safe%' OR book_id LIKE '%$search_safe%' ORDER BY title ASC";
} else { 
    $sql = "SELECT * FROM Catalog ORDER BY title ASC";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Buku - Sistem Manajemen Perpustakaan</title>
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
<body class="bg-gradient-to-br from-blue-100 to-cyan-100 min-h-screen text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors">
    <div class="flex flex-col items-center min-h-screen py-10">
        <div class="w-full max-w-6xl p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl animate-fadeInUp">
            <div class="flex justify-between items-center mb-6">
                <a href="student_dashboard.php" class="inline-flex items-center gap-2 text-blue-600 dark:text-cyan-400 font-semibold hover:underline px-4 py-2 rounded-lg bg-blue-50 dark:bg-cyan-900 hover:bg-blue-100 dark:hover:bg-cyan-800 transition">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dasbor
                </a>
                <button id="theme-toggle" title="Alihkan mode gelap" class="text-blue-400 hover:text-blue-600 text-xl focus:outline-none px-3 py-2 rounded-lg bg-blue-50 dark:bg-cyan-900 hover:bg-blue-100 dark:hover:bg-cyan-800 transition">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="mt-6 w-full max-w-xl mb-6">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari judul, penulis, penerbit, atau ID buku..." class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                    <button type="submit" class="px-5 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">Cari</button>
                    <?php if(!empty($search)): ?>
                    <a href="view_books.php" class="px-5 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-green-50 dark:bg-gray-700/50 rounded-xl p-6 shadow-md border dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-green-600 dark:text-green-300">Total Buku</h3>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-400"><?php echo $result ? $result->num_rows : 0; ?></p>
                        </div>
                        <span class="text-3xl text-green-500"><i class="fas fa-books"></i></span>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl p-6 shadow-md border dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-300">Salinan Tersedia</h3>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                                <?php 
                                $total_copies = 0;
                                if ($result) {
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) { $total_copies += $row['copies_available']; }
                                    $result->data_seek(0);
                                }
                                echo $total_copies;
                                ?>
                            </p>
                        </div>
                        <span class="text-3xl text-blue-500"><i class="fas fa-layer-group"></i></span>
                    </div>
                </div>
                <div class="bg-purple-50 dark:bg-gray-700/50 rounded-xl p-6 shadow-md border dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-purple-600 dark:text-purple-300">Kategori</h3>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                                <?php 
                                $publishers = [];
                                if ($result) {
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if (!in_array($row['publisher'], $publishers)) { $publishers[] = $row['publisher']; }
                                    }
                                    $result->data_seek(0);
                                }
                                echo count($publishers);
                                ?>
                            </p>
                        </div>
                        <span class="text-3xl text-purple-500"><i class="fas fa-tags"></i></span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl shadow mb-8">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-green-600 dark:bg-green-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">ID Buku</th>
                            <th class="px-4 py-3 text-left font-semibold">Judul</th>
                            <th class="px-4 py-3 text-left font-semibold">Penulis</th>
                            <th class="px-4 py-3 text-left font-semibold">Penerbit</th>
                            <th class="px-4 py-3 text-left font-semibold">Tahun</th>
                            <th class="px-4 py-3 text-left font-semibold">Salinan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($row['book_id']); ?></td>
                                <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['author']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['publisher']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['year']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold dark:bg-green-900 dark:text-green-200">
                                        <?php echo htmlspecialchars($row['copies_available']); ?> salinan
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">Buku tidak ditemukan!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        function updateIcon() {
            if (document.documentElement.classList.contains('dark')) {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
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