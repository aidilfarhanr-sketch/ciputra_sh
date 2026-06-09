README PERFORMANCE, CACHING & CDN CIPUTRA SH-1

Yang boleh dicache:
- asset statis: CSS, JS, logo, gambar UI, font.
- asset di folder assets.

Yang tidak boleh dicache:
- dashboard internal,
- data keluhan,
- data temuan,
- data jawaban kontraktor,
- data user,
- laporan internal,
- response API.

Implementasi:
- .htaccess memberi cache header untuk asset statis.
- api/config.php memberi no-store untuk response API.
- Daftar temuan memakai pagination server-side default 25 data per request.
- Query dashboard memakai agregasi.
- Index database ditambahkan melalui database_install_full.sql dan migration_upgrade_to_85.sql.

Asset versioning:
Jika nanti CSS/JS dipisahkan, gunakan pola:
- style.css?v=1.0.0
- app.js?v=1.0.0
Saat update besar, naikkan versi agar browser mengambil file baru.

CDN:
Chart.js dan SheetJS tetap boleh dari CDN. Jika koneksi CDN gagal, sistem tidak boleh blank; fitur Excel/chart dapat memberi pesan error ringan.

Optimasi gambar:
- Upload dibatasi maksimal 8 foto/request.
- Maksimal 6MB per foto dan 24MB total/request.
- Preview foto memakai lazy loading untuk daftar yang bisa dikembangkan lebih lanjut.
