<?php
require __DIR__ . '/config.php';
$user = require_role(['admin_sh1']);
$action = $_GET['action'] ?? 'list';

function audit_filter_sql(array &$params): string {
    $search = clean_text($_GET['search'] ?? '', 120);
    $where = ['1=1'];
    if ($search !== '') {
        $where[] = '(action LIKE ? OR role LIKE ? OR table_name LIKE ? OR ip_address LIKE ? OR username_attempt LIKE ? OR role_attempt LIKE ? OR status LIKE ? OR risk_level LIKE ? OR notes LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like);
    }
    return implode(' AND ', $where);
}

if ($action === 'list') {
    $params = [];
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    try {
        $sqlWhere = audit_filter_sql($params);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$sqlWhere}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT id, user_id, role, username_attempt, role_attempt, action, table_name, record_id, status, risk_level, notes, failed_attempts, ip_address, user_agent, created_at FROM audit_logs WHERE {$sqlWhere} ORDER BY created_at DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);
    } catch (Throwable $e) {
        $params = [];
        $search = clean_text($_GET['search'] ?? '', 120);
        $where = '1=1';
        if ($search !== '') {
            $where = '(action LIKE ? OR role LIKE ? OR table_name LIKE ? OR ip_address LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$where}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT id, user_id, role, NULL AS username_attempt, NULL AS role_attempt, action, table_name, record_id, NULL AS status, NULL AS risk_level, NULL AS notes, NULL AS failed_attempts, ip_address, user_agent, created_at FROM audit_logs WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);
    }
    json_out(['success'=>true, 'data'=>$stmt->fetchAll(), 'page'=>$page, 'limit'=>$limit, 'total'=>$total]);
}

if ($action === 'delete') {
    require_rate_limit('admin_delete_audit_log', 50, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Log tidak ditemukan.'], 404);
    $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE id=?");
    $stmt->execute([$id]);
    log_activity((int)$user['id'], $user['role'], 'admin hapus audit log', 'audit_logs', $id, $old, null);
    json_out(['success'=>true,'message'=>'Audit log berhasil dihapus.']);
}

if ($action === 'ban') {
    require_rate_limit('admin_ban_access', 30, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $type = clean_text($data['ban_type'] ?? '', 20);
    $value = clean_text($data['ban_value'] ?? '', 180);
    $reason = clean_text($data['reason'] ?? 'Aktivitas login mencurigakan', 255);
    if (!in_array($type, ['ip','user'], true) || $value === '') json_out(['success'=>false,'message'=>'Data ban belum lengkap.'], 422);
    $stmt = $pdo->prepare("SELECT id FROM banned_access WHERE ban_type=? AND ban_value=? AND status='active' LIMIT 1");
    $stmt->execute([$type, $value]);
    if ($stmt->fetch()) json_out(['success'=>true,'message'=>'Data tersebut sudah dibanned aktif.']);
    $stmt = $pdo->prepare("INSERT INTO banned_access (ban_type, ban_value, reason, status, banned_by, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, NOW(), NOW())");
    $stmt->execute([$type, $value, $reason, (int)$user['id']]);
    $id = (int)$pdo->lastInsertId();
    log_activity((int)$user['id'], $user['role'], 'admin ban ' . $type, 'banned_access', $id, null, ['ban_value'=>$value,'reason'=>$reason]);
    json_out(['success'=>true,'message'=>'Ban berhasil diaktifkan.']);
}

if ($action === 'unban') {
    require_rate_limit('admin_unban_access', 30, 900, 'admin:' . (int)$user['id']);
    $data = input_json();
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM banned_access WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) json_out(['success'=>false,'message'=>'Data ban tidak ditemukan.'], 404);
    $stmt = $pdo->prepare("UPDATE banned_access SET status='inactive', updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    log_activity((int)$user['id'], $user['role'], 'admin unban akses', 'banned_access', $id, $old, ['status'=>'inactive']);
    json_out(['success'=>true,'message'=>'Ban berhasil dibuka.']);
}

if ($action === 'banned_list') {
    $stmt = $pdo->query("SELECT b.*, u.nama_lengkap AS banned_by_name FROM banned_access b LEFT JOIN users u ON u.id=b.banned_by ORDER BY b.status ASC, b.created_at DESC, b.id DESC LIMIT 200");
    json_out(['success'=>true,'data'=>$stmt->fetchAll()]);
}

json_out(['success'=>false, 'message'=>'Action logs tidak dikenal.'], 404);
