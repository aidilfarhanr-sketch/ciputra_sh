README CLOUD & COMPUTE CIPUTRA SH-1

Status saat ini:
Sistem siap untuk local/XAMPP, shared hosting PHP/MySQL, dan VPS kecil/menengah. Cloud storage dan load balancer belum wajib karena target saat ini production kecil/menengah.

Opsi compute:
1. Local/XAMPP: demo, development, ujian, validasi fitur.
2. Shared hosting PHP/MySQL: penggunaan kecil dengan traffic rendah/sedang.
3. VPS Apache/Nginx + PHP + MariaDB: production lebih stabil dan mudah diatur.
4. Cloud server: jika traffic, upload, dan database mulai besar.

Struktur yang disiapkan:
- Konfigurasi DB terpusat di api/config.php + config.local.php/config.production.php.
- Path upload relatif: uploads/temuan dan uploads/jawaban.
- Logs berada di logs/app.log dan diproteksi .htaccess.
- Backups berada di backups dan diproteksi .htaccess.

Rekomendasi cloud masa depan:
- Pindahkan database ke managed MySQL/MariaDB jika traffic tinggi.
- Pindahkan uploads ke object storage jika file foto semakin besar.
- Gunakan CDN untuk asset statis: CSS, JS, logo, gambar UI.
- Gunakan reverse proxy/load balancer jika ada beberapa server aplikasi.

Catatan permission:
- uploads harus writable.
- logs harus writable.
- backups harus writable hanya saat backup berjalan.
- config.production.php jangan bisa diakses publik.
