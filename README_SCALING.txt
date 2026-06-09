README LOAD BALANCING & SCALING CIPUTRA SH-1

Status saat ini:
Sistem masih single-server dan itu cukup untuk demo, XAMPP, shared hosting, atau production kecil/menengah.

Kapan perlu scaling:
- Data keluhan/temuan sudah puluhan ribu.
- Upload foto semakin besar.
- Banyak user internal aktif bersamaan.
- Dashboard mulai lambat.
- Traffic publik form keluhan meningkat.

Yang sudah disiapkan:
- Pagination server-side untuk list data.
- Index database untuk proyek_id, status, sumber_temuan/jenis_temuan, qc_id, kontraktor_id, created_at, deadline.
- Upload path relatif agar nanti mudah dipindahkan ke storage lain.
- Cache header untuk asset statis.
- Dokumentasi backup dan migration.

Langkah scaling masa depan:
1. Pindahkan database ke server database khusus.
2. Pindahkan folder uploads ke object storage/cloud storage.
3. Gunakan CDN untuk asset statis.
4. Gunakan VPS dengan RAM/CPU lebih tinggi.
5. Jika traffic besar, taruh load balancer di depan beberapa server aplikasi.
6. Simpan session di Redis/database jika aplikasi berjalan di lebih dari 1 server.

Catatan:
Load balancer belum dibuat karena proyek belum membutuhkan multi-server. Namun struktur dan dokumentasi sudah siap untuk berkembang.
