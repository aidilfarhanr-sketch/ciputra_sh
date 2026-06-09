README DEPLOYMENT CIPUTRA SH-1 — PRODUCTION READINESS 85%

Tujuan:
Menjalankan proyek di XAMPP lokal, shared hosting, atau VPS tanpa mengubah desain utama.

A. INSTALL DI XAMPP LOKAL
1. Copy folder ciputra_sh ke C:\xampp\htdocs\ciputra_sh.
2. Buka XAMPP Control Panel, start Apache dan MySQL.
3. Buka phpMyAdmin, buat/import database:
   - untuk install baru: database_install_full.sql atau ciputra_sh_revisi_FINAL.sql
   - untuk upgrade dari database lama: jalankan migration_upgrade_to_85.sql setelah backup.
4. Copy api/config.example.php menjadi api/config.local.php.
5. Sesuaikan DB_HOST, DB_NAME, DB_USER, DB_PASS jika berbeda.
6. Pastikan folder uploads, logs, backups writable.
7. Buka http://localhost/ciputra_sh/.
8. Login admin default: admin / admin123, lalu segera ganti password.

B. PINDAH KE SHARED HOSTING
1. Upload isi folder ciputra_sh ke public_html/ciputra_sh atau root domain.
2. Buat database MySQL dari panel hosting.
3. Import database_install_full.sql untuk install baru atau migration_upgrade_to_85.sql untuk upgrade.
4. Copy api/config.example.php menjadi api/config.production.php.
5. Isi kredensial database hosting.
6. Set environment CIPUTRA_ENV=production jika panel hosting mendukung.
7. Pastikan .htaccess aktif dan AllowOverride diizinkan.
8. Pastikan folder uploads, logs, backups writable; untuk production backup sebaiknya dipindahkan keluar public_html.

C. PINDAH KE VPS
1. Install Apache/Nginx, PHP 8+, extension PDO MySQL, MySQL/MariaDB.
2. Arahkan document root ke folder ciputra_sh.
3. Aktifkan HTTPS dengan SSL certificate.
4. Buat config.production.php dengan kredensial database production.
5. Set permission:
   - uploads: writable oleh user web server
   - logs: writable, tidak public
   - backups: writable, tidak public
6. Matikan display error di production. Proyek sudah menyembunyikan debug error user.

D. CHECKLIST DEPLOYMENT
[ ] Backup database lama sebelum update.
[ ] Import database atau migration berhasil.
[ ] api/config.local.php atau api/config.production.php sudah benar.
[ ] Login admin berhasil.
[ ] Password default admin diganti.
[ ] Register publik tetap tidak ada.
[ ] Form keluhan publik berhasil submit.
[ ] Admin bisa buat akun QC/Kontraktor per proyek.
[ ] QC hanya melihat proyek sendiri.
[ ] Kontraktor hanya melihat tugas sendiri.
[ ] Upload foto berjalan.
[ ] Export/import Excel berjalan.
[ ] Print berjalan.
[ ] logs/app.log bisa terisi.
[ ] backup_database.bat bisa dipakai di Windows/XAMPP.

E. HTTPS DAN COOKIE SECURE
Saat HTTPS aktif, session cookie otomatis memakai Secure. Untuk lokal HTTP, Secure tidak dipaksa agar tetap bisa berjalan di XAMPP.

==================================================
CHECKLIST TAMBAHAN SETELAH REVISI NEXT
==================================================
- Pastikan file update_ciputra_sh_v_next.sql sudah dijalankan untuk database lama.
- Pastikan folder logs, backups, uploads memiliki .htaccess.
- Pastikan mod_rewrite/mod_headers aktif jika hosting mendukung.
- Ganti password default admin setelah deploy.
- Cek menu Master Proyek dan Audit Logs hanya muncul untuk Admin SH-1.
- Cek IP/user banned tidak bisa login.
