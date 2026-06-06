# VM2 — Application Server (Backend / Business Logic Layer)

## Spesifikasi
- **OS:** Ubuntu Server 22.04 LTS
- **Software:** PHP 8.x (PHP-FPM), Composer
- **IP:** 192.168.1.12 (contoh)
- **Port:** 9000 (PHP-FPM internal)

## Peran dalam Arsitektur
VM ini berfungsi sebagai **Application Server** yang mengelola semua logika bisnis, pemrosesan data, dan koneksi ke database. Tidak dapat diakses langsung dari internet.

## File yang Dihosting
| File | Fungsi |
|------|--------|
| `dbconnect.php` | Konfigurasi & koneksi ke VM3 (Database Server) |
| `ajax_issue_request.php` | Handler AJAX untuk proses permintaan peminjaman buku secara real-time |

## Komunikasi
- Menerima request dari: **VM1 (Web Server)**
- Mengirim query ke: **VM3 (Database Server)**
- **Tidak** dapat diakses langsung dari internet (internal network only)
