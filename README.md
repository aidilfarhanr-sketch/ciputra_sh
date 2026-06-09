<p align="center">
  <img src="assets/Ciputra.png" alt="Logo Ciputra" width="140">
</p>

<h1 align="center">CIPUTRA SH-1</h1>

<p align="center">
  <b>Sistem Pengaduan Online, Temuan QC, Jawaban Kontraktor, Monitoring Proyek, Cetak PDF, dan Excel untuk unit rumah Ciputra.</b>
</p>

<p align="center">
  <a href="https://github.com/aidilfarhanr-sketch/ciputra_sh">Repository GitHub</a> ·
  <a href="https://drive.google.com/drive/folders/1kMU7D-fIhNIVVooBWRyQ8yDwMCIqe61A?usp=drive_link">Video Demo</a>
</p>

---

## Tentang Project

**CIPUTRA SH-1** adalah sistem berbasis web yang dibuat untuk membantu proses pencatatan, validasi, tindak lanjut, dan dokumentasi pengaduan kerusakan rumah atau temuan lapangan pada proyek Ciputra.

Sistem ini memisahkan alur kerja antara **pemilik rumah/pelanggan**, **Admin SH-1**, **QC**, dan **Kontraktor** agar setiap laporan dapat diproses dengan lebih rapi, terarah, aman, dan mudah dipantau sampai pekerjaan selesai.

Project ini cocok digunakan untuk kebutuhan:

- Pengajuan keluhan rumah oleh pemilik rumah tanpa login.
- Pencatatan temuan QC berdasarkan proyek.
- Validasi laporan oleh Admin SH-1.
- Penerusan pekerjaan ke QC dan Kontraktor.
- Upload foto temuan dan foto hasil perbaikan.
- Monitoring status pekerjaan.
- Cetak laporan PDF melalui browser.
- Export dan import data Excel.
- Audit log aktivitas sistem.
- Manajemen proyek dan akun internal.

---

## Link Penting

| Keterangan | Link |
|---|---|
| Repository GitHub | https://github.com/aidilfarhanr-sketch/ciputra_sh |
| Video Demo | https://drive.google.com/drive/folders/1kMU7D-fIhNIVVooBWRyQ8yDwMCIqe61A?usp=drive_link |

---

## Tujuan Sistem

Sistem ini dibuat agar proses pengaduan dan penanganan temuan rumah tidak lagi tercecer melalui catatan manual, chat, atau file terpisah.

Dengan sistem ini, setiap laporan dapat:

1. Masuk melalui form yang jelas.
2. Dikelompokkan berdasarkan proyek.
3. Diproses sesuai role pengguna.
4. Dilampirkan foto sebagai bukti.
5. Dipantau status dan deadline-nya.
6. Dicetak menjadi dokumen laporan.
7. Diekspor ke Excel untuk arsip atau revisi data.

---

## Teknologi yang Digunakan

| Bagian | Teknologi |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Server Lokal | XAMPP |
| Library Grafik | Chart.js |
| Excel | SheetJS / XLSX |
| Keamanan | Session, CSRF Token, Rate Limit, Role Access, Upload Validation |
| Dokumentasi/CI | GitHub Actions untuk cek syntax PHP |

---

## Struktur Folder Project

```text
ciputra_sh/
├── .github/
│   └── workflows/
│       └── php-ci.yml
├── api/
│   ├── auth.php
│   ├── complaints.php
│   ├── config.php
│   ├── config.example.php
│   ├── config.local.php
│   ├── dashboard.php
│   ├── logs.php
│   ├── projects.php
│   └── users.php
├── assets/
│   ├── Ciputra.png
│   ├── bapak.png
│   ├── reference_pdfjawaban.jpeg
│   └── reference_pdftemuan.jpeg
├── backups/
├── cache/
├── logs/
│   └── app.log
├── uploads/
│   ├── jawaban/
│   └── temuan/
├── backup_database.bat
├── ciputra_sh_revisi_FINAL.sql
├── database_install_full.sql
├── excel.php
├── index.php
├── migration_upgrade_to_85.sql
├── print.php
└── update_ciputra_sh_v_next.sql
```

---

## Role Pengguna

### 1. Pemilik Rumah / Pelanggan

Pelanggan tidak perlu login. Pelanggan hanya mengisi form pengaduan publik.

Fitur pelanggan:

- Mengisi form pengaduan rumah.
- Memilih proyek.
- Mengisi blok/unit.
- Mengisi nama, nomor HP, tanggal, dan keterangan kerusakan.
- Upload foto kerusakan.
- Mengirim laporan ke Admin SH-1.

Pelanggan tidak memiliki akun dashboard dan tidak dapat mengakses data internal.

---

### 2. Admin SH-1

Admin SH-1 adalah pengelola utama sistem.

Fitur Admin SH-1:

- Login ke dashboard internal.
- Melihat seluruh data proyek.
- Melihat seluruh pengaduan pelanggan.
- Melihat seluruh temuan QC.
- Validasi atau tolak pengaduan pelanggan.
- Meneruskan pengaduan pelanggan ke QC.
- Mengelola master proyek.
- Membuat akun QC dan Kontraktor.
- Reset password akun internal.
- Menonaktifkan akun internal.
- Melihat dashboard statistik.
- Melihat audit logs.
- Ban atau unban IP/username mencurigakan.
- Cetak laporan temuan dan jawaban.
- Export dan import data Excel.

---

### 3. QC

QC adalah user internal yang menangani temuan lapangan dan tindak lanjut proyek.

Fitur QC:

- Login ke dashboard QC.
- Hanya melihat data sesuai proyeknya.
- Membuat temuan QC.
- Menerima pengaduan pelanggan yang diteruskan Admin.
- Meneruskan temuan atau pengaduan ke Kontraktor.
- Melihat jawaban dari Kontraktor.
- Memeriksa status perbaikan.
- Memantau deadline pekerjaan.
- Cetak dokumen temuan dan jawaban sesuai akses.

QC tidak dapat membuat akun internal dan tidak dapat mengakses semua proyek selain proyek yang diberikan.

---

### 4. Kontraktor

Kontraktor adalah user internal yang bertugas memberikan jawaban dan hasil perbaikan.

Fitur Kontraktor:

- Login ke dashboard Kontraktor.
- Hanya melihat tugas yang diberikan ke dirinya.
- Mengisi penyebab kerusakan.
- Mengisi antisipasi.
- Mengisi keterangan perbaikan.
- Menentukan tanggal dan jam rencana perbaikan.
- Upload foto hasil perbaikan.
- Mengubah status pekerjaan menjadi proses atau selesai.

Kontraktor tidak dapat membuat temuan, memvalidasi laporan, atau membuat akun internal.

---

## Alur Sistem

### A. Alur Pengaduan Pelanggan

```text
Pelanggan mengisi form publik
        ↓
Data masuk ke sistem sebagai Keluhan Pelanggan
        ↓
Admin SH-1 memvalidasi laporan
        ↓
Jika ditolak, laporan diberi alasan penolakan
        ↓
Jika diterima, laporan diteruskan ke QC
        ↓
QC meneruskan pekerjaan ke Kontraktor
        ↓
Kontraktor mengisi jawaban dan upload foto perbaikan
        ↓
QC memeriksa hasil pekerjaan
        ↓
Admin memantau dan mengarsipkan laporan
        ↓
Status akhir: Selesai
```

---

### B. Alur Temuan QC

```text
QC login ke dashboard
        ↓
QC membuat temuan baru berdasarkan proyek
        ↓
Sistem membuat nomor KQI otomatis
        ↓
QC meneruskan temuan ke Kontraktor
        ↓
Deadline pekerjaan dibuat
        ↓
Kontraktor mengisi jawaban dan foto hasil perbaikan
        ↓
QC memeriksa hasil pekerjaan
        ↓
Status akhir: Selesai
```

---

## Fitur Utama

### 1. Landing Page Publik

Halaman awal dibuat seperti landing page modern yang menjelaskan fungsi sistem. Dari halaman ini, pemilik rumah dapat diarahkan ke form pengaduan tanpa login.

### 2. Form Pengaduan Pemilik Rumah

Form publik digunakan oleh pemilik rumah untuk mengirim laporan kerusakan rumah.

Data yang dapat diisi:

- Proyek.
- Blok/unit.
- Tanggal laporan.
- Nama pelapor.
- Nomor HP.
- Email jika diperlukan.
- Kategori/keterangan kerusakan.
- Upload foto temuan.

### 3. Dashboard Internal

Dashboard menampilkan ringkasan data temuan dan pengaduan, termasuk:

- Total temuan.
- Temuan QC.
- Keluhan pelanggan.
- Status belum dijawab.
- Status proses.
- Status selesai.
- Grafik jenis temuan.
- Grafik status jawaban.
- Grafik temuan per proyek.
- Daftar temuan yang perlu perhatian.

### 4. Master Proyek

Admin dapat mengelola daftar proyek, seperti:

- Menambah proyek.
- Mengedit proyek.
- Menonaktifkan proyek.
- Menghapus proyek yang belum dipakai data.

Proyek aktif akan muncul otomatis di form pengajuan user, form temuan QC, dan pengaturan akun internal.

### 5. Kelola Akun Internal

Admin SH-1 dapat membuat akun untuk QC dan Kontraktor.

Data akun internal meliputi:

- Nama lengkap.
- Username.
- Email.
- Nomor HP.
- Role.
- Proyek.
- Status akun.

Sistem tidak menyediakan register publik. Akun QC dan Kontraktor hanya dibuat oleh Admin SH-1.

### 6. Temuan QC

QC dapat membuat temuan berdasarkan hasil pengecekan lapangan.

Temuan QC menggunakan nomor KQI otomatis berdasarkan kode proyek dan tanggal.

Contoh format:

```text
KQI-CGS-20260524-001
```

### 7. Keluhan Pelanggan

Keluhan pelanggan berasal dari form publik. Keluhan ini harus divalidasi terlebih dahulu oleh Admin SH-1 sebelum diteruskan ke QC dan Kontraktor.

### 8. Jawaban Kontraktor

Kontraktor dapat mengisi hasil pekerjaan, seperti:

- Penyebab kerusakan.
- Antisipasi.
- Keterangan perbaikan.
- Jawaban kontraktor.
- Jadwal rencana perbaikan.
- Foto hasil perbaikan.
- Status pekerjaan.

### 9. Upload Foto

Sistem mendukung upload foto untuk:

- Foto temuan.
- Foto pengaduan pelanggan.
- Foto jawaban atau hasil perbaikan kontraktor.

Upload dibuat lebih aman dengan validasi jenis file, ukuran file, dan penyimpanan nama file random.

### 10. Cetak Dokumen / PDF

File `print.php` digunakan untuk menampilkan dokumen laporan yang dapat dicetak melalui browser atau disimpan sebagai PDF.

Dokumen dapat menampilkan:

- Data proyek.
- Nomor laporan atau nomor KQI.
- Data pelapor.
- Keterangan temuan.
- Foto temuan.
- Jawaban kontraktor.
- Foto hasil perbaikan.
- Tanda tangan atau keterangan pihak terkait.

### 11. Export dan Import Excel

File `excel.php` dan fitur Excel di dashboard digunakan untuk membantu pengelolaan data.

Fitur Excel meliputi:

- Export data temuan.
- Export data jawaban.
- Revisi data massal melalui Excel.
- Import kembali data yang sudah diperbarui.

Catatan penting: kolom ID tidak boleh diubah karena digunakan sistem untuk mencocokkan data.

### 12. Audit Logs

Sistem mencatat aktivitas penting, seperti:

- Login berhasil.
- Login gagal.
- Perubahan data.
- Workflow temuan.
- Aktivitas admin.
- Aktivitas mencurigakan.

Admin dapat melihat audit logs dari dashboard.

### 13. Banned Access

Admin dapat melakukan ban terhadap IP atau username yang mencurigakan.

Fitur ini membantu mencegah akses tidak aman ke dashboard internal.

---

## Struktur Database

Database utama bernama:

```text
ciputra_sh
```

Tabel utama yang digunakan:

| Tabel | Fungsi |
|---|---|
| `proyek` | Menyimpan data master proyek Ciputra. |
| `users` | Menyimpan akun internal Admin SH-1, QC, dan Kontraktor. |
| `temuan` | Menyimpan data temuan QC dan keluhan pelanggan. |
| `foto_temuan` | Menyimpan foto dari temuan atau pengaduan. |
| `jawaban` | Menyimpan jawaban dan hasil perbaikan dari Kontraktor. |
| `foto_jawaban` | Menyimpan foto hasil perbaikan Kontraktor. |
| `audit_logs` | Menyimpan riwayat aktivitas sistem. |
| `banned_access` | Menyimpan data IP/user yang diblokir. |
| `login_attempts` | Menyimpan percobaan login untuk rate limit. |
| `public_submit_attempts` | Menyimpan limit submit form publik. |
| `endpoint_rate_limits` | Menyimpan pembatasan endpoint penting. |

---

## API Utama

| File API | Fungsi |
|---|---|
| `api/auth.php` | Login, logout, cek session user, dan blokir register publik. |
| `api/complaints.php` | CRUD temuan, pengaduan publik, detail, workflow, jawaban, upload, bulk update. |
| `api/dashboard.php` | Data ringkasan dan statistik dashboard. |
| `api/projects.php` | Master proyek: list, tambah, edit, aktif/nonaktif, hapus. |
| `api/users.php` | Kelola akun internal QC dan Kontraktor. |
| `api/logs.php` | Audit logs, banned access, ban, unban, hapus log. |
| `api/config.php` | Koneksi database, session, CSRF, helper, security, upload validation, rate limit. |

---

## Keamanan Sistem

Beberapa keamanan yang sudah diterapkan:

- Tidak ada register publik.
- Pelanggan tidak memiliki dashboard login.
- Login hanya untuk Admin SH-1, QC, dan Kontraktor.
- Password menggunakan hashing PHP.
- Session dibuat dengan pengaturan keamanan.
- CSRF token untuk request yang mengubah data.
- Rate limit untuk login.
- Rate limit untuk submit form publik.
- Rate limit untuk endpoint penting.
- Role based access control.
- Scope data berdasarkan proyek untuk QC dan Kontraktor.
- Validasi file upload.
- Proteksi folder `uploads`, `logs`, dan `backups` menggunakan `.htaccess`.
- Proteksi file konfigurasi dan SQL dari akses publik.
- Audit log aktivitas penting.
- Fitur banned IP/user.

---

## Cara Menjalankan di XAMPP

### 1. Copy Project

Copy folder project ke:

```text
C:\xampp\htdocs\ciputra_sh
```

### 2. Jalankan XAMPP

Buka XAMPP Control Panel, lalu start:

```text
Apache
MySQL
```

### 3. Buat Database

Buka phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Buat database baru:

```text
ciputra_sh
```

### 4. Import SQL

Untuk install baru, import salah satu file berikut:

```text
database_install_full.sql
```

atau:

```text
ciputra_sh_revisi_FINAL.sql
```

Untuk upgrade dari database lama, backup dulu database lama, lalu jalankan:

```text
migration_upgrade_to_85.sql
```

Jika memakai revisi next, jalankan juga:

```text
update_ciputra_sh_v_next.sql
```

### 5. Atur Config Database

Copy file:

```text
api/config.example.php
```

menjadi:

```text
api/config.local.php
```

Untuk XAMPP default, isi konfigurasi biasanya:

```php
define('CIPUTRA_DB_HOST', '127.0.0.1');
define('CIPUTRA_DB_PORT', '3306');
define('CIPUTRA_DB_NAME', 'ciputra_sh');
define('CIPUTRA_DB_USER', 'root');
define('CIPUTRA_DB_PASS', '');
```

### 6. Buka Website

Akses melalui browser:

```text
http://localhost/ciputra_sh/
```

---

## Akun Demo

| Role | Username | Password |
|---|---|---|
| Admin SH-1 | `admin` | `admin123` |
| QC | `qc` | `qc123` |
| Kontraktor | `kontraktor` | `kontraktor123` |

Setelah berhasil login, password default sebaiknya langsung diganti untuk keamanan.

---

## Cara Deploy ke Hosting

### Shared Hosting

1. Upload isi folder `ciputra_sh` ke `public_html` atau subfolder hosting.
2. Buat database MySQL dari panel hosting.
3. Import file SQL.
4. Copy `api/config.example.php` menjadi `api/config.production.php`.
5. Isi username, password, nama database, dan host database sesuai hosting.
6. Pastikan folder berikut writable:

```text
uploads/
logs/
backups/
```

7. Pastikan `.htaccess` aktif.
8. Login admin dan ganti password default.

### VPS

Untuk VPS, siapkan:

- Apache atau Nginx.
- PHP 8+.
- MySQL atau MariaDB.
- Extension PDO MySQL.
- SSL/HTTPS.
- Permission folder upload dan log.

---

## Catatan File Penting

| File | Keterangan |
|---|---|
| `index.php` | Halaman utama publik dan dashboard internal. |
| `print.php` | Halaman cetak laporan/dokumen. |
| `excel.php` | Halaman export/import Excel. |
| `database_install_full.sql` | SQL install database penuh. |
| `ciputra_sh_revisi_FINAL.sql` | SQL final revisi project. |
| `migration_upgrade_to_85.sql` | SQL upgrade database lama ke versi production readiness. |
| `update_ciputra_sh_v_next.sql` | SQL update tambahan master proyek, audit, dan ban access. |
| `backup_database.bat` | Script backup database untuk Windows/XAMPP. |
| `.htaccess` | Proteksi file sensitif dan cache asset. |

---

## File yang Tidak Disarankan untuk Diunggah ke GitHub Public

Untuk keamanan data asli, file berikut sebaiknya tidak diunggah ke repository public jika berisi data production:

```text
api/config.local.php
api/config.production.php
logs/app.log
backups/*.sql
uploads/ data asli pelanggan
```

Gunakan `api/config.example.php` sebagai contoh konfigurasi yang aman untuk repository.

---

## Backup dan Recovery

Backup minimal harus mencakup:

1. Database `.sql`.
2. Folder `uploads`.
3. File konfigurasi database.
4. Folder project final.

Cara backup database:

- Melalui phpMyAdmin Export.
- Atau menjalankan `backup_database.bat` di Windows/XAMPP.

Jika sistem dipindahkan ke komputer/server baru:

1. Copy folder project.
2. Import database backup.
3. Copy folder uploads.
4. Buat ulang config database.
5. Jalankan Apache dan MySQL.
6. Test login, submit pengaduan, upload foto, print, dan Excel.

---

## Status Kesiapan Project

Project ini sudah disiapkan untuk:

- Demo lokal XAMPP.
- Presentasi project.
- Shared hosting PHP/MySQL.
- VPS kecil sampai menengah.
- Pengembangan lanjutan menuju production.

Catatan jujur:

- Sistem masih single-server.
- Object storage/cloud storage belum diintegrasikan langsung.
- Load balancer belum dibutuhkan untuk skala kecil/menengah.
- Untuk production sungguhan, password default wajib diganti, data asli harus diamankan, dan backup rutin harus dijalankan.

---

## Pengembang

Project ini dibuat dan dikembangkan oleh:

```text
Aidil Farhan Rares
```

Sebagai project sistem pengaduan dan monitoring pekerjaan rumah berbasis web untuk kebutuhan dokumentasi, demo, dan pengembangan sistem internal.

---

## Lisensi dan Catatan Penggunaan

Repository ini digunakan untuk kebutuhan pembelajaran, presentasi, demo, dan pengembangan project. Jika sistem digunakan untuk data asli, pastikan konfigurasi database, akses admin, file upload, backup, dan keamanan hosting sudah disiapkan dengan benar.
