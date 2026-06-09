<?php
require __DIR__ . '/config.php';
$user = require_role(['admin_sh1']);
$action = $_GET['action'] ?? 'list';

function normalize_project_code(string $value, string $name = ''): string {
    $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));
    if ($value === '') $value = project_code($name ?: 'PRJ');
    return substr($value, 0, 12);
}

function project_payload(): array {
    $data = input_json();
    $nama = clean_text($data['nama_proyek'] ?? '', 150);
    $kode = normalize_project_code((string)($data['kode_proyek'] ?? ''), $nama);
    $lokasi = clean_text($data['lokasi'] ?? ($data['site_area'] ?? ''), 150);
    $siteArea = clean_text($data['site_area'] ?? $lokasi, 150);
    $status = clean_text($data['status'] ?? 'active', 20);
    $status = $status === 'inactive' ? 'inactive' : 'active';
    if ($nama === '') json_out(['success'=>false,'message'=>'Nama proyek wajib diisi.'], 422);
    return compact('nama','kode','lokasi','siteArea','status');
}

if ($action === 'list') {
    $search = clean_text($_GET['search'] ?? '', 120);
    $where = '1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (nama_proyek LIKE ? OR kode_proyek LIKE ? OR lokasi LIKE ? OR site_area LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    try {
        $stmt = $pdo->prepare("SELECT p.*, COUNT(t.id) used_count FROM proyek p LEFT JOIN temuan t ON t.proyek_id=p.id WHERE {$where} GROUP BY p.id ORDER BY p.status ASC, p.nama_proyek ASC");
        $stmt->execute($params);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare("SELECT p.id, NULL AS kode_proyek, p.nama_proyek, p.lokasi, p.lokasi AS site_area, p.status, p.created_at, p.updated_at, COUNT(t.id) used_count FROM proyek p LEFT JOIN temuan t ON t.proyek_id=p.id WHERE 1=1 GROUP BY p.id ORDER BY p.status ASC, p.nama_proyek ASC");
        $stmt->execute();
    }
    json_out(['success'=>true,'data'=>$stmt->fetchAll()]);
}

if ($action === 'create') {
    require_rate_limit('admin_create_project', 20, 900, 'admin:' . (int)$user['id']);
    $d = project_payload();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE nama_proyek=? OR kode_proyek=?");
    $stmt->execute([$d['nama'], $d['kode']]);
    if ((int)$stmt->fetchColumn() > 0) json_out(['success'=>false,'message'=>'Nama proyek atau kode proyek sudah digunakan.'], 409);
    $stmt = $pdo->prepare("INSERT INTO proyek (kode_proyek, nama_proyek, lokasi, site_area, status, created_at, updated_at) VALUES (?, ?, NULLIF(?,''), NULLIF(?,''), ?, NOW(), NOW())");
    $stmt->execute([$d['kode'], $d['nama'], $d['lokasi'], $d['siteArea'], $d['status']]);
    $id = (int)$pdo->lastInsertId();
    log_activity((int)$user['id'], $user['role'], 'admin tambah proyek', 'proyek', $id, null, $d);
    json_out(['success'=>true,'message'=>'Proyek berhasil ditambahkan.','id'=>$id]);
}

if ($action === 'update') {
    require_rate_limit('admin_update_project', 30, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM proyek WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Proyek tidak ditemukan.'], 404);
    $d = project_payload();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE id<>? AND (nama_proyek=? OR kode_proyek=?)");
    $stmt->execute([$id, $d['nama'], $d['kode']]);
    if ((int)$stmt->fetchColumn() > 0) json_out(['success'=>false,'message'=>'Nama proyek atau kode proyek sudah digunakan proyek lain.'], 409);
    $stmt = $pdo->prepare("UPDATE proyek SET kode_proyek=?, nama_proyek=?, lokasi=NULLIF(?,''), site_area=NULLIF(?,''), status=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$d['kode'], $d['nama'], $d['lokasi'], $d['siteArea'], $d['status'], $id]);
    log_activity((int)$user['id'], $user['role'], 'admin edit proyek', 'proyek', $id, $old, $d);
    json_out(['success'=>true,'message'=>'Proyek berhasil diperbarui.']);
}

if ($action === 'toggle') {
    require_rate_limit('admin_toggle_project', 40, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $status = clean_text($data['status'] ?? '', 20);
    $status = $status === 'active' ? 'active' : 'inactive';
    $stmt = $pdo->prepare("SELECT * FROM proyek WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Proyek tidak ditemukan.'], 404);
    $stmt = $pdo->prepare("UPDATE proyek SET status=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$status, $id]);
    log_activity((int)$user['id'], $user['role'], 'admin ubah status proyek', 'proyek', $id, $old, ['status'=>$status]);
    json_out(['success'=>true,'message'=>'Status proyek berhasil diubah.']);
}

if ($action === 'delete') {
    require_rate_limit('admin_delete_project', 10, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT p.*, COUNT(t.id) used_count FROM proyek p LEFT JOIN temuan t ON t.proyek_id=p.id WHERE p.id=? GROUP BY p.id LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Proyek tidak ditemukan.'], 404);
    if ((int)$old['used_count'] > 0) {
        $stmt = $pdo->prepare("UPDATE proyek SET status='inactive', updated_at=NOW() WHERE id=?");
        $stmt->execute([$id]);
        log_activity((int)$user['id'], $user['role'], 'admin nonaktifkan proyek karena sudah dipakai data', 'proyek', $id, $old, ['status'=>'inactive']);
        json_out(['success'=>true,'message'=>'Proyek sudah dipakai data lama, jadi dinonaktifkan agar data aman.']);
    }
    $stmt = $pdo->prepare("DELETE FROM proyek WHERE id=?");
    $stmt->execute([$id]);
    log_activity((int)$user['id'], $user['role'], 'admin hapus proyek kosong', 'proyek', $id, $old, null);
    json_out(['success'=>true,'message'=>'Proyek berhasil dihapus karena belum dipakai data.']);
}

json_out(['success'=>false,'message'=>'Action proyek tidak dikenal.'], 404);
