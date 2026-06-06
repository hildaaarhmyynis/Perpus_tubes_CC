<?php
session_start();
header('Content-Type: application/json');
include 'dbconnect.php';

// Periksa apakah pengguna sudah masuk dan perannya adalah 'user'
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'Anda harus masuk sebagai siswa untuk mengajukan buku.']);
    exit();
}

$username = $_SESSION['username'];
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if (!$book_id) {
    echo json_encode(['success' => false, 'error' => 'ID buku tidak valid.']);
    exit();
}

// Ambil student_id
$stmt = $conn->prepare("SELECT Roll_No FROM Students WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Data siswa tidak ditemukan.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Periksa apakah buku ada dan salinannya tersedia
$stmt = $conn->prepare("SELECT copies_available FROM Catalog WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$stmt->bind_result($copies_available);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Buku tidak ditemukan.']);
    $stmt->close();
    exit();
}
$stmt->close();

if ($copies_available < 1) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada salinan yang tersedia untuk buku ini.']);
    exit();
}

// Periksa apakah sudah ada pengajuan yang tertunda atau disetujui
$stmt = $conn->prepare("SELECT * FROM IssueRequest WHERE student_id = ? AND book_id = ? AND status IN ('pending', 'approved')");
$stmt->bind_param("ii", $student_id, $book_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Anda sudah memiliki pengajuan yang tertunda atau disetujui untuk buku ini.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Masukkan pengajuan pinjaman baru
$stmt = $conn->prepare("INSERT INTO IssueRequest (student_id, book_id, request_date, status) VALUES (?, ?, NOW(), 'pending')");
$stmt->bind_param("ii", $student_id, $book_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Kesalahan saat mengirim pengajuan.']);
}
$stmt->close();