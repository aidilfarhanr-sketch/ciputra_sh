<?php
// =====================================================
// CIPUTRA SH-1 - CONFIG DATABASE XAMPP
// Revisi alur sesuai Word: Admin SH-1, QC, Kontraktor
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

// Environment production-readiness. Default aman: error teknis tidak ditampilkan ke user.
define('CIPUTRA_ENV', getenv('CIPUTRA_ENV') ?: 'local');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Load konfigurasi lokal/production jika tersedia.
// Gunakan config.example.php sebagai template, lalu copy ke config.local.php atau config.production.php.
$localConfig = __DIR__ . '/config.local.php';
$productionConfig = __DIR__ . '/config.production.php';
if (CIPUTRA_ENV === 'production' && file_exists($productionConfig)) {
    require $productionConfig;
} elseif (file_exists($localConfig)) {
    require $localConfig;
}

// Security headers dasar. Tetap aman untuk XAMPP lokal dan hosting.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: blob:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

$db_host = defined('CIPUTRA_DB_HOST') ? CIPUTRA_DB_HOST : (getenv('CIPUTRA_DB_HOST') ?: '127.0.0.1');
$db_port = defined('CIPUTRA_DB_PORT') ? CIPUTRA_DB_PORT : (getenv('CIPUTRA_DB_PORT') ?: '3306');
$db_name = defined('CIPUTRA_DB_NAME') ? CIPUTRA_DB_NAME : (getenv('CIPUTRA_DB_NAME') ?: 'ciputra_sh');
$db_user = defined('CIPUTRA_DB_USER') ? CIPUTRA_DB_USER : (getenv('CIPUTRA_DB_USER') ?: 'root');
$db_pass = defined('CIPUTRA_DB_PASS') ? CIPUTRA_DB_PASS : (getenv('CIPUTRA_DB_PASS') ?: '');

try {
    $pdo = new PDO(
        "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    @file_put_contents($logDir . '/app.log', '[' . date('Y-m-d H:i:s') . '] DB_CONNECTION ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal. Pastikan database ciputra_sh sudah di-import dan konfigurasi api/config.php benar.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function input_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function request_csrf_token(): string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $value) {
        if (strtolower((string)$key) === 'x-csrf-token') return (string)$value;
    }
    if (!empty($_POST['_csrf_token'])) return (string)$_POST['_csrf_token'];
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) && isset($data['_csrf_token']) ? (string)$data['_csrf_token'] : '';
}

function enforce_csrf_for_state_changing_requests(): void {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;
    $token = request_csrf_token();
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        json_out(['success' => false, 'message' => 'Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.'], 419);
    }
}

enforce_csrf_for_state_changing_requests();

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function client_agent(): string {
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function log_error_app(string $message, array $context = []): void {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    $line = '[' . date('Y-m-d H:i:s') . '] ERROR ' . $message;
    if ($context) $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $line .= PHP_EOL;
    @file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
}

set_exception_handler(function(Throwable $e): void {
    log_error_app($e->getMessage(), [
        'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'user_id' => $_SESSION['user']['id'] ?? null,
        'role' => $_SESSION['user']['role'] ?? null,
        'ip' => client_ip(),
        'user_agent' => client_agent(),
    ]);
    if (!headers_sent()) {
        json_out(['success' => false, 'message' => 'Terjadi kesalahan server. Detail sudah dicatat di log internal.'], 500);
    }
});

function require_rate_limit(string $action, int $maxAttempts, int $windowSeconds, ?string $identity = null): void {
    global $pdo;
    $ip = client_ip();
    $user = current_user();
    $userId = $user['id'] ?? 0;
    $identity = $identity ?: ($userId ? ('user:' . $userId) : ('ip:' . $ip));
    $rateKey = hash('sha256', $action . '|' . $identity . '|' . $ip);
    try {
        $stmt = $pdo->prepare("SELECT id, attempts, window_started_at, blocked_until FROM endpoint_rate_limits WHERE rate_key=? LIMIT 1");
        $stmt->execute([$rateKey]);
        $row = $stmt->fetch();
        $now = time();
        if ($row && !empty($row['blocked_until']) && strtotime($row['blocked_until']) > $now) {
            json_out(['success'=>false,'message'=>'Terlalu banyak percobaan. Silakan coba lagi nanti.'], 429);
        }
        $windowStarted = $row && !empty($row['window_started_at']) ? strtotime($row['window_started_at']) : 0;
        $attempts = ($row && ($now - $windowStarted) < $windowSeconds) ? ((int)$row['attempts'] + 1) : 1;
        $blockedUntil = $attempts > $maxAttempts ? date('Y-m-d H:i:s', $now + $windowSeconds) : null;
        if ($row) {
            $stmt = $pdo->prepare("UPDATE endpoint_rate_limits SET action=?, ip_address=?, user_id=NULLIF(?,0), attempts=?, window_started_at=?, blocked_until=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$action, $ip, (int)$userId, $attempts, ($attempts === 1 ? date('Y-m-d H:i:s', $now) : $row['window_started_at']), $blockedUntil, (int)$row['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO endpoint_rate_limits (rate_key, action, ip_address, user_id, attempts, window_started_at, blocked_until, updated_at) VALUES (?, ?, ?, NULLIF(?,0), ?, NOW(), ?, NOW())");
            $stmt->execute([$rateKey, $action, $ip, (int)$userId, $attempts, $blockedUntil]);
        }
        if ($blockedUntil) json_out(['success'=>false,'message'=>'Terlalu banyak percobaan. Silakan coba lagi nanti.'], 429);
    } catch (Throwable $e) {
        log_error_app('RATE_LIMIT_FALLBACK ' . $e->getMessage(), ['action'=>$action]);
        // Jika tabel migration belum dipasang, jangan membuat aplikasi utama mati di demo lokal.
    }
}

function require_public_submit_rate_limit(): void {
    global $pdo;
    $ip = client_ip();
    $ua = client_agent();
    $hash = hash('sha256', $ua);
    try {
        $stmt = $pdo->prepare("DELETE FROM public_submit_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
        $stmt->execute();
        $stmt = $pdo->prepare("SELECT
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)) AS last_10_min,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) AS last_day
            FROM public_submit_attempts WHERE ip_address=? AND user_agent_hash=?");
        $stmt->execute([$ip, $hash]);
        $row = $stmt->fetch() ?: [];
        if ((int)($row['last_10_min'] ?? 0) >= 3 || (int)($row['last_day'] ?? 0) >= 10) {
            json_out(['success'=>false,'message'=>'Terlalu banyak pengiriman. Silakan coba lagi nanti.'], 429);
        }
        $stmt = $pdo->prepare("INSERT INTO public_submit_attempts (ip_address, user_agent_hash, user_agent, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$ip, $hash, $ua]);
    } catch (Throwable $e) {
        log_error_app('PUBLIC_SUBMIT_RATE_LIMIT_FALLBACK ' . $e->getMessage());
        if (!empty($_SESSION['last_public_submit']) && time() - (int)$_SESSION['last_public_submit'] < 20) {
            json_out(['success'=>false,'message'=>'Terlalu banyak pengiriman. Silakan coba lagi nanti.'], 429);
        }
    }
}

function clean_text($value, int $max = 500): string {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value);
    if (function_exists('mb_strlen') && mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    } elseif (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        json_out(['success' => false, 'message' => 'Sesi login habis. Silakan login ulang.'], 401);
    }
    return $user;
}

function require_role(array $roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_out(['success' => false, 'message' => 'Akses ditolak untuk role ini.'], 403);
    }
    return $user;
}

function role_label(string $role): string {
    return [
        'admin_sh1' => 'Admin SH-1',
        'qc' => 'QC',
        'kontraktor' => 'Kontraktor',
        'proyek' => 'Kontraktor'
    ][$role] ?? $role;
}

function proyek_list(bool $activeOnly = true): array {
    global $pdo;
    $sql = "SELECT id, kode_proyek, nama_proyek, lokasi, site_area, status FROM proyek";
    if ($activeOnly) $sql .= " WHERE status = 'active'";
    $sql .= " ORDER BY nama_proyek ASC";
    try {
        return $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        // Fallback untuk database lama yang belum menjalankan migration kode_proyek/site_area.
        $sql = "SELECT id, NULL AS kode_proyek, nama_proyek, lokasi, lokasi AS site_area, status FROM proyek";
        if ($activeOnly) $sql .= " WHERE status = 'active'";
        $sql .= " ORDER BY nama_proyek ASC";
        return $pdo->query($sql)->fetchAll();
    }
}

function get_proyek(int $id): ?array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, kode_proyek, nama_proyek, lokasi, site_area, status FROM proyek WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare("SELECT id, NULL AS kode_proyek, nama_proyek, lokasi, lokasi AS site_area, status FROM proyek WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function day_name_id(string $date): string {
    $map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $en = date('l', strtotime($date));
    return $map[$en] ?? '';
}

function project_code(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9\s]/', ' ', $name);
    $parts = preg_split('/\s+/', trim($name));
    $code = '';
    foreach ($parts as $p) if ($p !== '') $code .= strtoupper(substr($p, 0, 1));
    return substr($code ?: 'PRJ', 0, 8);
}

function project_code_from_project(?array $proyek): string {
    $kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($proyek['kode_proyek'] ?? '')));
    return $kode !== '' ? substr($kode, 0, 12) : project_code($proyek['nama_proyek'] ?? 'PRJ');
}

function generate_nomor_kqi(PDO $pdo, int $proyekId): string {
    $proyek = get_proyek($proyekId);
    $code = project_code_from_project($proyek);
    $prefix = 'KQI-' . $code . '-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM temuan WHERE nomor_kqi LIKE ?");
    $stmt->execute([$prefix . '%']);
    return $prefix . str_pad((string)((int)$stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
}

function generate_nomor_laporan(PDO $pdo, int $proyekId): string {
    $proyek = get_proyek($proyekId);
    $code = project_code_from_project($proyek);
    $prefix = 'LP-' . $code . '-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM temuan WHERE nomor_dokumen LIKE ? AND jenis_temuan = 'pelanggan'");
    $stmt->execute([$prefix . '%']);
    return $prefix . str_pad((string)((int)$stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
}

function normalize_files_array(string $field): array {
    if (!isset($_FILES[$field])) return [];
    $files = $_FILES[$field];
    if (!is_array($files['name'])) {
        return [[
            'name' => $files['name'], 'type' => $files['type'], 'tmp_name' => $files['tmp_name'],
            'error' => $files['error'], 'size' => $files['size'],
        ]];
    }
    $out = [];
    foreach ($files['name'] as $i => $name) {
        $out[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function safe_upload_many(string $field, string $targetSubdir, bool $required = false): array {
    $items = normalize_files_array($field);
    $saved = [];
    $hasFile = false;
    $maxFiles = 8;
    $maxBytes = 6 * 1024 * 1024;
    $maxTotalBytes = 24 * 1024 * 1024;
    $fileCount = 0;
    $totalBytes = 0;

    foreach ($items as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $fileCount++;
        $totalBytes += (int)($file['size'] ?? 0);
    }
    if ($fileCount > $maxFiles) json_out(['success'=>false,'message'=>'Maksimal upload 8 foto dalam satu request.'], 422);
    if ($totalBytes > $maxTotalBytes) json_out(['success'=>false,'message'=>'Total ukuran upload maksimal 24MB.'], 422);
    if ($fileCount > 0) require_rate_limit('upload_' . trim($targetSubdir, '/'), 20, 600, client_ip());

    foreach ($items as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $hasFile = true;
        if ($file['error'] !== UPLOAD_ERR_OK) json_out(['success'=>false,'message'=>'Upload foto gagal. Kode error: '.$file['error']], 422);
        if (($file['size'] ?? 0) > $maxBytes) json_out(['success'=>false,'message'=>'Ukuran tiap foto maksimal 6MB.'], 422);
        $tmp = $file['tmp_name'];
        $original = (string)($file['name'] ?? '');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExt, true)) json_out(['success'=>false,'message'=>'Ekstensi foto harus JPG, JPEG, PNG, atau WEBP.'], 422);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
        if (!isset($allowed[$mime])) json_out(['success'=>false,'message'=>'Format foto harus JPG, PNG, atau WEBP.'], 422);
        if (($ext === 'png' && $mime !== 'image/png') || (in_array($ext, ['jpg','jpeg'], true) && $mime !== 'image/jpeg') || ($ext === 'webp' && $mime !== 'image/webp')) {
            json_out(['success'=>false,'message'=>'Ekstensi dan MIME type foto tidak sesuai.'], 422);
        }
        $dir = dirname(__DIR__) . '/uploads/' . trim($targetSubdir, '/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = date('YmdHis') . '_' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) json_out(['success'=>false,'message'=>'Gagal menyimpan foto ke folder uploads.'], 500);
        @chmod($dest, 0644);
        $saved[] = [
            'path' => 'uploads/' . trim($targetSubdir, '/') . '/' . $name,
            'original_name' => clean_text($original, 180),
            'mime_type' => $mime,
            'size' => (int)($file['size'] ?? 0),
        ];
    }
    if ($required && !$hasFile) json_out(['success'=>false,'message'=>'Minimal 1 foto wajib diunggah.'], 422);
    return $saved;
}

function status_label(string $status): string {
    $map = [
        'menunggu' => 'Menunggu Validasi Admin', 'divalidasi' => 'Diterima Admin', 'ditolak' => 'Ditolak Admin',
        'belum_dijawab' => 'Belum Dijawab', 'proses' => 'Proses', 'selesai' => 'Selesai', 'belum_selesai' => 'Belum Selesai',
        'Menunggu Validasi Admin' => 'Menunggu Validasi Admin', 'Ditolak Admin' => 'Ditolak Admin', 'Diterima Admin' => 'Diterima Admin',
        'Diteruskan ke QC' => 'Diteruskan ke QC', 'Diteruskan ke Kontraktor' => 'Diteruskan ke Kontraktor',
        'Menunggu Jadwal Perbaikan Kontraktor' => 'Menunggu Jadwal Perbaikan Kontraktor',
        'Jadwal Perbaikan Diajukan Kontraktor' => 'Jadwal Perbaikan Diajukan Kontraktor', 'Sedang Dikerjakan' => 'Sedang Dikerjakan',
        'Selesai Dikerjakan Kontraktor' => 'Selesai Dikerjakan Kontraktor', 'Diperiksa QC' => 'Diperiksa QC',
        'Diteruskan ke Admin' => 'Diteruskan ke Admin', 'Menunggu Konfirmasi Admin ke Pelanggan' => 'Menunggu Konfirmasi Admin ke Pelanggan',
        'Temuan QC Dibuat' => 'Temuan QC Dibuat'
    ];
    return $map[$status] ?? $status;
}

function deadline_status(?string $deadline): string {
    if (!$deadline) return 'Normal';
    $today = new DateTime('today');
    $d = DateTime::createFromFormat('Y-m-d', substr($deadline, 0, 10));
    if (!$d) return 'Normal';
    $diff = (int)$today->diff($d)->format('%r%a');
    if ($diff < 0) return 'Melewati Deadline';
    if ($diff <= 1) return 'Mendekati Deadline';
    return 'Normal';
}

function log_activity(?int $userId, string $role, string $action, string $table = '', ?int $recordId = null, $old = null, $new = null): void {
    global $pdo;
    $line = '[' . date('Y-m-d H:i:s') . "] {$role}#" . ($userId ?: 0) . " {$action} {$table}#" . ($recordId ?: '-') . PHP_EOL;
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    @file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, role, action, table_name, record_id, old_data, new_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId, $role, $action, $table ?: null, $recordId,
            $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Throwable $e) {
        // Audit DB tidak boleh mengganggu proses utama.
    }
}


function log_login_event(string $usernameAttempt, ?string $roleAttempt, string $status, string $riskLevel, string $notes = '', ?int $userId = null, int $failedAttempts = 0): void {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, role, username_attempt, role_attempt, action, status, risk_level, notes, failed_attempts, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, 'login', ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $roleAttempt ?: 'guest', $usernameAttempt, $roleAttempt, $status, $riskLevel, $notes, $failedAttempts, client_ip(), client_agent()]);
    } catch (Throwable $e) {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, role, action, table_name, record_id, old_data, new_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, 'users', ?, NULL, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $roleAttempt ?: 'guest', 'login ' . $status . ': ' . $usernameAttempt, $userId, json_encode(['risk_level'=>$riskLevel,'notes'=>$notes], JSON_UNESCAPED_UNICODE), client_ip(), client_agent()]);
        } catch (Throwable $ignored) {}
    }
}

function access_ban_status(string $usernameAttempt = '', ?array $userRow = null): ?array {
    global $pdo;
    $values = [client_ip()];
    $usernameAttempt = clean_text($usernameAttempt, 120);
    if ($usernameAttempt !== '') $values[] = $usernameAttempt;
    if ($userRow) {
        foreach (['id','username','email'] as $key) {
            if (!empty($userRow[$key])) $values[] = (string)$userRow[$key];
        }
    }
    $values = array_values(array_unique(array_filter($values, fn($v) => $v !== '')));
    if (!$values) return null;
    try {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $stmt = $pdo->prepare("SELECT * FROM banned_access WHERE status='active' AND ((ban_type='ip' AND ban_value=?) OR (ban_type='user' AND ban_value IN ({$placeholders}))) ORDER BY id DESC LIMIT 1");
        $params = array_merge([client_ip()], $values);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        log_error_app('BAN_CHECK_FALLBACK ' . $e->getMessage());
        return null;
    }
}

function require_not_banned(string $usernameAttempt = '', ?array $userRow = null): void {
    $ban = access_ban_status($usernameAttempt, $userRow);
    if ($ban) {
        log_login_event($usernameAttempt, $userRow['role'] ?? null, 'blocked', 'blocked', 'Login ditolak karena ' . ($ban['ban_type'] ?? 'akses') . ' dibanned.', isset($userRow['id']) ? (int)$userRow['id'] : null);
        json_out(['success'=>false,'message'=>'Akses ditolak. Aktivitas login terdeteksi mencurigakan.'], 403);
    }
}

function login_rate_limited(string $email): bool {
    global $pdo;
    $ip = client_ip();
    try {
        $stmt = $pdo->prepare("SELECT attempts, last_attempt_at, blocked_until FROM login_attempts WHERE email=? AND ip_address=? LIMIT 1");
        $stmt->execute([$email, $ip]);
        $row = $stmt->fetch();
        if (!$row) return false;
        if (!empty($row['blocked_until']) && strtotime($row['blocked_until']) > time()) return true;
        if (!empty($row['last_attempt_at']) && strtotime($row['last_attempt_at']) < time() - 900) {
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email=? AND ip_address=?");
            $stmt->execute([$email, $ip]);
        }
    } catch (Throwable $e) { log_error_app('LOGIN_RATE_CHECK ' . $e->getMessage()); }
    return false;
}

function record_login_attempt(string $email, bool $success): int {
    global $pdo;
    $ip = client_ip();
    try {
        if ($success) {
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email=? AND ip_address=?");
            $stmt->execute([$email, $ip]);
            return 0;
        }
        $stmt = $pdo->prepare("SELECT id, attempts, last_attempt_at FROM login_attempts WHERE email=? AND ip_address=? LIMIT 1");
        $stmt->execute([$email, $ip]);
        $row = $stmt->fetch();
        $expired = !$row || empty($row['last_attempt_at']) || strtotime($row['last_attempt_at']) < time() - 900;
        $attempts = $expired ? 1 : ((int)$row['attempts'] + 1);
        $blocked = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
        if ($row) {
            $stmt = $pdo->prepare("UPDATE login_attempts SET attempts=?, last_attempt_at=NOW(), blocked_until=? WHERE id=?");
            $stmt->execute([$attempts, $blocked, (int)$row['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, attempts, last_attempt_at, blocked_until) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->execute([$email, $ip, $attempts, $blocked]);
        }
        return $attempts;
    } catch (Throwable $e) { log_error_app('LOGIN_RATE_RECORD ' . $e->getMessage()); return 0; }
}

function can_access_project(array $user, int $proyekId): bool {
    if ($user['role'] === 'admin_sh1') return true;
    return !empty($user['proyek_id']) && (int)$user['proyek_id'] === $proyekId;
}
