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

function xls_photos(PDO $pdo, int $temuanId): array {
    $stmt = $pdo->prepare("SELECT * FROM foto_temuan WHERE temuan_id=? ORDER BY id ASC");
    $stmt->execute([$temuanId]);
    return $stmt->fetchAll();
}
function xls_answer_photos(PDO $pdo, ?int $jawabanId): array {
    if (!$jawabanId) return [];
    $stmt = $pdo->prepare("SELECT * FROM foto_jawaban WHERE jawaban_id=? ORDER BY id ASC");
    $stmt->execute([$jawabanId]);
    return $stmt->fetchAll();
}
function xls_photo_list(array $photos): string {
    if (!$photos) return 'Belum ada foto';
    $out = [];
    foreach ($photos as $i => $p) {
        $out[] = ($i + 1) . '. ' . ($p['foto_path'] ?? '') . ' - ' . ($p['keterangan_foto'] ?? 'Foto');
    }
    return implode("\n", $out);
}

$title = $jenis === 'qc'
    ? ($mode === 'temuan' ? 'FORM TEMUAN QC / KEY QUALITY ITEM' : 'FORM JAWABAN QC / KEY QUALITY ITEM')
    : ($mode === 'temuan' ? 'FORM PENGADUAN PELANGGAN' : 'FORM JAWABAN PELANGGAN/KONSUMEN');
$docNo = $jenis === 'qc' ? 'QC-FRM-001' : 'PLG-FRM-001';
$filename = 'ciputra-sh-' . $mode . '-' . $jenis . '-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= e($title) ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#111} .sheet{width:100%}.head td{border:1.5pt solid #111;padding:8px;vertical-align:middle}.logo{font-size:20pt;font-weight:bold;color:#0f7050;text-align:center}.title{font-size:16pt;font-weight:bold;text-align:center;text-transform:uppercase}.meta td{border:1pt solid #111;padding:5px}.project{font-size:15pt;font-weight:bold;color:#0f7050;text-align:center;text-transform:uppercase;border:1.5pt solid #111;padding:8px}.section{font-size:12pt;font-weight:bold;color:#0f7050;border-left:5px solid #0f7050;padding:7px}.tbl{border-collapse:collapse;width:100%;table-layout:fixed}.tbl th,.tbl td{border:1pt solid #111;padding:7px;vertical-align:top;white-space:pre-wrap}.tbl th{background:#dbeafe;text-align:center;font-weight:bold}.label{background:#f1f5f9;font-weight:bold;width:180px}.yellow{background:#fef9c3}.sign td{border:1pt solid #111;height:80px;text-align:center;vertical-align:top;padding:8px}.small{font-size:9pt;color:#475569}.photo-path{color:#0f7050}.spacer{height:14px}.record-break{height:22px;border-top:2px dashed #94a3b8}.footer{font-size:9pt;color:#475569}.no{width:36px;text-align:center}.w-photo{width:240px}.w-medium{width:260px}
</style>
</head>
<body>
<table class="sheet">
<tr class="head"><td class="logo" style="width:170px">CIPUTRA</td><td class="title"><?= e($title) ?><br><span class="small">Sistem Pengaduan Online Ciputra SH-1</span></td><td style="width:260px"><table class="meta"><tr><td><b>No. Dok.</b></td><td><?= e($docNo) ?></td></tr><tr><td><b>Revisi</b></td><td>Final 2026</td></tr><tr><td><b><?= $jenis === 'qc' ? 'No. KQI' : 'No. Laporan' ?></b></td><td><?= e($jenis === 'qc' ? ($rows[0]['nomor_kqi'] ?? '-') : ($rows[0]['nomor_dokumen'] ?? '-')) ?></td></tr><tr><td><b>Tanggal Cetak</b></td><td><?= e(date('d-m-Y')) ?></td></tr></table></td></tr>
<tr><td colspan="3" class="project"><?= e($rows[0]['nama_proyek'] ?? 'CIPUTRA SH-1') ?><br><span class="small"><?= e($rows[0]['lokasi'] ?? 'Sistem Pengaduan Online') ?></span></td></tr>
</table>

<?php if (!$rows): ?>
<table class="tbl"><tr><td>Tidak ada data untuk dicetak.</td></tr></table>
<?php endif; ?>

<?php foreach ($rows as $idx => $r): $photos = xls_photos($pdo, (int)$r['id']); $ansPhotos = xls_answer_photos($pdo, $r['jawaban_id'] ? (int)$r['jawaban_id'] : null); ?>
<table class="sheet"><tr><td class="spacer"></td></tr><tr><td class="section">A. Identitas <?= $jenis === 'qc' ? 'Temuan QC' : 'Pengaduan Pelanggan/Konsumen' ?></td></tr></table>
<table class="tbl">
<tr><td class="label"><?= $jenis === 'qc' ? 'No. KQI' : 'No. Dokumen / Laporan' ?></td><td><?= e($jenis === 'qc' ? ($r['nomor_kqi'] ?: '-') : ($r['nomor_dokumen'] ?: '-')) ?></td></tr>
<?php if ($jenis === 'qc'): ?><tr><td class="label">No. Dokumen</td><td><?= e($r['nomor_dokumen'] ?: '-') ?></td></tr><?php endif; ?>
<tr><td class="label">Proyek</td><td><?= e($r['nama_proyek']) ?></td></tr>
<tr><td class="label">Blok / Unit</td><td><?= e($r['blok_unit']) ?></td></tr>
<tr><td class="label">Tanggal</td><td><?= e(day_name_id($r['tanggal'])) ?>, <?= e($r['tanggal']) ?></td></tr>
<tr><td class="label">Nama Pelapor</td><td><?= e($r['nama_pelapor']) ?></td></tr>
<tr><td class="label">No HP</td><td><?= e($r['no_hp']) ?></td></tr>
<tr><td class="label">Status</td><td><?= e(status_label($r['status_validasi'])) ?> | <?= e(status_label($r['status_jawaban'])) ?></td></tr>
</table>

<?php if ($mode === 'temuan'): ?>
<table class="sheet"><tr><td class="spacer"></td></tr><tr><td class="section">B. Detail Temuan</td></tr></table>
<table class="tbl">
<thead><tr><th class="no">No.</th><th class="w-photo">Foto Temuan</th><th class="w-medium">Keterangan / Fakta</th><th class="yellow">Penyebab Utama</th><th class="yellow">Cara Antisipasi</th></tr></thead>
<tbody>
<?php if (!$photos): ?>
<tr><td class="no">1</td><td>Belum ada foto</td><td><?= nl2br(e($r['keterangan'])) ?></td><td><?= nl2br(e($r['penyebab_utama'] ?: '-')) ?></td><td><?= nl2br(e($r['antisipasi'] ?: '-')) ?></td></tr>
<?php else: foreach ($photos as $i => $p): ?>
<tr><td class="no"><?= $i + 1 ?></td><td class="photo-path"><?= e($p['foto_path']) ?><br><span class="small"><?= e($p['keterangan_foto'] ?: 'Foto temuan') ?></span></td><td><b>Area:</b> <?= e($p['area_kerusakan'] ?: '-') ?><br><?= nl2br(e($p['keterangan_foto'] ?: $r['keterangan'])) ?><br><span class="small"><b>Fakta:</b> <?= e($r['fakta'] ?: '-') ?></span></td><td><?= nl2br(e($r['penyebab_utama'] ?: '-')) ?></td><td><?= nl2br(e($r['antisipasi'] ?: '-')) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table>
<?php else: ?>
<table class="sheet"><tr><td class="spacer"></td></tr><tr><td class="section">B. Jawaban / Hasil Perbaikan</td></tr></table>
<table class="tbl">
<thead><tr><th>Foto Temuan</th><th>Penyebab</th><th>Antisipasi</th><th>Foto Hasil Perbaikan</th><th>Keterangan Hasil</th></tr></thead>
<tbody><tr><td class="photo-path"><?= nl2br(e(xls_photo_list($photos))) ?></td><td><?= nl2br(e($r['penyebab'] ?: $r['penyebab_utama'] ?: 'Belum diisi.')) ?></td><td><?= nl2br(e($r['jawaban_antisipasi'] ?: $r['antisipasi'] ?: 'Belum diisi.')) ?></td><td class="photo-path"><?= nl2br(e(xls_photo_list($ansPhotos))) ?></td><td><?= nl2br(e($r['keterangan_perbaikan'] ?: 'Belum diisi.')) ?><br><br><b>Tanggal selesai:</b> <?= e($r['tanggal_selesai'] ?: '-') ?><br><b>Status:</b> <?= e(status_label($r['status_perbaikan'] ?: 'proses')) ?></td></tr></tbody>
</table>
<?php endif; ?>

<table class="sheet"><tr><td class="spacer"></td></tr></table>
<table class="sign"><tr><td><b>Admin / QC</b><br><br><br><?= e($r['ttd_admin'] ?: '________________') ?></td><td><b>Kontraktor</b><br><br><br><?= e($r['ttd_proyek'] ?: '________________') ?></td><td><b><?= $jenis === 'qc' ? 'Project Manager' : 'Pemilik Rumah' ?></b><br><br><br><?= e($jenis === 'qc' ? '________________' : ($r['ttd_pemilik_rumah'] ?: '________________')) ?></td></tr></table>
<?php if ($idx < count($rows)-1): ?><table class="sheet"><tr><td class="record-break"></td></tr></table><?php endif; ?>
<?php endforeach; ?>

<table class="sheet"><tr><td class="spacer"></td></tr><tr><td class="footer">Dicetak otomatis dari Sistem Pengaduan Online Ciputra SH-1. Dicetak oleh: <?= e($user['nama_lengkap']) ?></td></tr></table>
</body>
</html>
