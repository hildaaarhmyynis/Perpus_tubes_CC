<?php
session_start();
include 'dbconnect.php';

if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Memverifikasi kata sandi (diasumsikan disimpan menggunakan password_hash)
        if (password_verify($password, $row['password'])) {
            // Mengatur variabel sesi
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // 'admin' atau 'user'

            // Mengarahkan ke dasbor yang sesuai
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $error = "Nama pengguna atau kata sandi salah.";
        }
    } else {
        $error = "Nama pengguna atau kata sandi salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Masuk - Sistem Manajemen Perpustakaan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Masuk</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Nama Pengguna:</label>
            <input type="text" id="username" name="username" required autofocus>

            <label for="password">Kata Sandi:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Masuk</button>
        </form>
    </div>
    <script src="java.js"></script>
</body>
</html>