<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Ambil id pengguna dari tabel users
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Data pengguna tidak ditemukan.");
}
$stmt->close();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['issued_id'])) {
    $issued_id = (int)$_POST['issued_id'];

    $stmt = $conn->prepare("SELECT * FROM Renewal WHERE issued_id = ? AND status IN ('pending', 'renewed')");
    $stmt->bind_param("i", $issued_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = "Anda sudah memiliki permintaan perpanjangan yang tertunda atau disetujui untuk buku ini.";
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO Renewal (student_id, book_id, issued_id, renewal_date, status) VALUES (?, (SELECT book_id FROM IssuedBooks WHERE issued_id = ?), ?, NOW(), 'pending')");
        $stmt->bind_param("iii", $user_id, $issued_id, $issued_id);
        if ($stmt->execute()) {
            $success = "Permintaan perpanjangan berhasil dikirim. Silakan tunggu persetujuan admin.";
        } else {
            $error = "Kesalahan saat mengirim permintaan perpanjangan: " . $conn->error;
        }
        $stmt->close();
    }
}

$sql = "SELECT ib.issued_id, c.book_id, c.title, c.author, c.publisher, c.year, ib.issue_date, ib.due_date
        FROM IssuedBooks ib
        JOIN Catalog c ON ib.book_id = c.book_id
        WHERE ib.student_id = ? AND ib.due_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perpanjangan Buku - Pengguna</title>
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
        <div class="w-full max-w-5xl p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl animate-fadeInUp">
            
            <div class="flex justify-between items-center mb-6">
                <a href="student_dashboard.php" class="inline-flex items-center gap-2 text-blue-600 dark:text-cyan-400 font-semibold hover:underline px-4 py-2 rounded-lg bg-blue-50 dark:bg-cyan-900 transition">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dasbor
                </a>
                <button id="theme-toggle" class="text-blue-400 text-xl focus:outline-none"><i id="theme-icon" class="fas fa-moon"></i></button>
            </div>

            <h2 class="text-2xl font-bold text-center mb-6 text-blue-700 dark:text-cyan-400">Perpanjangan Buku Pinjaman</h2>

            <?php if ($error): ?><div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-center"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-center"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="overflow-x-auto rounded-xl shadow mb-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Judul Buku</th>
                            <th class="px-4 py-3 text-left">Penulis</th>
                            <th class="px-4 py-3 text-left">Tanggal Pinjam</th>
                            <th class="px-4 py-3 text-left">Tenggat Waktu</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['author']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['issue_date']); ?></td>
                                <td class="px-4 py-3 text-red-500 font-medium"><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="">
                                        <input type="hidden" name="issued_id" value="<?php echo $row['issued_id']; ?>">
                                        <button type="submit" class="px-4 py-1.5 bg-blue-500 text-white font-semibold rounded-lg shadow hover:bg-blue-600 transition text-sm">Ajukan Perpanjangan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">Tidak ada buku yang mendekati masa tenggat untuk diperpanjang.</td>
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