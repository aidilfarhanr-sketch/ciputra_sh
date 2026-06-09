<?php
require __DIR__ . '/config.php';

$action = $_GET['action'] ?? 'list';
$user = require_role(['admin_sh1']);

function normalize_internal_role(string $role): string {
    return in_array($role, ['qc', 'kontraktor'], true) ? $role : '';
}

if ($action === 'list') {
    $role = clean_text($_GET['role'] ?? '', 30);
    $params = [];
    $where = "u.role IN ('qc','kontraktor')";
    if (in_array($role, ['qc','kontraktor'], true)) {
        $where .= " AND u.role = ?";
        $params[] = $role;
    }
    $stmt = $pdo->prepare("SELECT u.id, u.nama_lengkap, u.username, u.email, u.no_hp, u.role, u.proyek_id, COALESCE(u.status_akun, u.status) status, p.nama_proyek
        FROM users u LEFT JOIN proyek p ON p.id=u.proyek_id
        WHERE {$where}
        ORDER BY u.role, p.nama_proyek, u.nama_lengkap");
    $stmt->execute($params);
    json_out(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($action === 'create') {
    require_rate_limit('admin_create_internal_user', 12, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $nama = clean_text($data['nama_lengkap'] ?? '', 120);
    $username = clean_text($data['username'] ?? '', 80);
    $email = clean_text($data['email'] ?? '', 120);
    $noHp = clean_text($data['no_hp'] ?? '', 40);
    $role = normalize_internal_role(clean_text($data['role'] ?? '', 30));
    $proyekId = (int)($data['proyek_id'] ?? 0);
    $password = (string)($data['password'] ?? '');
    $status = clean_text($data['status'] ?? 'active', 20);
    $status = $status === 'inactive' ? 'inactive' : 'active';

    if ($nama === '' || $username === '' || $role === '' || $proyekId <= 0 || $password === '') {
        json_out(['success' => false, 'message' => 'Nama, username, role QC/Kontraktor, proyek, dan password wajib diisi.'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['success'=>false,'message'=>'Format email tidak valid.'], 422);
    if (strlen($password) < 8) json_out(['success'=>false,'message'=>'Password minimal 8 karakter.'], 422);
    $proyek = get_proyek($proyekId);
    if (!$proyek || $proyek['status'] !== 'active') json_out(['success'=>false,'message'=>'Proyek tidak valid/aktif.'], 422);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=? OR (email IS NOT NULL AND email<>'' AND email=?)");
    $stmt->execute([$username, $email]);
    if ((int)$stmt->fetchColumn() > 0) json_out(['success'=>false,'message'=>'Username/email sudah digunakan.'], 409);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, email, password, no_hp, role, proyek_id, status, status_akun, must_change_password, created_by_admin, created_at, updated_at) VALUES (?, ?, NULLIF(?,''), ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())");
    $stmt->execute([$nama, $username, $email, $hash, $noHp, $role, $proyekId, $status, $status, (int)$user['id']]);
    $id = (int)$pdo->lastInsertId();
    log_activity((int)$user['id'], $user['role'], 'admin membuat akun ' . $role, 'users', $id, null, ['username'=>$username,'proyek_id'=>$proyekId]);
    json_out(['success' => true, 'message' => 'Akun internal berhasil dibuat.', 'id' => $id]);
}

if ($action === 'update') {
    require_rate_limit('admin_update_internal_user', 30, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND role IN ('qc','kontraktor') LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Akun tidak ditemukan atau bukan akun QC/Kontraktor.'], 404);

    $nama = clean_text($data['nama_lengkap'] ?? $old['nama_lengkap'], 120);
    $username = clean_text($data['username'] ?? $old['username'], 80);
    $email = clean_text($data['email'] ?? ($old['email'] ?? ''), 120);
    $noHp = clean_text($data['no_hp'] ?? ($old['no_hp'] ?? ''), 40);
    $role = normalize_internal_role(clean_text($data['role'] ?? $old['role'], 30));
    $proyekId = (int)($data['proyek_id'] ?? $old['proyek_id']);
    $status = clean_text($data['status'] ?? ($old['status_akun'] ?? $old['status'] ?? 'active'), 20);
    $status = $status === 'inactive' ? 'inactive' : 'active';

    if ($nama === '' || $username === '' || $role === '' || $proyekId <= 0) json_out(['success'=>false,'message'=>'Data akun belum lengkap.'], 422);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['success'=>false,'message'=>'Format email tidak valid.'], 422);
    $proyek = get_proyek($proyekId);
    if (!$proyek || $proyek['status'] !== 'active') json_out(['success'=>false,'message'=>'Proyek tidak valid/aktif.'], 422);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id<>? AND (username=? OR (email IS NOT NULL AND email<>'' AND email=?))");
    $stmt->execute([$id, $username, $email]);
    if ((int)$stmt->fetchColumn() > 0) json_out(['success'=>false,'message'=>'Username/email sudah digunakan akun lain.'], 409);

    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap=?, username=?, email=NULLIF(?,''), no_hp=?, role=?, proyek_id=?, status=?, status_akun=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$nama, $username, $email, $noHp, $role, $proyekId, $status, $status, $id]);
    log_activity((int)$user['id'], $user['role'], 'admin edit akun internal', 'users', $id, $old, $data);
    json_out(['success' => true, 'message' => 'Akun berhasil diperbarui.']);
}

if ($action === 'reset_password') {
    require_rate_limit('admin_reset_internal_password', 10, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $password = (string)($data['password'] ?? '');
    if (strlen($password) < 8) json_out(['success'=>false,'message'=>'Password baru minimal 8 karakter.'], 422);
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id=? AND role IN ('qc','kontraktor') LIMIT 1");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) json_out(['success'=>false,'message'=>'Akun tidak ditemukan atau bukan akun QC/Kontraktor.'], 404);
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password=?, must_change_password=1, password_changed_at=NULL, updated_at=NOW() WHERE id=?");
    $stmt->execute([$hash, $id]);
    log_activity((int)$user['id'], $user['role'], 'admin reset password akun internal', 'users', $id);
    json_out(['success' => true, 'message' => 'Password akun berhasil direset.']);
}

json_out(['success' => false, 'message' => 'Action users tidak dikenal.'], 404);
