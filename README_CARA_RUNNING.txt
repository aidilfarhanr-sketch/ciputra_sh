CARA RUNNING CIPUTRA SH-1 DI XAMPP

1. Copy folder ciputra_sh ke:
   C:\xampp\htdocs\ciputra_sh

2. Start XAMPP:
   - Apache: Start
   - MySQL: Start

3. Import database:
   - Install baru: import database_install_full.sql atau ciputra_sh_revisi_FINAL.sql
   - Upgrade dari database lama: backup dulu, lalu import migration_upgrade_to_85.sql

4. Config database:
   - Copy api/config.example.php menjadi api/config.local.php
   - Untuk XAMPP default biasanya:
     DB_HOST 127.0.0.1
     DB_NAME ciputra_sh
     DB_USER root
     DB_PASS kosong

5. Buka browser:
   http://localhost/ciputra_sh/

6. Login default demo:
   Admin: admin / admin123
   QC: qc / qc123
   Kontraktor: kontraktor / kontraktor123

7. Setelah masuk admin:
   - Ganti password default.
   - Buat proyek jika perlu.
   - Buat akun QC/Kontraktor per proyek.
   - Test form keluhan publik tanpa login.

8. Catatan keamanan:
   - Tidak ada register publik.
   - Pelanggan tidak bisa login.
   - CSRF aktif untuk semua POST/PUT/PATCH/DELETE.
   - Rate limit aktif untuk login, submit publik, upload, dan endpoint penting.
   - logs/app.log dan folder backups diproteksi .htaccess.

9. Backup:
   - Bisa lewat phpMyAdmin Export.
   - Bisa jalankan backup_database.bat di Windows/XAMPP.

==================================================
CATATAN REVISI NEXT - MASTER PROYEK, LOGO, AUDIT BAN
==================================================
1. Jika memakai database lama, jalankan file update_ciputra_sh_v_next.sql melalui phpMyAdmin setelah backup database.
2. Login sebagai admin, buka menu "Master Proyek" untuk menambah/edit/nonaktifkan proyek.
3. Proyek aktif otomatis muncul di dropdown form pengajuan user, form temuan, dan form akun internal.
4. No KQI mengikuti kode proyek: contoh kode BSD menjadi KQI-BSD-YYYYMMDD-001.
5. Buka "Audit Logs" untuk melihat login sukses/gagal, menghapus log, ban IP, ban username/user, dan unban.
6. Jika IP/user dibanned, login akan ditolak dengan pesan umum tanpa detail teknis.
7. Logo Ciputra sudah dibuat transparan di navbar, sidebar, login/landing, dan loading spinner.
