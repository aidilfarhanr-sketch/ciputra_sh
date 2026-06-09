<?php
require __DIR__ . '/api/config.php';
$user = require_login();
$mode = $_GET['mode'] ?? 'temuan';
$mode = in_array($mode, ['temuan', 'jawaban'], true) ? $mode : 'temuan';
$jenis = $_GET['jenis'] ?? 'qc';
$jenis = in_array($jenis, ['qc', 'pelanggan'], true) ? $jenis : 'qc';
$idsRaw = trim((string)($_GET['ids'] ?? ''));
$params = [$jenis];
$where = "WHERE t.jenis_temuan = ?";
if ($idsRaw !== '') {
    $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw)), fn($x) => $x > 0));
    if ($ids) {
        $where .= " AND t.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = array_merge($params, $ids);
    }
}
if ($user['role'] === 'qc' && !empty($user['proyek_id'])) {
    $where .= " AND t.proyek_id = ?";
    $params[] = (int)$user['proyek_id'];
}
if ($user['role'] === 'kontraktor' && !empty($user['proyek_id'])) {
    $where .= " AND t.proyek_id = ? AND t.kontraktor_id = ?";
    $params[] = (int)$user['proyek_id'];
    $params[] = (int)$user['id'];
}
$stmt = $pdo->prepare("SELECT t.*, p.nama_proyek, p.lokasi,
    j.id jawaban_id, j.penyebab, j.antisipasi AS jawaban_antisipasi, j.keterangan_perbaikan, j.tanggal_selesai, j.status_perbaikan, j.ttd_admin, j.ttd_proyek, j.ttd_pemilik_rumah
    FROM temuan t
    JOIN proyek p ON p.id=t.proyek_id
    LEFT JOIN jawaban j ON j.temuan_id=t.id
    {$where}
    ORDER BY p.nama_proyek ASC, t.tanggal ASC, t.id ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

function fetch_photos(PDO $pdo, int $temuanId): array {
    $stmt = $pdo->prepare("SELECT * FROM foto_temuan WHERE temuan_id=? ORDER BY id ASC");
    $stmt->execute([$temuanId]);
    return $stmt->fetchAll();
}
function fetch_answer_photos(PDO $pdo, ?int $jawabanId): array {
    if (!$jawabanId) return [];
    $stmt = $pdo->prepare("SELECT * FROM foto_jawaban WHERE jawaban_id=? ORDER BY id ASC");
    $stmt->execute([$jawabanId]);
    return $stmt->fetchAll();
}
function img_box(?string $path, string $caption = ''): string {
    if (!$path) return '<div class="no-img">Belum ada foto</div>';
    $safe = e($path);
    return '<figure class="photo-box"><img src="' . $safe . '" alt="Foto"><figcaption>' . e($caption) . '</figcaption></figure>';
}
$title = $jenis === 'qc'
    ? ($mode === 'temuan' ? 'FORM TEMUAN QC / KEY QUALITY ITEM' : 'FORM JAWABAN QC / KEY QUALITY ITEM')
    : ($mode === 'temuan' ? 'FORM PENGADUAN PELANGGAN' : 'FORM JAWABAN PELANGGAN/KONSUMEN');
$docNo = $jenis === 'qc' ? 'QC-FRM-001' : 'PLG-FRM-001';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#e5e7eb;color:#111;font-family:Arial,Helvetica,sans-serif}.no-print{max-width:210mm;margin:14px auto 0;display:flex;justify-content:flex-end;gap:8px}.btn{border:0;border-radius:8px;background:#0f7050;color:#fff;padding:10px 14px;font-weight:700;cursor:pointer}.btn.gray{background:#475569}.paper{width:210mm;min-height:297mm;background:#fff;margin:12px auto;padding:10mm;box-shadow:0 12px 36px rgba(15,23,42,.18)}.header{display:grid;grid-template-columns:32mm 1fr 52mm;border:1.5px solid #111}.logo-box{display:grid;place-items:center;border-right:1.5px solid #111;padding:3mm}.logo-box img{max-width:26mm;max-height:20mm}.title-box{display:grid;place-items:center;text-align:center;padding:4mm}.title-box h1{margin:0;font-size:13pt;line-height:1.25;text-transform:uppercase}.title-box p{margin:2mm 0 0;font-size:8pt;color:#334155}.doc-box table{width:100%;height:100%;border-collapse:collapse;font-size:7.6pt}.doc-box td{border-left:1.5px solid #111;border-bottom:1px solid #111;padding:1.1mm}.doc-box tr:last-child td{border-bottom:0}.project{border:1.5px solid #111;border-top:0;padding:3mm;text-align:center}.project h2{font-size:14pt;color:#0f7050;margin:0;text-transform:uppercase}.project p{font-size:8.5pt;margin:1mm 0 0;color:#334155}.section-title{margin:6mm 0 2mm;font-weight:800;font-size:10.5pt;color:#0f7050;border-left:4px solid #0f7050;padding-left:2mm}.info-table,.main-table{width:100%;border-collapse:collapse;table-layout:fixed}.info-table td,.main-table th,.main-table td{border:1px solid #111;padding:2mm;vertical-align:top;font-size:8.6pt;line-height:1.32}.info-table td:first-child{width:34mm;background:#f1f5f9;font-weight:700}.main-table th{background:#dbeafe;text-align:center;font-weight:800}.main-table .no{width:9mm;text-align:center}.main-table .photo-col{width:50mm}.photo-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:2mm}.photo-box{margin:0;break-inside:avoid}.photo-box img{width:100%;height:36mm;object-fit:cover;border:1px solid #94a3b8;display:block}.photo-box figcaption{font-size:7.3pt;color:#334155;margin-top:1mm}.no-img{height:36mm;background:#f8fafc;border:1px solid #cbd5e1;display:grid;place-items:center;color:#64748b;font-size:8pt}.status{display:inline-block;border:1px solid #111;border-radius:999px;padding:1mm 2mm;font-size:7.5pt;margin-top:1mm}.yellow{background:#fef9c3}.sign-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7mm;margin-top:8mm;break-inside:avoid}.sign-box{border:1px solid #111;min-height:30mm;text-align:center;padding:2mm;font-size:8.5pt;display:flex;flex-direction:column;justify-content:space-between}.footer-note{margin-top:5mm;font-size:7.8pt;color:#475569;display:flex;justify-content:space-between;gap:8px}.record{break-inside:avoid;page-break-inside:avoid;margin-bottom:8mm}.record + .record{border-top:2px dashed #94a3b8;padding-top:6mm}.empty{padding:20mm;border:1px solid #111;text-align:center;color:#64748b}.small{font-size:7.5pt;color:#475569}@media print{body{background:#fff}.no-print{display:none}.paper{box-shadow:none;margin:0;width:auto;min-height:auto;padding:8mm}@page{size:A4 portrait;margin:8mm}.record{break-inside:avoid}.photo-box img{height:32mm}}@media(max-width:760px){.paper{width:100%;margin:0;padding:12px}.header{grid-template-columns:1fr}.logo-box,.doc-box td{border-left:0;border-right:0}.photo-grid{grid-template-columns:1fr}.sign-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="no-print"><button class="btn gray" onclick="history.back()">Kembali</button><button class="btn" onclick="window.print()">Cetak / Save PDF</button></div>
<div class="paper">
  <div class="header">
    <div class="logo-box"><img src="assets/Ciputra.png" alt="Ciputra"></div>
    <div class="title-box"><h1><?= e($title) ?></h1><p>Sistem Pengaduan Online Ciputra SH-1</p></div>
    <div class="doc-box"><table><tr><td><b>No. Dok.</b></td><td><?= e($docNo) ?></td></tr><tr><td><b>Revisi</b></td><td>Final 2026</td></tr><tr><td><b><?= $jenis === 'qc' ? 'No. KQI' : 'No. Laporan' ?></b></td><td><?= e($jenis === 'qc' ? ($rows[0]['nomor_kqi'] ?? '-') : ($rows[0]['nomor_dokumen'] ?? '-')) ?></td></tr><tr><td><b>Tanggal Cetak</b></td><td><?= e(date('d-m-Y')) ?></td></tr></table></div>
  </div>
  <div class="project"><h2><?= e($rows[0]['nama_proyek'] ?? 'CIPUTRA SH-1') ?></h2><p><?= e($rows[0]['lokasi'] ?? 'Sistem Pengaduan Online') ?></p></div>

  <?php if (!$rows): ?>
    <div class="empty">Tidak ada data untuk dicetak.</div>
  <?php endif; ?>

  <?php foreach ($rows as $idx => $r): $photos = fetch_photos($pdo, (int)$r['id']); $ansPhotos = fetch_answer_photos($pdo, $r['jawaban_id'] ? (int)$r['jawaban_id'] : null); ?>
    <div class="record">
      <div class="section-title">A. Identitas <?= $jenis === 'qc' ? 'Temuan QC' : 'Pengaduan Pelanggan/Konsumen' ?></div>
      <table class="info-table">
        <tr><td><?= $jenis === 'qc' ? 'No. KQI' : 'No. Dokumen / Laporan' ?></td><td><?= e($jenis === 'qc' ? ($r['nomor_kqi'] ?: '-') : ($r['nomor_dokumen'] ?: '-')) ?></td></tr>
        <?php if ($jenis === 'qc'): ?><tr><td>No. Dokumen</td><td><?= e($r['nomor_dokumen'] ?: '-') ?></td></tr><?php endif; ?>
        <tr><td>Proyek</td><td><?= e($r['nama_proyek']) ?></td></tr>
        <tr><td>Blok / Unit</td><td><?= e($r['blok_unit']) ?></td></tr>
        <tr><td>Tanggal</td><td><?= e(day_name_id($r['tanggal'])) ?>, <?= e($r['tanggal']) ?></td></tr>
        <tr><td>Nama Pelapor</td><td><?= e($r['nama_pelapor']) ?></td></tr>
        <tr><td>No HP</td><td><?= e($r['no_hp']) ?></td></tr>
        <tr><td>Status</td><td><span class="status"><?= e(status_label($r['status_validasi'])) ?></span> <span class="status"><?= e(status_label($r['status_jawaban'])) ?></span></td></tr>
      </table>

      <?php if ($mode === 'temuan'): ?>
        <div class="section-title">B. Detail Temuan</div>
        <table class="main-table">
          <thead><tr><th class="no">No.</th><th class="photo-col">Foto Temuan</th><th>Keterangan / Fakta</th><th class="yellow">Penyebab Utama</th><th class="yellow">Cara Antisipasi</th></tr></thead>
          <tbody>
            <?php if (!$photos): ?>
              <tr><td class="no">1</td><td><?= img_box(null) ?></td><td><?= nl2br(e($r['keterangan'])) ?></td><td><?= nl2br(e($r['penyebab_utama'] ?: '-')) ?></td><td><?= nl2br(e($r['antisipasi'] ?: '-')) ?></td></tr>
            <?php else: foreach ($photos as $i => $p): ?>
              <tr>
                <td class="no"><?= $i + 1 ?></td>
                <td><?= img_box($p['foto_path'], $p['keterangan_foto'] ?: 'Foto temuan') ?></td>
                <td><b>Area:</b> <?= e($p['area_kerusakan'] ?: '-') ?><br><br><?= nl2br(e($p['keterangan_foto'] ?: $r['keterangan'])) ?><br><br><span class="small"><b>Fakta:</b> <?= e($r['fakta'] ?: '-') ?></span></td>
                <td><?= nl2br(e($r['penyebab_utama'] ?: '-')) ?></td>
                <td><?= nl2br(e($r['antisipasi'] ?: '-')) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="section-title">B. Jawaban / Hasil Perbaikan</div>
        <table class="main-table">
          <thead><tr><th>Foto Temuan</th><th>Penyebab</th><th>Antisipasi</th><th>Foto Hasil Perbaikan</th><th>Keterangan Hasil</th></tr></thead>
          <tbody><tr>
            <td><div class="photo-grid"><?php foreach (array_slice($photos,0,4) as $p) echo img_box($p['foto_path'], $p['keterangan_foto']); if(!$photos) echo img_box(null); ?></div></td>
            <td><?= nl2br(e($r['penyebab'] ?: $r['penyebab_utama'] ?: 'Belum diisi.')) ?></td>
            <td><?= nl2br(e($r['jawaban_antisipasi'] ?: $r['antisipasi'] ?: 'Belum diisi.')) ?></td>
            <td><div class="photo-grid"><?php foreach ($ansPhotos as $p) echo img_box($p['foto_path'], $p['keterangan_foto']); if(!$ansPhotos) echo img_box(null); ?></div></td>
            <td><?= nl2br(e($r['keterangan_perbaikan'] ?: 'Belum diisi.')) ?><br><br><b>Tanggal selesai:</b> <?= e($r['tanggal_selesai'] ?: '-') ?><br><b>Status:</b> <?= e(status_label($r['status_perbaikan'] ?: 'proses')) ?></td>
          </tr></tbody>
        </table>
      <?php endif; ?>

      <div class="sign-grid">
        <div class="sign-box"><b>Admin / QC</b><span><?= e($r['ttd_admin'] ?: '________________') ?></span></div>
        <div class="sign-box"><b>Kontraktor</b><span><?= e($r['ttd_proyek'] ?: '________________') ?></span></div>
        <div class="sign-box"><b><?= $jenis === 'qc' ? 'Project Manager' : 'Pemilik Rumah' ?></b><span><?= e($jenis === 'qc' ? '________________' : ($r['ttd_pemilik_rumah'] ?: '________________')) ?></span></div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="footer-note"><span>Dicetak otomatis dari Sistem Pengaduan Online Ciputra SH-1.</span><span>Dicetak oleh: <?= e($user['nama_lengkap']) ?></span></div>
</div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),450));</script>
</body>
</html>
