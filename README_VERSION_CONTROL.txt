README VERSION CONTROL & CI/CD CIPUTRA SH-1

A. GIT DASAR
1. Backup folder project dulu.
2. Jalankan: git init
3. Jalankan: git add .
4. Jalankan: git commit -m "Initial production-readiness 85"
5. Untuk revisi berikutnya: git checkout -b revisi-security-rate-limit

B. FILE YANG TIDAK BOLEH DICOMMIT
- api/config.local.php
- api/config.production.php
- logs/app.log
- backups/*.sql
- cache/*
- file upload production jika berisi data asli pelanggan

C. ROLLBACK JIKA ERROR
1. Simpan backup database sebelum deploy.
2. Catat commit terakhir yang stabil.
3. Jika error: git checkout <commit-stabil> atau restore ZIP backup.
4. Restore database dari file .sql backup.
5. Copy ulang folder uploads.

D. CI/CD SEDERHANA
Folder .github/workflows/php-ci.yml sudah ditambahkan.
Workflow mengecek:
- syntax PHP,
- file wajib,
- SQL install,
- migration,
- folder uploads/logs/backups.

E. CHECKLIST MANUAL DEPLOY
[ ] Pull/update file project.
[ ] Backup database.
[ ] Jalankan migration_upgrade_to_85.sql.
[ ] Test login admin/QC/Kontraktor.
[ ] Test form publik.
[ ] Test upload foto.
[ ] Test export/import Excel.
[ ] Test print.
[ ] Test backup.
