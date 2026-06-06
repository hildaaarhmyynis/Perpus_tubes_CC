# PERPUS — Sistem Perpustakaan Berbasis Cloud Computing

## Arsitektur Sistem (3-Tier Architecture)

```
[ Browser Pengguna / Internet ]
           │
           ▼
┌─────────────────────────┐
│  VM1 — Web Server       │  ← Presentation Layer
│  Apache2 + PHP          │
│  Port: 80/443           │
│  IP: 192.168.1.11       │
└───────────┬─────────────┘
            │ Internal Network
            ▼
┌─────────────────────────┐
│  VM2 — App Server       │  ← Business Logic Layer
│  PHP-FPM                │
│  Port: 9000             │
│  IP: 192.168.1.12       │
└───────────┬─────────────┘
            │ Internal Network
            ▼
┌─────────────────────────┐
│  VM3 — Database Server  │  ← Data Layer
│  MySQL 8.x              │
│  Port: 3306             │
│  IP: 192.168.1.13       │
└─────────────────────────┘
```

## Distribusi File per VM

| File | VM |
|------|----|
| index.php | VM1 |
| login.php | VM1 |
| logout.php | VM1 |
| signup.php | VM1 |
| student_dashboard.php | VM1 |
| view_books.php | VM1 |
| recommended_books.php | VM1 |
| issue_requests.php | VM1 |
| renewals.php | VM1 |
| penalty.php | VM1 |
| java.js | VM1 |
| admin/admin_dashboard.php | VM1 |
| dbconnect.php | VM2 |
| ajax_issue_request.php | VM2 |
| database/librarydb.sql | VM3 |
| database/.gitkeep | VM3 |

## Alur Request
1. User buka browser → hit **VM1** (Web Server)
2. VM1 butuh data/proses → panggil **VM2** (App Server)
3. VM2 butuh data → query ke **VM3** (Database Server)
4. Response mengalir balik: VM3 → VM2 → VM1 → Browser
