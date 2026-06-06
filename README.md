# PERPUS — Sistem Perpustakaan Berbasis Cloud Computing

> Sistem manajemen perpustakaan digital yang dibangun di atas arsitektur **3-Tier** dengan distribusi beban kerja ke tiga Virtual Machine terpisah, memastikan skalabilitas, keamanan, dan pemisahan tanggung jawab yang optimal.

---
## I. Tim Pengembang

<div align="center">

| Nama | NIM |
|------|-----|
| Hilda Rahmayani S | 101032400010 |
| Farah Fadhilah | 101032430007 |
| Saraya Abharina | 101032400191 |

> Tugas Besar Komputasi Awan

</div>

---

## II. Arsitektur Sistem

```
         [ Browser / Internet ]
                   │
                   ▼
┌──────────────────────────────────────┐
│  VM1 — Frontend Server               │
│  Apache2 + PHP  │  192.168.100.24    │
└──────────────────┬───────────────────┘
                   │ SSH / Internal Network
                   ▼
┌──────────────────────────────────────┐
│  VM2 — Backend Server                │
│  PHP-FPM        │  192.168.100.92    │
└──────────────────┬───────────────────┘
                   │ Internal Network
                   ▼
┌──────────────────────────────────────┐
│  VM3 — Database Server               │
│  MySQL 8        │  192.168.100.29    │
└──────────────────────────────────────┘
```
## VM1 — Web Server *(Presentation Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** Apache2 + PHP 8.x | **IP:** `192.168.100.24` | **Port:** `80`, `443`

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
-  **Menerima** request dari: Browser pengguna (Internet)
-  **Mengirim** request ke: VM2 (App Server) untuk proses bisnis

---

## VM2 — Application Server *(Business Logic Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** PHP 8.x (PHP-FPM), Composer | **IP:** `192.168.100.92` | **Port:** `9000`

VM ini mengelola semua **logika bisnis**, pemrosesan data, dan koneksi ke database. Tidak dapat diakses langsung dari internet — hanya bisa dipanggil oleh VM1.

### File yang Dihosting

| File | Fungsi |
|------|--------|
| `dbconnect.php` | Konfigurasi & koneksi ke VM3 (Database Server) |
| `ajax_issue_request.php` | Handler AJAX untuk proses permintaan peminjaman buku secara real-time |

### Komunikasi
-  **Menerima** request dari: VM1 (Web Server)
-  **Mengirim** query ke: VM3 (Database Server)
-  **Tidak** dapat diakses langsung dari internet (internal network only)

---

## VM3 — Database Server *(Data Layer)*

**OS:** Ubuntu Server 22.04 LTS | **Software:** MySQL 8.x / MariaDB | **IP:** `192.168.100.29` | **Port:** `3306`

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
-  **Menerima** koneksi dari: VM2 (App Server)
-  **Tidak** dapat diakses dari VM1 maupun Internet

---

## Distribusi File per VM

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

## III. Prasyarat

Pastikan sudah tersedia sebelum mulai:

| Kebutuhan | Keterangan |
|-----------|------------|
| VirtualBox | Terinstall di Mac/Windows — [download di sini](https://www.virtualbox.org) |
| ISO Ubuntu 24.04 Desktop | [Download di ubuntu.com](https://ubuntu.com/download/desktop) |
| Terminal | Mac: Terminal / iTerm — Windows: PowerShell |
| RAM minimal 6 GB | Disarankan 8 GB agar 3 VM berjalan lancar |

##  Alur Request
1.   User buka browser          →  hit VM1 (Web Server / Apache2)
2.   VM1 butuh data/proses      →  panggil VM2 (App Server / PHP-FPM)
3.   VM2 butuh data             →  query ke VM3 (Database / MySQL)
4.   Response mengalir balik    →  VM3 → VM2 → VM1 → Browser
---
>  **Urutan setup wajib diikuti:** VM3 (Database) → VM2 (Backend) → VM1 (Frontend)

---
##  Langkah 1 — Persiapan VirtualBox

### 1.1 Cek nama adapter WiFi
```bash
VBoxManage list bridgedifs | grep "^Name:"
```
> Catat nama adapter WiFi kamu, contoh: `en0: Wi-Fi`

### 1.2 Buat folder kerja
```bash
mkdir -p ~/vbox-perpus
cd ~/vbox-perpus
```

---

##  Langkah 2 — Buat 3 VM via Terminal

> Ganti `en0: Wi-Fi` dengan nama adapter WiFi kamu, dan sesuaikan path ISO.

```bash
ISO="/Users/macbookpro/Documents/ubuntu-24.04.3-desktop-amd64.iso"

# ======== VM1 - FRONTEND ========
VBoxManage createvm --name "PERPUS-VM1-Frontend" --ostype Ubuntu_64 --register
VBoxManage modifyvm "PERPUS-VM1-Frontend" \
  --memory 2048 --cpus 2 \
  --nic1 bridged --bridgeadapter1 "en0: Wi-Fi" \
  --graphicscontroller vboxsvga --vram 128
VBoxManage createhd --filename ~/vbox-perpus/vm1-frontend.vdi --size 20000
VBoxManage storagectl "PERPUS-VM1-Frontend" --name "SATA" --add sata --controller IntelAhci
VBoxManage storageattach "PERPUS-VM1-Frontend" --storagectl "SATA" --port 0 --device 0 --type hdd --medium ~/vbox-perpus/vm1-frontend.vdi
VBoxManage storageattach "PERPUS-VM1-Frontend" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium "$ISO"

# ======== VM2 - BACKEND ========
VBoxManage createvm --name "PERPUS-VM2-Backend" --ostype Ubuntu_64 --register
VBoxManage modifyvm "PERPUS-VM2-Backend" \
  --memory 2048 --cpus 2 \
  --nic1 bridged --bridgeadapter1 "en0: Wi-Fi" \
  --graphicscontroller vboxsvga --vram 128
VBoxManage createhd --filename ~/vbox-perpus/vm2-backend.vdi --size 20000
VBoxManage storagectl "PERPUS-VM2-Backend" --name "SATA" --add sata --controller IntelAhci
VBoxManage storageattach "PERPUS-VM2-Backend" --storagectl "SATA" --port 0 --device 0 --type hdd --medium ~/vbox-perpus/vm2-backend.vdi
VBoxManage storageattach "PERPUS-VM2-Backend" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium "$ISO"

# ======== VM3 - DATABASE ========
VBoxManage createvm --name "PERPUS-VM3-Database" --ostype Ubuntu_64 --register
VBoxManage modifyvm "PERPUS-VM3-Database" \
  --memory 2048 --cpus 2 \
  --nic1 bridged --bridgeadapter1 "en0: Wi-Fi" \
  --graphicscontroller vboxsvga --vram 128
VBoxManage createhd --filename ~/vbox-perpus/vm3-database.vdi --size 20000
VBoxManage storagectl "PERPUS-VM3-Database" --name "SATA" --add sata --controller IntelAhci
VBoxManage storageattach "PERPUS-VM3-Database" --storagectl "SATA" --port 0 --device 0 --type hdd --medium ~/vbox-perpus/vm3-database.vdi
VBoxManage storageattach "PERPUS-VM3-Database" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium "$ISO"
```

Verifikasi VM berhasil dibuat:
```bash
VBoxManage list vms
```

---

##  Langkah 3 — Install Ubuntu di Tiap VM

Jalankan tiap VM satu per satu:
```bash
VBoxManage startvm "PERPUS-VM1-Frontend"
```

Ikuti proses instalasi Ubuntu Desktop:

| Pengaturan | Nilai |
|------------|-------|
| Language | English |
| Installation type | Minimal Installation |
| Disk | Erase disk and install Ubuntu |
| Username | `ubuntu` |
| Computer name | `vm1-frontend` / `vm2-backend` / `vm3-database` |

>  Catat password yang kamu buat — akan dipakai untuk SSH nanti.

Setelah install selesai dan restart, ulangi untuk VM2 dan VM3.

---

##  Langkah 4 — Setup SSH di Tiap VM

Lakukan di **jendela VirtualBox** masing-masing VM:

```bash
# Install SSH server
sudo apt update
sudo apt install -y openssh-server

# Aktifkan SFTP
echo "Subsystem sftp /usr/lib/openssh/sftp-server" | sudo tee -a /etc/ssh/sshd_config
sudo systemctl enable ssh
sudo systemctl restart ssh

# Cek IP VM (catat hasilnya!)
ip a
```

>  Catat IP masing-masing VM dari output `ip a` — akan dipakai di langkah berikutnya.

---

##  Langkah 5 — Jalankan VM Headless (Tanpa Jendela)

Setelah install selesai, VM bisa dijalankan di background tanpa membuka jendela:

```bash
# Lepas ISO dari semua VM
VBoxManage storageattach "PERPUS-VM1-Frontend" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium emptydrive
VBoxManage storageattach "PERPUS-VM2-Backend" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium emptydrive
VBoxManage storageattach "PERPUS-VM3-Database" --storagectl "SATA" --port 1 --device 0 --type dvddrive --medium emptydrive

# Jalankan headless
VBoxManage startvm "PERPUS-VM1-Frontend" --type headless
VBoxManage startvm "PERPUS-VM2-Backend" --type headless
VBoxManage startvm "PERPUS-VM3-Database" --type headless
```

---

##  Langkah 6 — Setup VM1 (Frontend)

```bash
# SSH ke VM1
ssh ubuntu@<IP-VM1>

# Install Apache + PHP
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-mbstring

# Set permission
sudo chown -R ubuntu:ubuntu /var/www/html
sudo rm -f /var/www/html/index.html

# Enable Apache
sudo systemctl enable apache2
sudo systemctl restart apache2
exit
```

###  Upload file ke VM1

Jalankan dari terminal **lokal** (bukan dari dalam VM):
```bash
scp -r ./FRONTEND/* ubuntu@<IP-VM1>:/var/www/html/
scp ./BACKEND/dbconnect.php ubuntu@<IP-VM1>:/var/www/html/
scp ./BACKEND/ajax_issue_request.php ubuntu@<IP-VM1>:/var/www/html/
scp ./BACKEND/logout.php ubuntu@<IP-VM1>:/var/www/html/
```

### Konfigurasi `dbconnect.php` di VM1

```bash
ssh ubuntu@<IP-VM1>
nano /var/www/html/dbconnect.php
```

Isi file dengan:
```php
<?php
$host = "<IP-VM3>";
$user = "perpususer";
$pass = "Password123!";
$db   = "librarydb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
```

### Fix nama tabel (case sensitive)

```bash
sudo sed -i 's/FROM Students/FROM students/g; s/INTO Students/INTO students/g; s/UPDATE Students/UPDATE students/g; s/JOIN Students/JOIN students/g; s/FROM Catalog/FROM catalog/g; s/JOIN Catalog/JOIN catalog/g; s/FROM IssueRequest/FROM issuerequest/g; s/JOIN IssueRequest/JOIN issuerequest/g; s/FROM IssuedBooks/FROM issuedbooks/g; s/JOIN IssuedBooks/JOIN issuedbooks/g; s/FROM Notifications/FROM notifications/g; s/JOIN Notifications/JOIN notifications/g; s/FROM Penalty/FROM penalty/g; s/JOIN Penalty/JOIN penalty/g; s/FROM Renewal/FROM renewal/g; s/JOIN Renewal/JOIN renewal/g; s/IssueRequest/issuerequest/g; s/IssuedBooks/issuedbooks/g; s/Penalty/penalty/g' /var/www/html/*.php /var/www/html/admin/*.php
exit
```

---

##  Langkah 7 — Setup VM2 (Backend)

```bash
# SSH ke VM2
ssh ubuntu@<IP-VM2>

# Install PHP
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql

# Set permission
sudo chown -R ubuntu:ubuntu /var/www/html
sudo systemctl enable apache2
sudo systemctl restart apache2
exit
```

### Upload file ke VM2

```bash
scp ./BACKEND/dbconnect.php ubuntu@<IP-VM2>:/var/www/html/
scp ./BACKEND/ajax_issue_request.php ubuntu@<IP-VM2>:/var/www/html/
scp ./BACKEND/logout.php ubuntu@<IP-VM2>:/var/www/html/
```

---

##  Langkah 8 — Setup VM3 (Database)

```bash
# SSH ke VM3
ssh ubuntu@<IP-VM3>

# Install MySQL
sudo apt update
sudo apt install -y mysql-server
sudo mysql_secure_installation
# Jawab: Y Y Y N Y Y
```

### Buat database dan user

```bash
sudo mysql
```

```sql
CREATE DATABASE librarydb;
CREATE USER 'perpususer'@'%' IDENTIFIED BY 'Password123!';
GRANT ALL PRIVILEGES ON librarydb.* TO 'perpususer'@'%';
FLUSH PRIVILEGES;
EXIT;
```

### Izinkan koneksi dari luar localhost

```bash
sudo sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql
sudo systemctl enable mysql
exit
```

### Import database

```bash
# Upload file SQL dari lokal ke VM3
scp ./DATABASE/librarydb.sql ubuntu@<IP-VM3>:/home/ubuntu/

# Import ke MySQL
ssh ubuntu@<IP-VM3> "sudo mysql librarydb < /home/ubuntu/librarydb.sql"
```

---

##  Langkah 9 — Test Koneksi

### Test koneksi VM2 → VM3
```bash
ssh ubuntu@<IP-VM2>
mysql -h <IP-VM3> -u perpususer -p
# Masukkan password: Password123!
# Kalau berhasil masuk ke MySQL = koneksi OK
```

### Test aplikasi di browser
http://<IP-VM1>
Jika muncul halaman login PERPUS = instalasi berhasil!

---

##  Langkah 10 — Perintah Manajemen VM

```bash
# Lihat semua VM
VBoxManage list vms

# Lihat VM yang sedang berjalan
VBoxManage list runningvms

# Start VM headless
VBoxManage startvm "PERPUS-VM1-Frontend" --type headless

# Matikan VM
VBoxManage controlvm "PERPUS-VM1-Frontend" poweroff

# Cek status VM
VBoxManage showvminfo "PERPUS-VM1-Frontend" | grep "State"
```

---
## IV. Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| SSH Host Key Changed | IP VM berubah | `ssh-keygen -R <IP-VM>` lalu SSH ulang |
| IP VM berubah setelah restart | DHCP | Cek IP baru via `ip a` di jendela VirtualBox |
| SFTP / SCP gagal | SFTP belum aktif | Tambahkan `Subsystem sftp ...` ke sshd_config, restart ssh |
| Permission denied saat upload | Folder milik root | `sudo chown -R ubuntu:ubuntu /var/www/html` |
| Halaman putih / error tabel | Nama tabel case-sensitive | Jalankan perintah `sed` di Langkah 6 |
| Koneksi database gagal | IP/password salah | Periksa kembali isi `dbconnect.php` |
| MySQL tidak bisa diakses dari VM2 | bind-address belum diubah | Pastikan `bind-address = 0.0.0.0` di mysqld.cnf |

Cek log error Apache jika ada masalah di frontend:
```bash
ssh ubuntu@<IP-VM1>
cat /var/log/apache2/error.log | tail -20
```

---

##  V.  Keamanan Jaringan

- **VM1** adalah satu-satunya titik yang terekspos ke internet publik.
- **VM2** hanya dapat diakses melalui jaringan internal dari VM1.
- **VM3** hanya dapat diakses melalui jaringan internal dari VM2 — tidak pernah langsung dari VM1 atau internet.
- Port MySQL (`3306`) **tidak** dibuka ke interface publik.

---

## VI. Teknologi yang Digunakan

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

---

## VII. Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin |
| Student | VINAYAK | *(cek database)* |

---

*📝 Dibuat untuk Tugas Besar Komputasi Awan*
