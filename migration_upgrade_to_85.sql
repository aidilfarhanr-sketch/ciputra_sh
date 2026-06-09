-- =====================================================
-- MIGRATION CIPUTRA SH-1 PRODUCTION READINESS 85%
-- Aman untuk database lama: tidak DROP TABLE dan tidak menghapus data.
-- Jalankan setelah backup database dari phpMyAdmin/XAMPP.
-- =====================================================
USE ciputra_sh;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS status_akun ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER status,
  ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status_akun,
  ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL AFTER must_change_password,
  ADD COLUMN IF NOT EXISTS created_by_admin INT UNSIGNED NULL AFTER password_changed_at;

ALTER TABLE foto_temuan
  ADD COLUMN IF NOT EXISTS original_name VARCHAR(180) NULL AFTER foto_path,
  ADD COLUMN IF NOT EXISTS mime_type VARCHAR(80) NULL AFTER original_name,
  ADD COLUMN IF NOT EXISTS size INT UNSIGNED NULL AFTER mime_type;

ALTER TABLE foto_jawaban
  ADD COLUMN IF NOT EXISTS original_name VARCHAR(180) NULL AFTER foto_path,
  ADD COLUMN IF NOT EXISTS mime_type VARCHAR(80) NULL AFTER original_name,
  ADD COLUMN IF NOT EXISTS size INT UNSIGNED NULL AFTER mime_type;

CREATE TABLE IF NOT EXISTS public_submit_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(80) NOT NULL,
  user_agent_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_public_submit_ip_hash_time (ip_address, user_agent_hash, created_at),
  INDEX idx_public_submit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS endpoint_rate_limits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_key CHAR(64) NOT NULL UNIQUE,
  action VARCHAR(120) NOT NULL,
  ip_address VARCHAR(80) NOT NULL,
  user_id INT UNSIGNED NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  window_started_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_endpoint_action_ip (action, ip_address),
  INDEX idx_endpoint_user (user_id),
  INDEX idx_endpoint_blocked (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL,
  ip_address VARCHAR(80) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL,
  blocked_until DATETIME NULL,
  UNIQUE KEY uniq_login_rate (email, ip_address),
  INDEX idx_login_blocked (blocked_until),
  INDEX idx_login_last_attempt (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  role VARCHAR(40) NULL,
  action VARCHAR(160) NOT NULL,
  table_name VARCHAR(80) NULL,
  record_id INT UNSIGNED NULL,
  old_data JSON NULL,
  new_data JSON NULL,
  ip_address VARCHAR(80) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_table_record (table_name, record_id),
  INDEX idx_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);
CREATE INDEX IF NOT EXISTS idx_users_proyek ON users (proyek_id);
CREATE INDEX IF NOT EXISTS idx_users_status_akun ON users (status_akun);
CREATE INDEX IF NOT EXISTS idx_temuan_proyek ON temuan (proyek_id);
CREATE INDEX IF NOT EXISTS idx_temuan_status ON temuan (status);
CREATE INDEX IF NOT EXISTS idx_temuan_status_validasi ON temuan (status_validasi);
CREATE INDEX IF NOT EXISTS idx_temuan_status_jawaban ON temuan (status_jawaban);
CREATE INDEX IF NOT EXISTS idx_temuan_sumber ON temuan (sumber_temuan);
CREATE INDEX IF NOT EXISTS idx_temuan_jenis ON temuan (jenis_temuan);
CREATE INDEX IF NOT EXISTS idx_temuan_qc ON temuan (qc_id);
CREATE INDEX IF NOT EXISTS idx_temuan_kontraktor ON temuan (kontraktor_id);
CREATE INDEX IF NOT EXISTS idx_temuan_created_at ON temuan (created_at);
CREATE INDEX IF NOT EXISTS idx_temuan_deadline ON temuan (tanggal_deadline_kontraktor);

-- Catatan: setelah migration, cek login admin lalu ganti password default.


-- =====================================================
-- MIGRATION LANJUTAN: MASTER PROYEK + AUDIT BAN/UNBAN + FOOTER/LOGO REVISION
-- Aman: tidak DROP TABLE dan tidak menghapus data lama.
-- =====================================================

ALTER TABLE proyek
  ADD COLUMN IF NOT EXISTS kode_proyek VARCHAR(30) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS site_area VARCHAR(150) NULL AFTER lokasi;

UPDATE proyek SET kode_proyek = UPPER(REPLACE(REPLACE(REPLACE(LEFT(nama_proyek, 12),' ',''),'-',''),'_','')) WHERE kode_proyek IS NULL OR kode_proyek = '';
UPDATE proyek SET site_area = lokasi WHERE (site_area IS NULL OR site_area = '') AND lokasi IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_proyek_kode ON proyek (kode_proyek);
CREATE INDEX IF NOT EXISTS idx_proyek_status ON proyek (status);

ALTER TABLE audit_logs
  ADD COLUMN IF NOT EXISTS username_attempt VARCHAR(180) NULL AFTER role,
  ADD COLUMN IF NOT EXISTS role_attempt VARCHAR(40) NULL AFTER username_attempt,
  ADD COLUMN IF NOT EXISTS status VARCHAR(40) NULL AFTER record_id,
  ADD COLUMN IF NOT EXISTS risk_level ENUM('normal','mencurigakan','blocked','diblokir') NOT NULL DEFAULT 'normal' AFTER status,
  ADD COLUMN IF NOT EXISTS notes VARCHAR(255) NULL AFTER risk_level,
  ADD COLUMN IF NOT EXISTS failed_attempts INT UNSIGNED NULL DEFAULT 0 AFTER notes;

CREATE INDEX IF NOT EXISTS idx_audit_username_attempt ON audit_logs (username_attempt);
CREATE INDEX IF NOT EXISTS idx_audit_ip_status ON audit_logs (ip_address, status, risk_level);

CREATE TABLE IF NOT EXISTS banned_access (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ban_type ENUM('ip','user') NOT NULL,
  ban_value VARCHAR(180) NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  banned_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ban_type_value (ban_type, ban_value),
  INDEX idx_ban_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
