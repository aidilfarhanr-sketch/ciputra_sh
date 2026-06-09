README BACKUP & RECOVERY CIPUTRA SH-1 — PRODUCTION READINESS 85%

Backup minimal harus mencakup:
1. Database .sql
2. Folder uploads
3. File konfigurasi api/config.local.php atau api/config.production.php
4. File project final/ZIP final

A. BACKUP DATABASE DARI PHPMYADMIN
1. Buka phpMyAdmin.
2. Pilih database ciputra_sh.
3. Klik Export.
4. Pilih Quick atau Custom.
5. Format SQL.
6. Download file .sql dan simpan di tempat aman.

B. BACKUP DATABASE VIA SCRIPT WINDOWS/XAMPP
1. Jalankan backup_database.bat.
2. File backup masuk ke folder backups.
3. Setelah backup selesai, copy file .sql ke tempat aman di luar htdocs.

C. RESTORE DATABASE
1. Buka phpMyAdmin.
2. Buat database ciputra_sh jika belum ada.
3. Klik Import.
4. Pilih file .sql backup.
5. Klik Go.

D. BACKUP/RESTORE UPLOADS
Backup:
- Copy folder uploads/temuan dan uploads/jawaban ke tempat aman.
Restore:
- Copy kembali folder uploads ke project baru.
- Pastikan permission writable.

E. RECOVERY CEPAT JIKA XAMPP/MYSQL ERROR
1. Install XAMPP baru.
2. Copy folder ciputra_sh ke htdocs.
3. Import file backup database .sql.
4. Copy folder uploads dari backup.
5. Copy config.local.php atau buat lagi dari config.example.php.
6. Start Apache/MySQL.
7. Login admin.
8. Test dashboard, form publik, upload, print, export.

F. CHECKLIST RECOVERY
[ ] Database berhasil diimport.
[ ] User admin bisa login.
[ ] Data proyek muncul.
[ ] Data keluhan/temuan muncul.
[ ] Foto upload muncul.
[ ] Export/print berjalan.
[ ] logs/app.log terisi jika ada error.
[ ] backup baru berhasil dibuat.

Catatan production:
Folder backups diproteksi .htaccess, tetapi tetap lebih aman menyimpan backup di luar public_html/htdocs atau cloud drive privat.

==================================================
SEBELUM MENJALANKAN UPDATE NEXT
==================================================
- Backup database lama dari phpMyAdmin.
- Backup folder uploads.
- Backup file project lama.
- Jalankan update_ciputra_sh_v_next.sql, bukan DROP TABLE, agar data lama aman.
- Setelah update, cek: login admin, Master Proyek, form pengajuan user, Audit Logs, ban/unban, upload foto, print PDF, dan export/import Excel.
