# VM1 — Web Server (Frontend / Presentation Layer)

## Spesifikasi
- **OS:** Ubuntu Server 22.04 LTS
- **Software:** Apache2 / Nginx + PHP 8.x
- **IP:** 192.168.1.11 (contoh)
- **Port:** 80 (HTTP), 443 (HTTPS)

## Peran dalam Arsitektur
VM ini berfungsi sebagai **Web Server** yang melayani semua request dari browser pengguna (mahasiswa & admin). Semua file tampilan (UI) ada di sini.

## File yang Dihosting
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

## Komunikasi
- Menerima request dari: **Browser pengguna (Internet)**
- Mengirim request ke: **VM2 (App Server)** untuk proses bisnis
