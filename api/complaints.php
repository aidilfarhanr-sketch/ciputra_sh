<?php
require __DIR__ . '/config.php';
$action = $_GET['action'] ?? '';

function normalize_jenis(string $jenis): string { return $jenis === 'qc' ? 'qc' : 'pelanggan'; }
function split_lines($value): array {
    $lines = preg_split('/\r\n|\r|\n/', (string)$value);
    return array_values(array_filter(array_map(fn($x) => clean_text($x, 255), $lines), fn($x) => $x !== ''));
}
function upload_item_path($item): string { return is_array($item) ? ($item['path'] ?? '') : (string)$item; }
function upload_item_meta($item, string $key) { return is_array($item) ? ($item[$key] ?? null) : null; }

function insert_foto_temuan(PDO $pdo, int $temuanId, array $items, $captions = '', $areas = ''): void {
    $capLines = is_array($captions) ? $captions : split_lines($captions);
    $areaLines = is_array($areas) ? $areas : split_lines($areas);
    $stmt = $pdo->prepare("INSERT INTO foto_temuan (temuan_id, foto_path, original_name, mime_type, size, keterangan_foto, area_kerusakan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    foreach ($items as $i => $item) {
        $path = upload_item_path($item);
        if ($path === '') continue;
        $caption = $capLines[$i] ?? ($capLines[0] ?? 'Foto temuan/kerusakan');
        $area = $areaLines[$i] ?? ($areaLines[0] ?? null);
        $stmt->execute([$temuanId, $path, upload_item_meta($item,'original_name'), upload_item_meta($item,'mime_type'), upload_item_meta($item,'size'), $caption, $area]);
    }
}

function insert_foto_jawaban(PDO $pdo, int $jawabanId, array $items, $captions = ''): void {
    $capLines = is_array($captions) ? $captions : split_lines($captions);
    $stmt = $pdo->prepare("INSERT INTO foto_jawaban (jawaban_id, foto_path, original_name, mime_type, size, keterangan_foto, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    foreach ($items as $i => $item) {
        $path = upload_item_path($item);
        if ($path === '') continue;
        $caption = $capLines[$i] ?? ($capLines[0] ?? 'Foto hasil perbaikan');
        $stmt->execute([$jawabanId, $path, upload_item_meta($item,'original_name'), upload_item_meta($item,'mime_type'), upload_item_meta($item,'size'), $caption]);
    }
}

function add_scope(array $user, array &$where, array &$params, string $alias = 't'): void {
    if ($user['role'] === 'qc') {
        $where[] = "{$alias}.proyek_id = ?";
        $params[] = (int)$user['proyek_id'];
    } elseif ($user['role'] === 'kontraktor') {
        $where[] = "{$alias}.proyek_id = ?";
        $where[] = "{$alias}.kontraktor_id = ?";
        $params[] = (int)$user['proyek_id'];
        $params[] = (int)$user['id'];
    }
}

function fetch_temuan(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT t.*, p.nama_proyek, p.lokasi, uq.nama_lengkap AS qc_name, uk.nama_lengkap AS kontraktor_name, uc.nama_lengkap AS created_by_name
        FROM temuan t
        JOIN proyek p ON p.id=t.proyek_id
        LEFT JOIN users uq ON uq.id=t.qc_id
        LEFT JOIN users uk ON uk.id=t.kontraktor_id
        LEFT JOIN users uc ON uc.id=t.created_by
        WHERE t.id=? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function assert_temuan_access(array $user, array $row): void {
    if ($user['role'] === 'admin_sh1') return;
    if ($user['role'] === 'qc' && (int)$row['proyek_id'] === (int)$user['proyek_id']) return;
    if ($user['role'] === 'kontraktor' && (int)$row['proyek_id'] === (int)$user['proyek_id'] && (int)$row['kontraktor_id'] === (int)$user['id']) return;
    json_out(['success'=>false,'message'=>'Akses data proyek/temuan ini ditolak.'], 403);
}

function sync_legacy_status(string $status, string $jenis): array {
    $validasi = 'menunggu'; $jawaban = 'belum_dijawab';
    if (in_array($status, ['Ditolak Admin'], true)) $validasi = 'ditolak';
    if (in_array($status, ['Diterima Admin','Diteruskan ke QC','Diteruskan ke Kontraktor','Menunggu Jadwal Perbaikan Kontraktor','Jadwal Perbaikan Diajukan Kontraktor','Sedang Dikerjakan','Selesai Dikerjakan Kontraktor','Diperiksa QC','Diteruskan ke Admin','Menunggu Konfirmasi Admin ke Pelanggan','Selesai','Temuan QC Dibuat'], true)) $validasi = 'divalidasi';
    if (in_array($status, ['Diteruskan ke Kontraktor','Menunggu Jadwal Perbaikan Kontraktor','Jadwal Perbaikan Diajukan Kontraktor','Sedang Dikerjakan'], true)) $jawaban = 'proses';
    if (in_array($status, ['Selesai Dikerjakan Kontraktor','Diperiksa QC','Diteruskan ke Admin','Menunggu Konfirmasi Admin ke Pelanggan','Selesai'], true)) $jawaban = 'selesai';
    return [$validasi, $jawaban];
}

if ($action === 'proyek') {
    json_out(['success' => true, 'data' => proyek_list(true)]);
}


if ($action === 'assignees') {
    $user = require_login();
    $role = clean_text($_GET['role'] ?? '', 30);
    $proyekId = (int)($_GET['proyek_id'] ?? 0);
    if (!in_array($role, ['qc','kontraktor'], true)) json_out(['success'=>false,'message'=>'Role assignee tidak valid.'], 422);
    if ($user['role'] !== 'admin_sh1') {
        $proyekId = (int)$user['proyek_id'];
    }
    if ($proyekId <= 0) json_out(['success'=>false,'message'=>'Proyek belum dipilih.'], 422);
    if (!can_access_project($user, $proyekId)) json_out(['success'=>false,'message'=>'Akses proyek ditolak.'], 403);
    $stmt = $pdo->prepare("SELECT id, nama_lengkap, username, role, proyek_id FROM users WHERE role=? AND proyek_id=? AND COALESCE(status_akun,status)='active' ORDER BY nama_lengkap ASC");
    $stmt->execute([$role, $proyekId]);
    json_out(['success'=>true,'data'=>$stmt->fetchAll()]);
}

if ($action === 'stats') {
    $user = require_login();
    $where = ['1=1']; $params = [];
    add_scope($user, $where, $params, 't');
    $whereSql = implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT
        COUNT(*) total,
        COALESCE(SUM(t.jenis_temuan='qc'),0) total_qc,
        COALESCE(SUM(t.jenis_temuan='pelanggan'),0) total_pelanggan,
        COALESCE(SUM(t.status_validasi='menunggu'),0) validasi_menunggu,
        COALESCE(SUM(t.status_jawaban='belum_dijawab'),0) belum_dijawab,
        COALESCE(SUM(t.status_jawaban='proses'),0) proses,
        COALESCE(SUM(t.status_jawaban='selesai'),0) selesai
        FROM temuan t WHERE {$whereSql}");
    $stmt->execute($params);
    $summary = $stmt->fetch() ?: [];

    $projectWhere = [];
    $projectParams = [];
    if ($user['role'] !== 'admin_sh1') { $projectWhere[] = "p.id = ?"; $projectParams[] = (int)$user['proyek_id']; }
    $projectSql = $projectWhere ? 'WHERE ' . implode(' AND ', $projectWhere) : '';
    $joinExtra = $user['role'] === 'kontraktor' ? ' AND t.kontraktor_id=' . (int)$user['id'] : '';
    $stmt = $pdo->prepare("SELECT p.id, p.nama_proyek, p.lokasi, COUNT(t.id) total,
        COALESCE(SUM(t.jenis_temuan='qc'),0) qc,
        COALESCE(SUM(t.jenis_temuan='pelanggan'),0) pelanggan,
        COALESCE(SUM(t.status_jawaban='selesai'),0) selesai
        FROM proyek p LEFT JOIN temuan t ON t.proyek_id=p.id {$joinExtra}
        {$projectSql}
        GROUP BY p.id, p.nama_proyek, p.lokasi ORDER BY p.nama_proyek ASC");
    $stmt->execute($projectParams);
    $proyekStats = $stmt->fetchAll();

    $pendingWhere = ["t.status_jawaban IN ('belum_dijawab','proses')"];
    $pendingParams = [];
    add_scope($user, $pendingWhere, $pendingParams, 't');
    $stmt = $pdo->prepare("SELECT t.id, t.jenis_temuan, t.nomor_kqi, t.nomor_dokumen, t.blok_unit, t.tanggal, t.nama_pelapor, t.no_hp, t.keterangan, t.status_validasi, t.status_jawaban, t.status, t.deadline, t.tanggal_deadline_kontraktor, p.nama_proyek
        FROM temuan t JOIN proyek p ON p.id=t.proyek_id
        WHERE " . implode(' AND ', $pendingWhere) . "
        ORDER BY t.updated_at DESC, t.created_at DESC LIMIT 20");
    $stmt->execute($pendingParams);
    $pendingList = $stmt->fetchAll();
    foreach ($pendingList as &$r) $r['deadline_status'] = deadline_status($r['tanggal_deadline_kontraktor'] ?: $r['deadline']);

    json_out(['success' => true, 'summary' => $summary, 'proyek' => $proyekStats, 'pendingList' => $pendingList]);
}

if ($action === 'create_public') {
    require_public_submit_rate_limit();
    require_rate_limit('public_complaint_submit', 3, 600, client_ip());
    $proyekId = (int)($_POST['proyek_id'] ?? 0);
    $proyek = get_proyek($proyekId);
    if (!$proyek || $proyek['status'] !== 'active') json_out(['success'=>false,'message'=>'Proyek tidak valid atau tidak aktif.'], 422);
    $blok = clean_text($_POST['blok_unit'] ?? '', 80);
    $tanggal = clean_text($_POST['tanggal'] ?? date('Y-m-d'), 20);
    $nama = clean_text($_POST['nama_pelapor'] ?? '', 120);
    $hp = clean_text($_POST['no_hp'] ?? '', 40);
    $email = clean_text($_POST['email'] ?? '', 120);
    $tanggalSerahTerima = clean_text($_POST['tanggal_serah_terima'] ?? '', 20);
    $ket = trim((string)($_POST['keterangan'] ?? ''));
    $ketFoto = $_POST['keterangan_foto'] ?? $ket;
    $area = $_POST['area_kerusakan'] ?? '';
    if ($blok === '' || $nama === '' || $hp === '' || $ket === '') json_out(['success'=>false,'message'=>'Nama, no HP, blok/unit, dan keterangan wajib diisi.'], 422);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['success'=>false,'message'=>'Format email tidak valid.'], 422);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) json_out(['success'=>false,'message'=>'Format tanggal tidak valid.'], 422);
    $paths = safe_upload_many('foto_temuan', 'temuan', true);
    $nomor = generate_nomor_laporan($pdo, $proyekId);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO temuan (jenis_temuan, sumber_temuan, nomor_kqi, nomor_dokumen, proyek_id, blok_unit, tanggal, tanggal_keluhan, tanggal_serah_terima, nama_pelapor, no_hp, email, keterangan, status, status_validasi, status_jawaban, created_by, created_at, updated_at) VALUES ('pelanggan','pelanggan', NULL, ?, ?, ?, ?, ?, NULLIF(?,''), ?, ?, NULLIF(?,''), ?, 'Menunggu Validasi Admin', 'menunggu', 'belum_dijawab', NULL, NOW(), NOW())");
        $stmt->execute([$nomor, $proyekId, $blok, $tanggal, $tanggal, $tanggalSerahTerima, $nama, $hp, $email, $ket]);
        $temuanId = (int)$pdo->lastInsertId();
        insert_foto_temuan($pdo, $temuanId, $paths, $ketFoto, $area);
        $pdo->commit();
        $_SESSION['last_public_submit'] = time();
        log_activity(null, 'pelanggan', 'submit keluhan publik', 'temuan', $temuanId, null, ['nomor_dokumen'=>$nomor]);
        json_out(['success' => true, 'message' => 'Pengaduan berhasil dikirim. Data masuk ke dashboard Admin SH-1 untuk divalidasi.', 'nomor_dokumen' => $nomor]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        log_error_app('CREATE_PUBLIC_FAILED ' . $e->getMessage(), ['action'=>'create_public']);
        json_out(['success' => false, 'message' => 'Gagal menyimpan pengaduan.'], 500);
    }
}

if ($action === 'create') {
    $user = require_role(['qc']);
    require_rate_limit('qc_create_temuan', 30, 900, 'user:' . (int)$user['id']);
    $jenis = normalize_jenis($_POST['jenis_temuan'] ?? 'qc');
    if ($jenis !== 'qc') json_out(['success'=>false,'message'=>'Temuan pelanggan hanya boleh berasal dari form publik dan validasi Admin.'], 403);
    $proyekId = (int)($_POST['proyek_id'] ?? 0);
    if (!can_access_project($user, $proyekId)) json_out(['success'=>false,'message'=>'QC hanya bisa membuat temuan untuk proyeknya sendiri.'], 403);
    $proyek = get_proyek($proyekId);
    if (!$proyek || $proyek['status'] !== 'active') json_out(['success'=>false,'message'=>'Proyek tidak valid.'], 422);
    $blok = clean_text($_POST['blok_unit'] ?? '', 80);
    $tanggal = clean_text($_POST['tanggal'] ?? date('Y-m-d'), 20);
    $nama = clean_text($_POST['nama_pelapor'] ?? ($user['nama_lengkap'] ?? 'QC'), 120);
    $hp = clean_text($_POST['no_hp'] ?? '', 40);
    $ket = trim((string)($_POST['keterangan'] ?? ''));
    if ($blok === '' || $tanggal === '' || $ket === '') json_out(['success'=>false,'message'=>'Blok/unit, tanggal, dan keterangan wajib diisi.'], 422);
    $nomorKqi = clean_text($_POST['nomor_kqi'] ?? '', 80);
    if ($nomorKqi === '') $nomorKqi = generate_nomor_kqi($pdo, $proyekId);
    $nomorDokumen = clean_text($_POST['nomor_dokumen'] ?? '', 80);
    $paths = safe_upload_many('foto_temuan', 'temuan', false);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO temuan (jenis_temuan, sumber_temuan, nomor_kqi, nomor_dokumen, proyek_id, blok_unit, tanggal, tanggal_keluhan, nama_pelapor, no_hp, keterangan, dampak, fakta, penyebab_utama, antisipasi, deadline, status, status_validasi, status_jawaban, created_by, qc_id, created_at, updated_at) VALUES ('qc','qc', ?, NULLIF(?,''), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?,''), 'Temuan QC Dibuat', 'divalidasi', 'belum_dijawab', ?, ?, NOW(), NOW())");
        $stmt->execute([
            $nomorKqi, $nomorDokumen, $proyekId, $blok, $tanggal, $tanggal, $nama, $hp, $ket,
            trim((string)($_POST['dampak'] ?? '')), trim((string)($_POST['fakta'] ?? '')), trim((string)($_POST['penyebab_utama'] ?? '')), trim((string)($_POST['antisipasi'] ?? '')),
            clean_text($_POST['deadline'] ?? '', 20), (int)$user['id'], (int)$user['id']
        ]);
        $temuanId = (int)$pdo->lastInsertId();
        if ($paths) insert_foto_temuan($pdo, $temuanId, $paths, $_POST['keterangan_foto'] ?? $_POST['keterangan'] ?? '', $_POST['area_kerusakan'] ?? '');
        $pdo->commit();
        log_activity((int)$user['id'], $user['role'], 'QC membuat temuan', 'temuan', $temuanId, null, ['nomor_kqi'=>$nomorKqi]);
        json_out(['success'=>true,'message'=>'Temuan QC berhasil dibuat.','id'=>$temuanId,'nomor_kqi'=>$nomorKqi]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        log_error_app('CREATE_QC_FAILED ' . $e->getMessage(), ['action'=>'create']);
        json_out(['success'=>false,'message'=>'Gagal menyimpan temuan.'], 500);
    }
}

if ($action === 'list') {
    $user = require_login();
    $jenis = $_GET['jenis'] ?? '';
    $proyekId = (int)($_GET['proyek_id'] ?? 0);
    $statusJawaban = clean_text($_GET['status_jawaban'] ?? '', 30);
    $search = clean_text($_GET['search'] ?? '', 120);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    $where = ['1=1']; $params = [];
    if (in_array($jenis, ['qc','pelanggan'], true)) { $where[] = 't.jenis_temuan = ?'; $params[] = $jenis; }
    if ($proyekId > 0) { $where[] = 't.proyek_id = ?'; $params[] = $proyekId; }
    if (in_array($statusJawaban, ['belum_dijawab','proses','selesai'], true)) { $where[] = 't.status_jawaban = ?'; $params[] = $statusJawaban; }
    if ($search !== '') {
        $where[] = "(t.nomor_kqi LIKE ? OR t.nomor_dokumen LIKE ? OR t.blok_unit LIKE ? OR t.nama_pelapor LIKE ? OR t.no_hp LIKE ? OR t.keterangan LIKE ? OR p.nama_proyek LIKE ?)";
        $like = '%' . $search . '%'; array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }
    add_scope($user, $where, $params, 't');
    $countSql = "SELECT COUNT(*) FROM temuan t JOIN proyek p ON p.id=t.proyek_id WHERE " . implode(' AND ', $where);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $sql = "SELECT t.*, p.nama_proyek, p.lokasi,
        uq.nama_lengkap AS qc_name, uk.nama_lengkap AS kontraktor_name,
        (SELECT COUNT(*) FROM foto_temuan ft WHERE ft.temuan_id=t.id) foto_count,
        (SELECT COUNT(*) FROM foto_jawaban fj JOIN jawaban jj ON jj.id=fj.jawaban_id WHERE jj.temuan_id=t.id) foto_jawaban_count,
        j.penyebab AS jawaban_penyebab, j.antisipasi AS jawaban_antisipasi, j.keterangan_perbaikan, j.tanggal_selesai, j.status_perbaikan, j.ttd_admin, j.ttd_proyek, j.ttd_pemilik_rumah
        FROM temuan t
        JOIN proyek p ON p.id=t.proyek_id
        LEFT JOIN users uq ON uq.id=t.qc_id
        LEFT JOIN users uk ON uk.id=t.kontraktor_id
        LEFT JOIN jawaban j ON j.temuan_id=t.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.updated_at DESC, t.created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['deadline_status'] = deadline_status($r['tanggal_deadline_kontraktor'] ?: $r['deadline']);
    json_out(['success' => true, 'data' => $rows, 'page' => $page, 'limit' => $limit, 'total' => $totalRows]);
}

if ($action === 'detail') {
    $user = require_login();
    $id = (int)($_GET['id'] ?? 0);
    $row = fetch_temuan($pdo, $id);
    if (!$row) json_out(['success'=>false,'message'=>'Data tidak ditemukan.'], 404);
    assert_temuan_access($user, $row);
    $row['deadline_status'] = deadline_status($row['tanggal_deadline_kontraktor'] ?: $row['deadline']);
    $stmt = $pdo->prepare("SELECT * FROM foto_temuan WHERE temuan_id=? ORDER BY id ASC"); $stmt->execute([$id]); $photos = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM jawaban WHERE temuan_id=? LIMIT 1"); $stmt->execute([$id]); $jawaban = $stmt->fetch() ?: null;
    $answerPhotos = [];
    if ($jawaban) { $stmt = $pdo->prepare("SELECT * FROM foto_jawaban WHERE jawaban_id=? ORDER BY id ASC"); $stmt->execute([(int)$jawaban['id']]); $answerPhotos = $stmt->fetchAll(); }
    json_out(['success'=>true,'data'=>$row,'photos'=>$photos,'jawaban'=>$jawaban,'answerPhotos'=>$answerPhotos]);
}

if ($action === 'update') {
    $user = require_login();
    require_rate_limit('update_temuan', 40, 900, 'user:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $old = fetch_temuan($pdo, $id);
    if (!$old) json_out(['success'=>false,'message'=>'Data tidak ditemukan.'], 404);
    assert_temuan_access($user, $old);
    if ($user['role'] === 'kontraktor') json_out(['success'=>false,'message'=>'Kontraktor tidak boleh mengedit data temuan.'], 403);
    if ($user['role'] === 'admin_sh1' && $old['jenis_temuan'] === 'qc') {
        json_out(['success'=>false,'message'=>'Admin hanya memonitor temuan QC. Temuan QC tidak boleh diedit admin.'], 403);
    }
    if ($user['role'] === 'qc' && $old['jenis_temuan'] !== 'qc') {
        json_out(['success'=>false,'message'=>'QC tidak boleh mengedit keluhan pelanggan dari admin. Gunakan aksi teruskan/periksa.'], 403);
    }
    $proyekId = (int)($data['proyek_id'] ?? $old['proyek_id']);
    if (!can_access_project($user, $proyekId)) json_out(['success'=>false,'message'=>'Tidak boleh memindahkan ke proyek lain.'], 403);
    $statusValidasi = clean_text($data['status_validasi'] ?? $old['status_validasi'], 30);
    $statusJawaban = clean_text($data['status_jawaban'] ?? $old['status_jawaban'], 30);
    $allowedValidasi = ['menunggu','divalidasi','ditolak'];
    $allowedJawaban = ['belum_dijawab','proses','selesai'];
    if (!in_array($statusValidasi, $allowedValidasi, true)) $statusValidasi = $old['status_validasi'];
    if (!in_array($statusJawaban, $allowedJawaban, true)) $statusJawaban = $old['status_jawaban'];
    $status = $old['status'];
    if ($old['jenis_temuan'] === 'pelanggan' && $statusValidasi === 'ditolak') $status = 'Ditolak Admin';
    elseif ($old['jenis_temuan'] === 'pelanggan' && $statusValidasi === 'divalidasi' && $old['status'] === 'Menunggu Validasi Admin') $status = 'Diterima Admin';
    elseif ($old['jenis_temuan'] === 'qc' && $statusJawaban === 'selesai') $status = 'Selesai';
    $stmt = $pdo->prepare("UPDATE temuan SET nomor_kqi=?, nomor_dokumen=?, proyek_id=?, blok_unit=?, tanggal=?, tanggal_keluhan=?, nama_pelapor=?, no_hp=?, email=NULLIF(?,''), keterangan=?, dampak=?, fakta=?, penyebab_utama=?, antisipasi=?, deadline=NULLIF(?,''), status=?, status_validasi=?, status_jawaban=?, alasan_penolakan=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([
        $old['jenis_temuan'] === 'qc' ? clean_text($data['nomor_kqi'] ?? $old['nomor_kqi'], 80) : null,
        clean_text($data['nomor_dokumen'] ?? $old['nomor_dokumen'], 80) ?: null,
        $proyekId, clean_text($data['blok_unit'] ?? $old['blok_unit'], 80), clean_text($data['tanggal'] ?? $old['tanggal'], 20), clean_text($data['tanggal'] ?? $old['tanggal_keluhan'], 20),
        clean_text($data['nama_pelapor'] ?? $old['nama_pelapor'], 120), clean_text($data['no_hp'] ?? $old['no_hp'], 40), clean_text($data['email'] ?? $old['email'], 120), trim((string)($data['keterangan'] ?? $old['keterangan'])),
        trim((string)($data['dampak'] ?? $old['dampak'])), trim((string)($data['fakta'] ?? $old['fakta'])), trim((string)($data['penyebab_utama'] ?? $old['penyebab_utama'])), trim((string)($data['antisipasi'] ?? $old['antisipasi'])), clean_text($data['deadline'] ?? $old['deadline'], 20),
        $status, $statusValidasi, $statusJawaban, clean_text($data['alasan_penolakan'] ?? $old['alasan_penolakan'], 255), $id
    ]);
    log_activity((int)$user['id'], $user['role'], 'update temuan', 'temuan', $id, $old, $data);
    json_out(['success'=>true,'message'=>'Data berhasil diperbarui.']);
}

if ($action === 'workflow') {
    $user = require_login();
    require_rate_limit('workflow_temuan', 60, 900, 'user:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $op = clean_text($data['op'] ?? '', 60);
    $row = fetch_temuan($pdo, $id);
    if (!$row) json_out(['success'=>false,'message'=>'Data tidak ditemukan.'], 404);
    assert_temuan_access($user, $row);
    $newStatus = $row['status']; $updates = [];
    $params = [];
    if ($op === 'reject_customer') {
        require_role(['admin_sh1']);
        if ($row['jenis_temuan'] !== 'pelanggan') json_out(['success'=>false,'message'=>'Hanya keluhan pelanggan yang dapat ditolak admin.'], 403);
        $newStatus = 'Ditolak Admin';
        $updates = ["status=?", "status_validasi='ditolak'", "status_jawaban='belum_dijawab'", "alasan_penolakan=?"];
        $params = [$newStatus, clean_text($data['alasan_penolakan'] ?? 'Masa garansi/persyaratan belum terpenuhi.', 255)];
    } elseif ($op === 'forward_to_qc') {
        require_role(['admin_sh1']);
        if ($row['jenis_temuan'] !== 'pelanggan') json_out(['success'=>false,'message'=>'Hanya keluhan pelanggan yang diteruskan admin ke QC.'], 403);
        $qcId = (int)($data['qc_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='qc' AND proyek_id=? AND COALESCE(status_akun,status)='active' LIMIT 1");
        $stmt->execute([$qcId, (int)$row['proyek_id']]);
        if (!$stmt->fetch()) json_out(['success'=>false,'message'=>'QC tidak valid untuk proyek ini.'], 422);
        $newStatus = 'Diteruskan ke QC';
        $updates = ["status=?", "status_validasi='divalidasi'", "qc_id=?", "diteruskan_ke_qc_oleh=?", "tanggal_diterima_admin=COALESCE(tanggal_diterima_admin,CURDATE())", "tanggal_diteruskan_qc=CURDATE()"];
        $params = [$newStatus, $qcId, (int)$user['id']];
    } elseif ($op === 'forward_to_contractor') {
        require_role(['qc']);
        if ((int)$row['proyek_id'] !== (int)$user['proyek_id']) json_out(['success'=>false,'message'=>'QC hanya bisa meneruskan data proyeknya sendiri.'], 403);
        $kontraktorId = (int)($data['kontraktor_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='kontraktor' AND proyek_id=? AND COALESCE(status_akun,status)='active' LIMIT 1");
        $stmt->execute([$kontraktorId, (int)$row['proyek_id']]);
        if (!$stmt->fetch()) json_out(['success'=>false,'message'=>'Kontraktor tidak valid untuk proyek ini.'], 422);
        $newStatus = 'Diteruskan ke Kontraktor';
        $baseDate = $row['jenis_temuan'] === 'pelanggan' ? ($row['tanggal_diterima_admin'] ?: date('Y-m-d')) : date('Y-m-d');
        $deadline = date('Y-m-d', strtotime($baseDate . ' +5 days'));
        $updates = ["status=?", "status_validasi='divalidasi'", "status_jawaban='proses'", "kontraktor_id=?", "diteruskan_ke_kontraktor_oleh=?", "tanggal_diteruskan_kontraktor=CURDATE()", "tanggal_deadline_kontraktor=?", "deadline=?"];
        $params = [$newStatus, $kontraktorId, (int)$user['id'], $deadline, $deadline];
    } elseif ($op === 'qc_approve') {
        require_role(['qc']);
        if ((int)$row['proyek_id'] !== (int)$user['proyek_id']) json_out(['success'=>false,'message'=>'QC hanya bisa memeriksa proyeknya sendiri.'], 403);
        $newStatus = $row['jenis_temuan'] === 'pelanggan' ? 'Menunggu Konfirmasi Admin ke Pelanggan' : 'Selesai';
        $updates = ["status=?", "status_jawaban='selesai'", "tanggal_diteruskan_admin=?"];
        $params = [$newStatus, date('Y-m-d')];
    } elseif ($op === 'admin_confirm_done') {
        require_role(['admin_sh1']);
        if ($row['jenis_temuan'] !== 'pelanggan') json_out(['success'=>false,'message'=>'Konfirmasi pelanggan hanya untuk keluhan pelanggan.'], 403);
        $newStatus = 'Selesai';
        $updates = ["status=?", "status_jawaban='selesai'"];
        $params = [$newStatus];
    } else {
        json_out(['success'=>false,'message'=>'Aksi workflow tidak dikenal.'], 404);
    }
    $sql = "UPDATE temuan SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE id=?";
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    log_activity((int)$user['id'], $user['role'], 'workflow: ' . $op, 'temuan', $id, $row, ['status'=>$newStatus]);
    json_out(['success'=>true,'message'=>'Alur kerja berhasil diperbarui.','status'=>$newStatus]);
}

if ($action === 'save_answer') {
    $user = require_role(['kontraktor']);
    require_rate_limit('kontraktor_save_answer', 30, 900, 'user:' . (int)$user['id']);
    $temuanId = (int)($_POST['temuan_id'] ?? 0);
    $temuan = fetch_temuan($pdo, $temuanId);
    if (!$temuan) json_out(['success'=>false,'message'=>'Temuan tidak ditemukan.'], 404);
    assert_temuan_access($user, $temuan);
    $penyebab = trim((string)($_POST['penyebab'] ?? ''));
    $antisipasi = trim((string)($_POST['antisipasi'] ?? ''));
    $ket = trim((string)($_POST['keterangan_perbaikan'] ?? $_POST['jawaban_kontraktor'] ?? ''));
    $tglRencana = clean_text($_POST['tanggal_rencana_perbaikan'] ?? '', 20);
    $jamRencana = clean_text($_POST['jam_rencana_perbaikan'] ?? '', 20);
    $tanggalSelesai = clean_text($_POST['tanggal_selesai'] ?? '', 20);
    $status = clean_text($_POST['status_perbaikan'] ?? 'proses', 30);
    if (!in_array($status, ['belum_selesai','proses','selesai'], true)) $status = 'proses';
    if ($ket === '' && $status === 'selesai') json_out(['success'=>false,'message'=>'Jawaban/hasil perbaikan wajib diisi saat status selesai.'], 422);
    $paths = safe_upload_many('foto_jawaban', 'jawaban', false);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM jawaban WHERE temuan_id=? LIMIT 1"); $stmt->execute([$temuanId]); $existing = $stmt->fetch();
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE jawaban SET kontraktor_id=?, penyebab=?, antisipasi=?, keterangan_perbaikan=?, jawaban_kontraktor=?, tanggal_rencana_perbaikan=NULLIF(?,''), jam_rencana_perbaikan=NULLIF(?,''), tanggal_selesai=NULLIF(?,''), status_perbaikan=?, status_jawaban=?, ttd_admin=?, ttd_proyek=?, ttd_pemilik_rumah=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([(int)$user['id'], $penyebab, $antisipasi, $ket, $ket, $tglRencana, $jamRencana, $tanggalSelesai, $status, $status, clean_text($_POST['ttd_admin'] ?? '', 120), clean_text($_POST['ttd_proyek'] ?? '', 120), clean_text($_POST['ttd_pemilik_rumah'] ?? '', 120), (int)$existing['id']]);
            $jawabanId = (int)$existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO jawaban (temuan_id, kontraktor_id, penyebab, antisipasi, keterangan_perbaikan, jawaban_kontraktor, tanggal_rencana_perbaikan, jam_rencana_perbaikan, tanggal_selesai, status_perbaikan, status_jawaban, ttd_admin, ttd_proyek, ttd_pemilik_rumah, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$temuanId, (int)$user['id'], $penyebab, $antisipasi, $ket, $ket, $tglRencana, $jamRencana, $tanggalSelesai, $status, $status, clean_text($_POST['ttd_admin'] ?? '', 120), clean_text($_POST['ttd_proyek'] ?? '', 120), clean_text($_POST['ttd_pemilik_rumah'] ?? '', 120), (int)$user['id']]);
            $jawabanId = (int)$pdo->lastInsertId();
        }
        if ($paths) insert_foto_jawaban($pdo, $jawabanId, $paths, $_POST['keterangan_foto'] ?? '');
        $newTemuanStatus = $status === 'selesai' ? 'Selesai Dikerjakan Kontraktor' : ($tglRencana !== '' ? 'Jadwal Perbaikan Diajukan Kontraktor' : 'Sedang Dikerjakan');
        $legacy = $status === 'selesai' ? 'selesai' : 'proses';
        $stmt = $pdo->prepare("UPDATE temuan SET status=?, status_jawaban=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$newTemuanStatus, $legacy, $temuanId]);
        $pdo->commit();
        log_activity((int)$user['id'], $user['role'], 'kontraktor mengisi jawaban', 'jawaban', $jawabanId, $existing ?: null, ['status'=>$status]);
        json_out(['success'=>true,'message'=>'Jawaban/perbaikan berhasil disimpan.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        log_error_app('SAVE_ANSWER_FAILED ' . $e->getMessage(), ['action'=>'save_answer']);
        json_out(['success'=>false,'message'=>'Gagal menyimpan jawaban.'], 500);
    }
}

function excel_cell(array $row, string $key, $default = '') {
    $lower = strtolower($key);
    foreach ($row as $k => $v) if (strtolower((string)$k) === $lower) return $v;
    return $default;
}
function normalize_status_value($value, array $allowed, string $fallback): string {
    $value = strtolower(trim((string)$value));
    $aliases = ['pending'=>'belum_dijawab','validasi'=>'divalidasi','validated'=>'divalidasi','process'=>'proses','done'=>'selesai','completed'=>'selesai'];
    $value = $aliases[$value] ?? $value;
    return in_array($value, $allowed, true) ? $value : $fallback;
}
function resolve_proyek_for_excel(PDO $pdo, array $row, int $fallback): int {
    $proyekId = (int)excel_cell($row, 'proyek_id', 0);
    if ($proyekId > 0) { $stmt = $pdo->prepare("SELECT id FROM proyek WHERE id=? LIMIT 1"); $stmt->execute([$proyekId]); if ($stmt->fetchColumn()) return $proyekId; }
    $nama = clean_text(excel_cell($row, 'nama_proyek', ''), 150);
    if ($nama !== '') { $stmt = $pdo->prepare("SELECT id FROM proyek WHERE nama_proyek = ? LIMIT 1"); $stmt->execute([$nama]); $found = $stmt->fetchColumn(); if ($found) return (int)$found; }
    return $fallback;
}

if ($action === 'bulk_update') {
    $user = require_login();
    require_rate_limit('bulk_import_excel', 5, 900, 'user:' . (int)$user['id']);
    if ($user['role'] === 'kontraktor') json_out(['success'=>false,'message'=>'Kontraktor tidak boleh import/edit massal data temuan.'], 403);
    $payload = input_json(); $mode = clean_text($payload['mode'] ?? 'temuan', 20); $rows = $payload['rows'] ?? [];
    if (!is_array($rows) || count($rows) === 0) json_out(['success'=>false,'message'=>'Data Excel kosong atau tidak valid.'], 422);
    if (count($rows) > 1000) json_out(['success'=>false,'message'=>'Maksimal import 1000 baris dalam sekali proses.'], 422);
    $updated = 0; $skipped = 0; $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            if (!is_array($row)) { $skipped++; continue; }
            $id = (int)excel_cell($row, 'id', excel_cell($row, 'ID', 0)); if ($id <= 0) { $skipped++; continue; }
            $old = fetch_temuan($pdo, $id); if (!$old) { $skipped++; continue; }
            if ($user['role'] === 'qc' && ((int)$old['proyek_id'] !== (int)$user['proyek_id'] || $old['jenis_temuan'] !== 'qc')) { $skipped++; continue; }
            if ($mode === 'jawaban') { $skipped++; continue; }
            $jenis = $old['jenis_temuan'];
            $nomorKqi = $jenis === 'qc' ? clean_text(excel_cell($row, 'nomor_kqi', $old['nomor_kqi']), 80) : null;
            $nomorDokumen = clean_text(excel_cell($row, 'nomor_dokumen', $old['nomor_dokumen']), 80);
            $proyekId = resolve_proyek_for_excel($pdo, $row, (int)$old['proyek_id']);
            if (!can_access_project($user, $proyekId)) $proyekId = (int)$old['proyek_id'];
            $statusValidasi = normalize_status_value(excel_cell($row, 'status_validasi', $old['status_validasi']), ['menunggu','divalidasi','ditolak'], $old['status_validasi']);
            $statusJawaban = normalize_status_value(excel_cell($row, 'status_jawaban', $old['status_jawaban']), ['belum_dijawab','proses','selesai'], $old['status_jawaban']);
            $stmt = $pdo->prepare("UPDATE temuan SET nomor_kqi=?, nomor_dokumen=?, proyek_id=?, blok_unit=?, tanggal=?, nama_pelapor=?, no_hp=?, keterangan=?, dampak=?, fakta=?, penyebab_utama=?, antisipasi=?, deadline=NULLIF(?,''), status_validasi=?, status_jawaban=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nomorKqi, $nomorDokumen ?: null, $proyekId, clean_text(excel_cell($row,'blok_unit',$old['blok_unit']),80), clean_text(excel_cell($row,'tanggal',$old['tanggal']),20), clean_text(excel_cell($row,'nama_pelapor',$old['nama_pelapor']),120), clean_text(excel_cell($row,'no_hp',$old['no_hp']),40), trim((string)excel_cell($row,'keterangan',$old['keterangan'])), trim((string)excel_cell($row,'dampak',$old['dampak'])), trim((string)excel_cell($row,'fakta',$old['fakta'])), trim((string)excel_cell($row,'penyebab_utama',$old['penyebab_utama'])), trim((string)excel_cell($row,'antisipasi',$old['antisipasi'])), clean_text(excel_cell($row,'deadline',$old['deadline']),20), $statusValidasi, $statusJawaban, $id]);
            $updated++;
        }
        $pdo->commit(); log_activity((int)$user['id'], $user['role'], 'import excel temuan', 'temuan', null, null, ['updated'=>$updated,'skipped'=>$skipped]);
        json_out(['success'=>true,'message'=>'Import Excel berhasil diproses.','updated'=>$updated,'skipped'=>$skipped]);
    } catch (Throwable $e) { $pdo->rollBack(); log_error_app('BULK_IMPORT_FAILED ' . $e->getMessage(), ['action'=>'bulk_update']); json_out(['success'=>false,'message'=>'Gagal import Excel.'], 500); }
}

if ($action === 'delete') {
    $user = require_role(['admin_sh1']);
    require_rate_limit('admin_delete_temuan', 20, 900, 'admin:' . (int)$user['id']);
    $data = input_json(); $id = (int)($data['id'] ?? 0);
    $old = fetch_temuan($pdo, $id);
    $stmt = $pdo->prepare("DELETE FROM temuan WHERE id=?"); $stmt->execute([$id]);
    log_activity((int)$user['id'], $user['role'], 'hapus temuan', 'temuan', $id, $old, null);
    json_out(['success'=>true,'message'=>'Data temuan berhasil dihapus.']);
}

json_out(['success' => false, 'message' => 'Action tidak dikenal.'], 404);
