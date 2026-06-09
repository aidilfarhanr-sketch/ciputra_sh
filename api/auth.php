<?php
require __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'me') {
    json_out(['success' => true, 'user' => current_user()]);
}

if ($action === 'login') {
    require_rate_limit('auth_login_request', 30, 900, client_ip());
    $data = input_json();
    $username = clean_text($data['username'] ?? '', 120);
    $password = (string)($data['password'] ?? '');

    if ($username === '' || $password === '') {
        json_out(['success' => false, 'message' => 'Username/email dan password wajib diisi.'], 422);
    }
    require_not_banned($username);
    if (login_rate_limited($username)) {
        json_out(['success' => false, 'message' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.'], 429);
    }

    $stmt = $pdo->prepare("SELECT u.id, u.nama_lengkap, u.username, u.email, u.password, u.no_hp, u.role, u.proyek_id, p.nama_proyek, COALESCE(u.status_akun, u.status) AS status
        FROM users u
        LEFT JOIN proyek p ON p.id = u.proyek_id
        WHERE (u.username = ? OR u.email = ?) AND u.role IN ('admin_sh1','qc','kontraktor')
        LIMIT 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user) {
        require_not_banned($username, $user);
    }

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
        $failedAttempts = record_login_attempt($username, false);
        $risk = $failedAttempts >= 3 ? 'mencurigakan' : 'normal';
        log_login_event($username, $user['role'] ?? null, 'failed', $risk, 'Username/password salah, akun nonaktif, atau bukan akun internal. Percobaan gagal: ' . $failedAttempts, $user['id'] ?? null, $failedAttempts);
        log_activity(null, 'guest', 'login gagal: ' . $username, 'users', null, null, ['username' => $username]);
        json_out(['success' => false, 'message' => 'Username/email atau password salah, akun tidak aktif, atau bukan akun internal.'], 401);
    }

    record_login_attempt($username, true);
    session_regenerate_id(true);
    csrf_token();
    unset($user['password']);
    $_SESSION['user'] = $user;
    log_login_event($username, $user['role'], 'success', 'normal', 'Login berhasil.', (int)$user['id']);
    log_activity((int)$user['id'], $user['role'], 'login berhasil', 'users', (int)$user['id']);
    json_out(['success' => true, 'message' => 'Login berhasil.', 'user' => $user]);
}


if ($action === 'register') {
    json_out(['success' => false, 'message' => 'Register publik sudah dinonaktifkan. Akun QC dan Kontraktor hanya dapat dibuat oleh Admin SH-1.'], 403);
}

if ($action === 'logout') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_out(['success' => false, 'message' => 'Logout harus menggunakan request POST yang aman.'], 405);
    }
    $u = current_user();
    if ($u) log_activity((int)$u['id'], $u['role'], 'logout', 'users', (int)$u['id']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    json_out(['success' => true, 'message' => 'Berhasil logout.']);
}

json_out(['success' => false, 'message' => 'Action auth tidak dikenal.'], 404);
