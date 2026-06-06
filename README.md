# 📚 PERPUS — Sistem Perpustakaan Berbasis Cloud Computing

> Sistem manajemen perpustakaan digital yang dibangun di atas arsitektur **3-Tier** dengan distribusi beban kerja ke tiga Virtual Machine terpisah, memastikan skalabilitas, keamanan, dan pemisahan tanggung jawab yang optimal.

---

## 🏗️ Arsitektur Sistem (3-Tier Architecture)
[ Browser Pengguna / Internet ]
                    │
                    ▼
     ┌──────────────────────────────┐
     │     VM1 — Web Server         │  ◀── Presentation Layer
     │     Apache2 + PHP 8.x        │
     │     Port: 80 / 443 (HTTPS)   │
     │     IP: 192.168.1.11         │
     └──────────────┬───────────────┘
                    │ Internal Network
                    ▼
     ┌──────────────────────────────┐
     │     VM2 — App Server         │  ◀── Business Logic Layer
     │     PHP-FPM + Composer       │
     │     Port: 9000               │
     │     IP: 192.168.1.12         │
     └──────────────┬───────────────┘
                    │ Internal Network
                    ▼
     ┌──────────────────────────────┐
     │     VM3 — Database Server    │  ◀── Data Layer
     │     MySQL 8.x / MariaDB      │
     │     Port: 3306 (internal)    │
     │     IP: 192.168.1.13         │
     └──────────────────────────────┘
     ---

## 🖥️ VM1 — Web Server *(Presentation Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** Apache2 + PHP 8.x | **IP:** `192.168.1.11` | **Port:** `80`, `443`

VM ini melayani semua request dari browser pengguna (mahasiswa & admin). Semua file tampilan (UI) berada di sini. Dapat diakses langsung dari internet dan mengirim request ke VM2 untuk pemrosesan bisnis.

### File yang Dihosting

| File | Fungsi |
|------|--------|
| `index.php` | Halaman utama / landing page |
| `login.php` | Halaman login pengguna |
| `logout.php` | Proses logout & destroy session |
| `signup.php` | Halaman registrasi mahasiswa |
| `student_dashboard.php` | Dashboard utama mahasiswa |
| `view_books.php` | Halaman lihat katalog buku |
| `recommended_books.php` | Halaman rekomendasi buku |
| `issue_requests.php` | Halaman permintaan peminjaman buku |
| `renewals.php` | Halaman perpanjangan peminjaman |
| `penalty.php` | Halaman informasi denda |
| `java.js` | Script JavaScript untuk interaktivitas UI |
| `admin/admin_dashboard.php` | Dashboard admin perpustakaan |

### Komunikasi
- ✅ **Menerima** request dari: Browser pengguna (Internet)
- ➡️ **Mengirim** request ke: VM2 (App Server) untuk proses bisnis

---

## ⚙️ VM2 — Application Server *(Business Logic Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** PHP 8.x (PHP-FPM), Composer | **IP:** `192.168.1.12` | **Port:** `9000`

VM ini mengelola semua **logika bisnis**, pemrosesan data, dan koneksi ke database. Tidak dapat diakses langsung dari internet — hanya bisa dipanggil oleh VM1.

### File yang Dihosting

| File | Fungsi |
|------|--------|
| `dbconnect.php` | Konfigurasi & koneksi ke VM3 (Database Server) |
| `ajax_issue_request.php` | Handler AJAX untuk proses permintaan peminjaman buku secara real-time |

### Komunikasi
- ✅ **Menerima** request dari: VM1 (Web Server)
- ➡️ **Mengirim** query ke: VM3 (Database Server)
- 🚫 **Tidak** dapat diakses langsung dari internet (internal network only)

---

## 🗄️ VM3 — Database Server *(Data Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** MySQL 8.x / MariaDB | **IP:** `192.168.1.13` | **Port:** `3306`

VM ini menyimpan **seluruh data aplikasi** perpustakaan. Hanya dapat diakses oleh VM2 melalui jaringan internal — tidak pernah terekspos ke internet maupun VM1 secara langsung.

### File yang Dihosting

| File | Fungsi |
|------|--------|
| `database/librarydb.sql` | Schema & data awal database perpustakaan |
| `database/.gitkeep` | Menjaga folder database tetap ter-track di Git |

### Struktur Database: `librarydb`

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Data mahasiswa & admin |
| `books` | Katalog buku |
| `issue_requests` | Permintaan peminjaman |
| `renewals` | Data perpanjangan |
| `penalties` | Data denda keterlambatan |

### Komunikasi
- ✅ **Menerima** koneksi dari: VM2 (App Server)
- 🚫 **Tidak** dapat diakses dari VM1 maupun Internet

---

## 📦 Distribusi File per VM

| File | VM | Layer |
|------|----|-------|
| `index.php` | VM1 | Presentation |
| `login.php` | VM1 | Presentation |
| `logout.php` | VM1 | Presentation |
| `signup.php` | VM1 | Presentation |
| `student_dashboard.php` | VM1 | Presentation |
| `view_books.php` | VM1 | Presentation |
| `recommended_books.php` | VM1 | Presentation |
| `issue_requests.php` | VM1 | Presentation |
| `renewals.php` | VM1 | Presentation |
| `penalty.php` | VM1 | Presentation |
| `java.js` | VM1 | Presentation |
| `admin/admin_dashboard.php` | VM1 | Presentation |
| `dbconnect.php` | VM2 | Business Logic |
| `ajax_issue_request.php` | VM2 | Business Logic |
| `database/librarydb.sql` | VM3 | Data |
| `database/.gitkeep` | VM3 | Data |

---

## 🔄 Alur Request
1. 🌐  User buka browser          →  hit VM1 (Web Server / Apache2)
2. ⚙️  VM1 butuh data/proses      →  panggil VM2 (App Server / PHP-FPM)
3. 🗄️  VM2 butuh data             →  query ke VM3 (Database / MySQL)
4. ↩️  Response mengalir balik    →  VM3 → VM2 → VM1 → Browser
---

## 🔐 Keamanan Jaringan

- **VM1** adalah satu-satunya titik yang terekspos ke internet publik.
- **VM2** hanya dapat diakses melalui jaringan internal dari VM1.
- **VM3** hanya dapat diakses melalui jaringan internal dari VM2 — tidak pernah langsung dari VM1 atau internet.
- Port MySQL (`3306`) **tidak** dibuka ke interface publik.

---

## 🚀 Teknologi yang Digunakan

| Komponen | Teknologi |
|----------|-----------|
| Web Server | Apache2 / Nginx |
| Backend Language | PHP 8.x |
| Process Manager | PHP-FPM |
| Dependency Manager | Composer |
| Database | MySQL 8.x / MariaDB |
| Frontend | PHP + JavaScript |
| OS (semua VM) | Ubuntu Server 22.04 LTS |
| Arsitektur | 3-Tier Cloud (VM-based) |
