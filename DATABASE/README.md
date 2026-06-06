# VM3 — Database Server (Data Layer)

## Spesifikasi
- **OS:** Ubuntu Server 22.04 LTS
- **Software:** MySQL 8.x / MariaDB
- **IP:** 192.168.1.13 (contoh)
- **Port:** 3306 (MySQL, internal only)

## Peran dalam Arsitektur
VM ini berfungsi sebagai **Database Server** yang menyimpan seluruh data aplikasi perpustakaan. Hanya dapat diakses oleh VM2 (App Server) melalui jaringan internal.

## File yang Dihosting
| File | Fungsi |
|------|--------|
| `database/librarydb.sql` | Schema & data awal database perpustakaan |
| `database/.gitkeep` | Menjaga folder database tetap ter-track di Git |

## Database: `librarydb`
Berisi tabel-tabel utama:
- `users` — Data mahasiswa & admin
- `books` — Katalog buku
- `issue_requests` — Permintaan peminjaman
- `renewals` — Data perpanjangan
- `penalties` — Data denda keterlambatan

## Komunikasi
- Hanya menerima koneksi dari: **VM2 (App Server)**
- **Tidak** dapat diakses dari VM1 maupun Internet
