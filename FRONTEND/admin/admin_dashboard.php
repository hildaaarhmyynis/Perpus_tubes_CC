<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../dbconnect.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

// ── PROSES AKSI POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Tambah Buku
    if (isset($_POST['action']) && $_POST['action'] === 'tambah_buku') {
        $title     = $conn->real_escape_string(trim($_POST['title']));
        $author    = $conn->real_escape_string(trim($_POST['author']));
        $year      = (int)$_POST['year'];
        $publisher = $conn->real_escape_string(trim($_POST['publisher']));
        $copies    = (int)$_POST['copies_available'];
        $isbn      = $conn->real_escape_string(trim($_POST['isbn']));
        $category  = $conn->real_escape_string(trim($_POST['category']));
        $sql = "INSERT INTO catalog (title, author, year, publisher, copies_available, isbn, category)
                VALUES ('$title','$author',$year,'$publisher',$copies,'$isbn','$category')";
        if ($conn->query($sql)) { $message = "Buku berhasil ditambahkan!"; $message_type = 'success'; }
        else { $message = "Gagal menambahkan buku: " . $conn->error; $message_type = 'error'; }
    }

    // Hapus Buku
    if (isset($_POST['action']) && $_POST['action'] === 'hapus_buku') {
        $book_id = (int)$_POST['book_id'];
        if ($conn->query("DELETE FROM catalog WHERE book_id = $book_id")) {
            $message = "Buku berhasil dihapus!"; $message_type = 'success';
        } else { $message = "Gagal menghapus: " . $conn->error; $message_type = 'error'; }
    }

    // Edit Buku
    if (isset($_POST['action']) && $_POST['action'] === 'edit_buku') {
        $book_id   = (int)$_POST['book_id'];
        $title     = $conn->real_escape_string(trim($_POST['title']));
        $author    = $conn->real_escape_string(trim($_POST['author']));
        $year      = (int)$_POST['year'];
        $publisher = $conn->real_escape_string(trim($_POST['publisher']));
        $copies    = (int)$_POST['copies_available'];
        $sql = "UPDATE catalog SET title='$title', author='$author', year=$year,
                publisher='$publisher', copies_available=$copies WHERE book_id=$book_id";
        if ($conn->query($sql)) { $message = "Buku berhasil diperbarui!"; $message_type = 'success'; }
        else { $message = "Gagal memperbarui: " . $conn->error; $message_type = 'error'; }
    }

    // Hapus Pengguna
    if (isset($_POST['action']) && $_POST['action'] === 'hapus_user') {
        $user_id = (int)$_POST['user_id'];
        $conn->query("DELETE FROM users WHERE id = $user_id");
        $message = "Pengguna berhasil dihapus!"; $message_type = 'success';
    }

    // Setujui Pengajuan
    if (isset($_POST['action']) && $_POST['action'] === 'setujui') {
        $req_id     = (int)$_POST['request_id'];
        $student_id = (int)$_POST['student_id'];
        $book_id    = (int)$_POST['book_id'];
        $conn->query("UPDATE issuerequest SET status='approved' WHERE request_id=$req_id");
        $conn->query("UPDATE catalog SET copies_available = copies_available - 1 WHERE book_id=$book_id AND copies_available > 0");
        $due   = date('Y-m-d', strtotime('+14 days'));
        $today = date('Y-m-d');
        $conn->query("INSERT INTO issuedbooks (student_id, book_id, issue_date, due_date) VALUES ($student_id, $book_id, '$today', '$due')");
        $conn->query("INSERT INTO notifications (Roll_No, Message) VALUES ($student_id, 'Pengajuan buku kamu telah disetujui! Silakan ambil bukunya.')");
        $message = "Pengajuan berhasil disetujui!"; $message_type = 'success';
    }

    // Tolak Pengajuan
    if (isset($_POST['action']) && $_POST['action'] === 'tolak') {
        $req_id     = (int)$_POST['request_id'];
        $student_id = (int)$_POST['student_id'];
        $conn->query("UPDATE issuerequest SET status='rejected' WHERE request_id=$req_id");
        $conn->query("INSERT INTO notifications (Roll_No, Message) VALUES ($student_id, 'Maaf, pengajuan buku kamu ditolak oleh admin.')");
        $message = "Pengajuan berhasil ditolak."; $message_type = 'info';
    }

    // Tambah Denda Manual
    // penalty: Fine_ID, User_ID (= users.id), Book_ID, Amount, Status, reason, created_at
    if (isset($_POST['action']) && $_POST['action'] === 'tambah_denda') {
        $user_id = (int)$_POST['user_id'];
        $book_id = (int)$_POST['book_id'];
        $amount  = (float)$_POST['amount'];
        $reason  = $conn->real_escape_string(trim($_POST['reason']));
        $today   = date('Y-m-d');
        // Ambil Roll_No siswa dari users.id untuk notifikasi
        $urow = $conn->query("SELECT u.id, s.Roll_No FROM users u JOIN students s ON u.username = s.Username WHERE u.id = $user_id")->fetch_assoc();
        $roll = $urow ? (int)$urow['Roll_No'] : 0;
        $sql  = "INSERT INTO penalty (User_ID, Book_ID, Amount, Status, reason, created_at)
                 VALUES ($user_id, $book_id, $amount, 'Pending', '$reason', '$today')";
        if ($conn->query($sql)) {
            if ($roll) {
                $conn->query("INSERT INTO notifications (Roll_No, Message) VALUES ($roll, 'Kamu mendapat denda sebesar Rp " . number_format($amount, 0, ',', '.') . ". Alasan: $reason')");
            }
            $message = "Denda berhasil ditambahkan!"; $message_type = 'success';
        } else { $message = "Gagal menambah denda: " . $conn->error; $message_type = 'error'; }
    }

    // Tandai Denda Lunas
    if (isset($_POST['action']) && $_POST['action'] === 'bayar_denda') {
        $fine_id = (int)$_POST['fine_id'];
        $user_id = (int)$_POST['user_id'];
        if ($conn->query("UPDATE penalty SET Status='Paid' WHERE Fine_ID=$fine_id")) {
            // Kirim notifikasi ke Roll_No siswa
            $urow = $conn->query("SELECT s.Roll_No FROM users u JOIN students s ON u.username = s.Username WHERE u.id = $user_id")->fetch_assoc();
            if ($urow) {
                $roll = (int)$urow['Roll_No'];
                $conn->query("INSERT INTO notifications (Roll_No, Message) VALUES ($roll, 'Denda kamu telah ditandai lunas oleh admin. Terima kasih!')");
            }
            $message = "Denda berhasil ditandai lunas!"; $message_type = 'success';
        } else { $message = "Gagal: " . $conn->error; $message_type = 'error'; }
    }

    // Hapus Denda
    if (isset($_POST['action']) && $_POST['action'] === 'hapus_denda') {
        $fine_id = (int)$_POST['fine_id'];
        if ($conn->query("DELETE FROM penalty WHERE Fine_ID=$fine_id")) {
            $message = "Denda berhasil dihapus!"; $message_type = 'success';
        } else { $message = "Gagal menghapus denda: " . $conn->error; $message_type = 'error'; }
    }
}

// ── AMBIL DATA ────────────────────────────────────────────────────
$books   = $conn->query("SELECT * FROM catalog ORDER BY title ASC");
$users   = $conn->query("SELECT u.id, u.username, u.role, s.Name, s.Roll_No
                          FROM users u
                          LEFT JOIN students s ON u.username = s.Username
                          ORDER BY u.id ASC");
$pending = $conn->query("SELECT ir.*, c.title, s.Name as student_name
                          FROM issuerequest ir
                          JOIN catalog c ON ir.book_id = c.book_id
                          JOIN students s ON ir.student_id = s.Roll_No
                          WHERE ir.status = 'pending'
                          ORDER BY ir.request_date ASC");
$all_req = $conn->query("SELECT ir.*, c.title, s.Name as student_name
                          FROM issuerequest ir
                          JOIN catalog c ON ir.book_id = c.book_id
                          JOIN students s ON ir.student_id = s.Roll_No
                          ORDER BY ir.request_date DESC LIMIT 20");

// Data denda — pakai tabel penalty, join users + catalog
$fines = $conn->query("
    SELECT p.Fine_ID, p.User_ID, p.Book_ID, p.Amount, p.Status,
           COALESCE(p.reason, '-') as reason,
           COALESCE(p.created_at, CURDATE()) as created_at,
           u.username,
           COALESCE(s.Name, u.username) as student_name,
           c.title as book_title
    FROM penalty p
    LEFT JOIN users u   ON p.User_ID = u.id
    LEFT JOIN students s ON u.username = s.Username
    LEFT JOIN catalog c  ON p.Book_ID = c.book_id
    ORDER BY p.Fine_ID DESC
    LIMIT 50
");

// Daftar pengguna aktif untuk dropdown form tambah denda
$users_list = $conn->query("
    SELECT u.id, u.username, COALESCE(s.Name, u.username) as display_name
    FROM users u
    LEFT JOIN students s ON u.username = s.Username
    WHERE u.role = 'user'
    ORDER BY display_name ASC
");

$total_books        = $conn->query("SELECT COUNT(*) as c FROM catalog")->fetch_assoc()['c'];
$total_users        = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_pending      = $conn->query("SELECT COUNT(*) as c FROM issuerequest WHERE status='pending'")->fetch_assoc()['c'];
$total_fines_unpaid = $conn->query("SELECT COUNT(*) as c FROM penalty WHERE Status='Pending'")->fetch_assoc()['c'];
$total_fines_amount = $conn->query("SELECT COALESCE(SUM(Amount),0) as total FROM penalty WHERE Status='Pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dasbor Admin - Sistem Manajemen Perpustakaan</title>
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
    @keyframes fadeInUp { from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)} }
    .animate-fadeInUp { animation: fadeInUp 0.6s ease-out; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-blue-100 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-100 transition-colors duration-300">

<!-- NAVBAR -->
<nav class="w-full bg-white dark:bg-gray-800 shadow-md px-6 py-4 flex items-center justify-between sticky top-0 z-50">
    <div class="flex items-center gap-3">
        <span class="text-2xl text-blue-600 dark:text-cyan-400"><i class="fas fa-book-open-reader"></i></span>
        <span class="font-extrabold text-lg text-blue-700 dark:text-cyan-400">Panel Admin</span>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            <i class="fas fa-user-shield mr-1"></i><?php echo htmlspecialchars($admin_id); ?>
        </span>
        <button id="theme-toggle" class="text-cyan-500 hover:text-blue-600 text-xl focus:outline-none transition-colors">
            <i id="theme-icon" class="fas fa-moon"></i>
        </button>
        <a href="../logout.php" class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-8 animate-fadeInUp">

    <!-- Pesan Notifikasi -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-xl text-center font-semibold text-sm
        <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-200 border border-green-200' :
                  ($message_type === 'error'   ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200 border border-red-200' :
                   'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200 border border-blue-200'); ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-info-circle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md flex items-center gap-5 border border-blue-100 dark:border-gray-700">
            <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-2xl text-blue-500">
                <i class="fas fa-book"></i>
            </div>
            <div>
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-300"><?php echo $total_books; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Buku</div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md flex items-center gap-5 border border-green-100 dark:border-gray-700">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center text-2xl text-green-500">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-700 dark:text-green-300"><?php echo $total_users; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Pengguna</div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md flex items-center gap-5 border border-yellow-100 dark:border-gray-700">
            <div class="w-14 h-14 rounded-full bg-yellow-100 dark:bg-yellow-900/50 flex items-center justify-center text-2xl text-yellow-500">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="text-3xl font-bold text-yellow-700 dark:text-yellow-300"><?php echo $total_pending; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pengajuan Menunggu</div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-md flex items-center gap-5 border border-red-100 dark:border-gray-700">
            <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-2xl text-red-500">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="text-3xl font-bold text-red-700 dark:text-red-300"><?php echo $total_fines_unpaid; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Denda Belum Lunas</div>
                <div class="text-xs text-red-400 font-semibold">Rp <?php echo number_format($total_fines_amount, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Tab Navigasi -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button onclick="showTab('buku')" id="tab-buku"
            class="tab-btn px-5 py-2 rounded-lg font-semibold text-sm transition bg-blue-600 text-white shadow">
            <i class="fas fa-book mr-1"></i> Kelola Buku
        </button>
        <button onclick="showTab('pengguna')" id="tab-pengguna"
            class="tab-btn px-5 py-2 rounded-lg font-semibold text-sm transition bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 shadow">
            <i class="fas fa-users mr-1"></i> Kelola Pengguna
        </button>
        <button onclick="showTab('pengajuan')" id="tab-pengajuan"
            class="tab-btn px-5 py-2 rounded-lg font-semibold text-sm transition bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 shadow">
            <i class="fas fa-hand-holding-heart mr-1"></i> Pengajuan Pinjaman
            <?php if ($total_pending > 0): ?>
            <span class="ml-1 bg-red-500 text-white text-xs rounded-full px-2"><?php echo $total_pending; ?></span>
            <?php endif; ?>
        </button>
        <button onclick="showTab('denda')" id="tab-denda"
            class="tab-btn px-5 py-2 rounded-lg font-semibold text-sm transition bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 shadow">
            <i class="fas fa-money-bill-wave mr-1"></i> Kelola Denda
            <?php if ($total_fines_unpaid > 0): ?>
            <span class="ml-1 bg-red-500 text-white text-xs rounded-full px-2"><?php echo $total_fines_unpaid; ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: KELOLA BUKU
    ═══════════════════════════════════════════════════════════ -->
    <div id="tab-content-buku" class="tab-content active">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 mb-6 border border-blue-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-blue-700 dark:text-cyan-300 mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Tambah Buku Baru
            </h2>
            <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="tambah_buku">
                <input type="text" name="title" placeholder="Judul Buku" required
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="text" name="author" placeholder="Penulis" required
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="number" name="year" placeholder="Tahun Terbit" min="1900" max="2099" required
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="text" name="publisher" placeholder="Penerbit" required
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="number" name="copies_available" placeholder="Jumlah Salinan" min="0" required
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="text" name="isbn" placeholder="ISBN (opsional)"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <input type="text" name="category" placeholder="Kategori (opsional)"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-blue-400 outline-none transition" />
                <div class="md:col-span-2 flex items-end">
                    <button type="submit"
                        class="w-full py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-lg shadow transition flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Tambah Buku
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-blue-100 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-blue-700 dark:text-cyan-300 flex items-center gap-2">
                    <i class="fas fa-list"></i> Daftar Buku (<?php echo $total_books; ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3 text-left">Penulis</th>
                            <th class="px-4 py-3 text-left">Penerbit</th>
                            <th class="px-4 py-3 text-left">Tahun</th>
                            <th class="px-4 py-3 text-left">Salinan</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($books && $books->num_rows > 0): while ($b = $books->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-500"><?php echo $b['book_id']; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($b['title']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($b['author']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($b['publisher']); ?></td>
                            <td class="px-4 py-3"><?php echo $b['year']; ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $b['copies_available'] > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'; ?>">
                                    <?php echo $b['copies_available']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 flex gap-2">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($b)); ?>)"
                                    class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-semibold transition">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus buku ini?');" class="inline">
                                    <input type="hidden" name="action" value="hapus_buku">
                                    <input type="hidden" name="book_id" value="<?php echo $b['book_id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-8 text-gray-400">Belum ada buku.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: KELOLA PENGGUNA
    ═══════════════════════════════════════════════════════════ -->
    <div id="tab-content-pengguna" class="tab-content">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-green-100 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-green-700 dark:text-green-300 flex items-center gap-2">
                    <i class="fas fa-users"></i> Daftar Pengguna (<?php echo $total_users; ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Username</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($users && $users->num_rows > 0): while ($u = $users->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 text-gray-500 font-semibold"><?php echo $u['id']; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($u['Name'] ?: '-'); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" onsubmit="return confirm('Hapus pengguna ini?');" class="inline">
                                    <input type="hidden" name="action" value="hapus_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">Belum ada pengguna.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: PENGAJUAN PINJAMAN
    ═══════════════════════════════════════════════════════════ -->
    <div id="tab-content-pengajuan" class="tab-content">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-yellow-100 dark:border-gray-700 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-yellow-700 dark:text-yellow-300 flex items-center gap-2">
                    <i class="fas fa-clock"></i> Menunggu Persetujuan (<?php echo $total_pending; ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-yellow-500 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nama Siswa</th>
                            <th class="px-4 py-3 text-left">Judul Buku</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($pending && $pending->num_rows > 0): while ($r = $pending->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 text-gray-500"><?php echo $r['request_id']; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($r['student_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($r['title']); ?></td>
                            <td class="px-4 py-3 text-xs text-gray-400"><?php echo htmlspecialchars($r['request_date']); ?></td>
                            <td class="px-4 py-3 flex gap-2">
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="setujui">
                                    <input type="hidden" name="request_id" value="<?php echo $r['request_id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $r['student_id']; ?>">
                                    <input type="hidden" name="book_id" value="<?php echo $r['book_id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-check"></i> Setujui
                                    </button>
                                </form>
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="tolak">
                                    <input type="hidden" name="request_id" value="<?php echo $r['request_id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $r['student_id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-times"></i> Tolak
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">Tidak ada pengajuan yang menunggu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i class="fas fa-history"></i> Riwayat Pengajuan (20 terakhir)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Siswa</th>
                            <th class="px-4 py-3 text-left">Buku</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($all_req && $all_req->num_rows > 0): while ($r = $all_req->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 text-gray-500"><?php echo $r['request_id']; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($r['student_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($r['title']); ?></td>
                            <td class="px-4 py-3 text-xs text-gray-400"><?php echo htmlspecialchars($r['request_date']); ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $sc = match($r['status']) {
                                    'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                    'pending'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                    default    => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                };
                                $sl = match($r['status']) {
                                    'approved' => 'Disetujui',
                                    'pending'  => 'Menunggu',
                                    default    => 'Ditolak',
                                };
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $sc; ?>">
                                    <?php echo $sl; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">Belum ada riwayat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB: KELOLA DENDA  (tabel: penalty)
    ═══════════════════════════════════════════════════════════ -->
    <div id="tab-content-denda" class="tab-content">

        <!-- Form Tambah Denda -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 mb-6 border border-red-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-red-700 dark:text-red-300 mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Tambah Denda Manual
            </h2>
            <form method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="hidden" name="action" value="tambah_denda">
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">Pengguna</label>
                    <select name="user_id" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none transition text-sm">
                        <option value="">-- Pilih Pengguna --</option>
                        <?php if ($users_list && $users_list->num_rows > 0): while ($ul = $users_list->fetch_assoc()): ?>
                        <option value="<?php echo $ul['id']; ?>">
                            <?php echo htmlspecialchars($ul['display_name'] . ' (' . $ul['username'] . ')'); ?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">Buku</label>
                    <select name="book_id" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none transition text-sm">
                        <option value="">-- Pilih Buku --</option>
                        <?php
                        $books_opt = $conn->query("SELECT book_id, title FROM catalog ORDER BY title ASC");
                        while ($bo = $books_opt->fetch_assoc()): ?>
                        <option value="<?php echo $bo['book_id']; ?>"><?php echo htmlspecialchars($bo['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">Jumlah Denda (Rp)</label>
                    <input type="number" name="amount" placeholder="Contoh: 5000" min="0" step="500" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none transition" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">Alasan</label>
                    <input type="text" name="reason" placeholder="Contoh: Terlambat 3 hari" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none transition" />
                </div>
                <div class="md:col-span-4 flex justify-end">
                    <button type="submit"
                        class="px-8 py-2.5 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Tambah Denda
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Denda -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-red-100 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-3">
                <h2 class="text-lg font-bold text-red-700 dark:text-red-300 flex items-center gap-2">
                    <i class="fas fa-list"></i> Daftar Denda
                </h2>
                <div class="flex gap-3 text-sm">
                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded-full font-semibold">
                        Belum lunas: <?php echo $total_fines_unpaid; ?>
                    </span>
                    <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 rounded-full font-semibold">
                        Total: Rp <?php echo number_format($total_fines_amount, 0, ',', '.'); ?>
                    </span>
                </div>
            </div>

            <!-- Filter Status -->
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex gap-2 flex-wrap">
                <button onclick="filterFines('all')" id="filter-all"
                    class="fine-filter px-4 py-1.5 rounded-lg text-xs font-semibold bg-gray-700 text-white transition">
                    Semua
                </button>
                <button onclick="filterFines('Pending')" id="filter-Pending"
                    class="fine-filter px-4 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 transition">
                    Belum Lunas
                </button>
                <button onclick="filterFines('Paid')" id="filter-Paid"
                    class="fine-filter px-4 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 transition">
                    Lunas
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-red-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Pengguna</th>
                            <th class="px-4 py-3 text-left">Buku</th>
                            <th class="px-4 py-3 text-left">Alasan</th>
                            <th class="px-4 py-3 text-left">Jumlah</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="fines-tbody">
                        <?php if ($fines && $fines->num_rows > 0): while ($f = $fines->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition fine-row" data-status="<?php echo $f['Status']; ?>">
                            <td class="px-4 py-3 text-gray-500 font-semibold"><?php echo $f['Fine_ID']; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($f['student_name']); ?></td>
                            <td class="px-4 py-3 max-w-xs truncate" title="<?php echo htmlspecialchars($f['book_title'] ?? '-'); ?>">
                                <?php echo htmlspecialchars($f['book_title'] ?? '-'); ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($f['reason']); ?></td>
                            <td class="px-4 py-3 font-bold text-red-600 dark:text-red-400">
                                Rp <?php echo number_format($f['Amount'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400"><?php echo $f['created_at']; ?></td>
                            <td class="px-4 py-3">
                                <?php if ($f['Status'] === 'Paid'): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                    <i class="fas fa-check-circle mr-1"></i>Lunas
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Belum Lunas
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <?php if ($f['Status'] === 'Pending'): ?>
                                    <form method="post" onsubmit="return confirm('Tandai denda ini sebagai lunas?');" class="inline">
                                        <input type="hidden" name="action" value="bayar_denda">
                                        <input type="hidden" name="fine_id" value="<?php echo $f['Fine_ID']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $f['User_ID']; ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-semibold transition whitespace-nowrap">
                                            <i class="fas fa-check"></i> Lunas
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" onsubmit="return confirm('Hapus data denda ini secara permanen?');" class="inline">
                                        <input type="hidden" name="action" value="hapus_denda">
                                        <input type="hidden" name="fine_id" value="<?php echo $f['Fine_ID']; ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-8 text-gray-400">Belum ada data denda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- end max-w-7xl -->

<!-- MODAL EDIT BUKU -->
<div id="modal-edit" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-lg mx-4">
        <h2 class="text-xl font-bold text-blue-700 dark:text-cyan-300 mb-5 flex items-center gap-2">
            <i class="fas fa-edit"></i> Edit Buku
        </h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="edit_buku">
            <input type="hidden" name="book_id" id="edit_book_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1 block">Judul</label>
                    <input type="text" name="title" id="edit_title" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 outline-none focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1 block">Penulis</label>
                    <input type="text" name="author" id="edit_author" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 outline-none focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1 block">Tahun</label>
                    <input type="number" name="year" id="edit_year" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 outline-none focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1 block">Penerbit</label>
                    <input type="text" name="publisher" id="edit_publisher" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 outline-none focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1 block">Jumlah Salinan</label>
                    <input type="number" name="copies_available" id="edit_copies" min="0" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 outline-none focus:ring-2 focus:ring-blue-400 transition" />
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
                <button type="button" onclick="closeEditModal()"
                    class="flex-1 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Dark mode
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

// Tab navigation
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('bg-blue-600', 'text-white');
        el.classList.add('bg-white', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-200');
    });
    document.getElementById('tab-content-' + tab).classList.add('active');
    const activeBtn = document.getElementById('tab-' + tab);
    activeBtn.classList.add('bg-blue-600', 'text-white');
    activeBtn.classList.remove('bg-white', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-200');
}

// Modal edit buku
function openEditModal(book) {
    document.getElementById('edit_book_id').value   = book.book_id;
    document.getElementById('edit_title').value     = book.title;
    document.getElementById('edit_author').value    = book.author;
    document.getElementById('edit_year').value      = book.year;
    document.getElementById('edit_publisher').value = book.publisher;
    document.getElementById('edit_copies').value    = book.copies_available;
    document.getElementById('modal-edit').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('modal-edit').classList.add('hidden');
}
document.getElementById('modal-edit').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Filter denda
function filterFines(status) {
    document.querySelectorAll('.fine-filter').forEach(b => {
        b.classList.remove('bg-gray-700', 'text-white');
        b.classList.add('bg-white', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-200', 'border', 'border-gray-300', 'dark:border-gray-600');
    });
    const ab = document.getElementById('filter-' + status);
    ab.classList.add('bg-gray-700', 'text-white');
    ab.classList.remove('bg-white', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-200', 'border', 'border-gray-300', 'dark:border-gray-600');
    document.querySelectorAll('.fine-row').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}
</script>
</body>
</html>