-- =====================================================
-- DATABASE CIPUTRA SH-1 - REVISI ALUR WORD
-- XAMPP / phpMyAdmin / MySQL MariaDB
-- Catatan: backup database lama sebelum import ulang.
-- =====================================================

CREATE DATABASE IF NOT EXISTS ciputra_sh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ciputra_sh;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS endpoint_rate_limits;
DROP TABLE IF EXISTS public_submit_attempts;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS banned_access;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS foto_jawaban;
DROP TABLE IF EXISTS jawaban;
DROP TABLE IF EXISTS foto_temuan;
DROP TABLE IF EXISTS temuan;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS proyek;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE proyek (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_proyek VARCHAR(30) NOT NULL UNIQUE,
  nama_proyek VARCHAR(150) NOT NULL UNIQUE,
  lokasi VARCHAR(150) NULL,
  site_area VARCHAR(150) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_proyek_status (status),
  INDEX idx_proyek_kode (kode_proyek),
  INDEX idx_proyek_nama (nama_proyek)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_lengkap VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(120) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_hp VARCHAR(40) NULL,
  role ENUM('admin_sh1','qc','kontraktor') NOT NULL,
  proyek_id INT UNSIGNED NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  status_akun ENUM('active','inactive') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  password_changed_at DATETIME NULL,
  created_by_admin INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_proyek (proyek_id),
  INDEX idx_users_status (status_akun),
  CONSTRAINT fk_users_proyek FOREIGN KEY (proyek_id) REFERENCES proyek(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_created_by_admin FOREIGN KEY (created_by_admin) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE temuan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis_temuan ENUM('qc','pelanggan') NOT NULL,
  sumber_temuan ENUM('qc','pelanggan') NOT NULL,
  nomor_kqi VARCHAR(80) NULL UNIQUE,
  nomor_dokumen VARCHAR(80) NULL,
  proyek_id INT UNSIGNED NOT NULL,
  blok_unit VARCHAR(80) NOT NULL,
  tanggal DATE NOT NULL,
  tanggal_keluhan DATE NULL,
  tanggal_serah_terima DATE NULL,
  tanggal_diterima_admin DATE NULL,
  tanggal_diteruskan_qc DATE NULL,
  tanggal_diteruskan_kontraktor DATE NULL,
  tanggal_deadline_kontraktor DATE NULL,
  tanggal_diteruskan_admin DATE NULL,
  nama_pelapor VARCHAR(120) NOT NULL,
  no_hp VARCHAR(40) NULL,
  email VARCHAR(120) NULL,
  kategori VARCHAR(120) NULL,
  keterangan TEXT NOT NULL,
  dampak TEXT NULL,
  fakta TEXT NULL,
  penyebab_utama TEXT NULL,
  antisipasi TEXT NULL,
  deadline DATE NULL,
  status VARCHAR(80) NOT NULL,
  status_validasi ENUM('menunggu','divalidasi','ditolak') NOT NULL DEFAULT 'menunggu',
  status_jawaban ENUM('belum_dijawab','proses','selesai') NOT NULL DEFAULT 'belum_dijawab',
  alasan_penolakan VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  dibuat_oleh INT UNSIGNED NULL,
  diteruskan_ke_qc_oleh INT UNSIGNED NULL,
  diteruskan_ke_kontraktor_oleh INT UNSIGNED NULL,
  qc_id INT UNSIGNED NULL,
  kontraktor_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_temuan_jenis (jenis_temuan),
  INDEX idx_temuan_nomor_dokumen (nomor_dokumen),
  INDEX idx_temuan_proyek (proyek_id),
  INDEX idx_temuan_status (status),
  INDEX idx_temuan_status_validasi (status_validasi),
  INDEX idx_temuan_status_jawaban (status_jawaban),
  INDEX idx_temuan_sumber (sumber_temuan),
  INDEX idx_temuan_qc (qc_id),
  INDEX idx_temuan_kontraktor (kontraktor_id),
  INDEX idx_temuan_created_at (created_at),
  INDEX idx_temuan_deadline (tanggal_deadline_kontraktor),
  CONSTRAINT fk_temuan_proyek FOREIGN KEY (proyek_id) REFERENCES proyek(id) ON DELETE RESTRICT,
  CONSTRAINT fk_temuan_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_temuan_qc FOREIGN KEY (qc_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_temuan_kontraktor FOREIGN KEY (kontraktor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE foto_temuan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  temuan_id INT UNSIGNED NOT NULL,
  foto_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(180) NULL,
  mime_type VARCHAR(80) NULL,
  size INT UNSIGNED NULL,
  keterangan_foto VARCHAR(255) NULL,
  area_kerusakan VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_foto_temuan_temuan (temuan_id),
  CONSTRAINT fk_foto_temuan_temuan FOREIGN KEY (temuan_id) REFERENCES temuan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jawaban (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  temuan_id INT UNSIGNED NOT NULL UNIQUE,
  kontraktor_id INT UNSIGNED NULL,
  tanggal_rencana_perbaikan DATE NULL,
  jam_rencana_perbaikan TIME NULL,
  jawaban_kontraktor TEXT NULL,
  status_jawaban VARCHAR(40) NULL,
  catatan_qc TEXT NULL,
  penyebab TEXT NULL,
  antisipasi TEXT NULL,
  keterangan_perbaikan TEXT NULL,
  tanggal_selesai DATE NULL,
  status_perbaikan ENUM('belum_selesai','proses','selesai') NOT NULL DEFAULT 'proses',
  ttd_admin VARCHAR(120) NULL,
  ttd_proyek VARCHAR(120) NULL,
  ttd_pemilik_rumah VARCHAR(120) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_jawaban_status (status_perbaikan),
  INDEX idx_jawaban_created_by (created_by),
  INDEX idx_jawaban_kontraktor (kontraktor_id),
  CONSTRAINT fk_jawaban_temuan FOREIGN KEY (temuan_id) REFERENCES temuan(id) ON DELETE CASCADE,
  CONSTRAINT fk_jawaban_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_jawaban_kontraktor FOREIGN KEY (kontraktor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE foto_jawaban (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jawaban_id INT UNSIGNED NOT NULL,
  foto_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(180) NULL,
  mime_type VARCHAR(80) NULL,
  size INT UNSIGNED NULL,
  keterangan_foto VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_foto_jawaban_jawaban (jawaban_id),
  CONSTRAINT fk_foto_jawaban_jawaban FOREIGN KEY (jawaban_id) REFERENCES jawaban(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  role VARCHAR(40) NULL,
  username_attempt VARCHAR(180) NULL,
  role_attempt VARCHAR(40) NULL,
  action VARCHAR(160) NOT NULL,
  table_name VARCHAR(80) NULL,
  record_id INT UNSIGNED NULL,
  status VARCHAR(40) NULL,
  risk_level ENUM('normal','mencurigakan','blocked','diblokir') NOT NULL DEFAULT 'normal',
  notes VARCHAR(255) NULL,
  failed_attempts INT UNSIGNED NULL DEFAULT 0,
  old_data JSON NULL,
  new_data JSON NULL,
  ip_address VARCHAR(80) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_username_attempt (username_attempt),
  INDEX idx_audit_ip_status (ip_address, status, risk_level),
  INDEX idx_audit_table_record (table_name, record_id),
  INDEX idx_audit_created_at (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banned_access (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ban_type ENUM('ip','user') NOT NULL,
  ban_value VARCHAR(180) NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  banned_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ban_type_value (ban_type, ban_value),
  INDEX idx_ban_status (status),
  CONSTRAINT fk_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
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

CREATE TABLE public_submit_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(80) NOT NULL,
  user_agent_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_public_submit_ip_hash_time (ip_address, user_agent_hash, created_at),
  INDEX idx_public_submit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE endpoint_rate_limits (
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
  INDEX idx_endpoint_blocked (blocked_until),
  CONSTRAINT fk_endpoint_rate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO proyek (kode_proyek, nama_proyek, lokasi, site_area, status) VALUES
('CGB', 'CitraGarden Bintaro', 'Bintaro', 'Bintaro', 'active'),
('CCS', 'Citra City Sentul', 'Sentul', 'Sentul', 'active'),
('CRT', 'CitraRaya Tangerang', 'Tangerang', 'Tangerang', 'active'),
('CSR', 'Citra Sentul Raya', 'Sentul', 'Sentul', 'active'),
('CGS', 'CitraGarden Serpong', 'Serpong Tangerang', 'Serpong Tangerang', 'active'),
('CMC2', 'CitraMaja City 2', 'Maja', 'Maja', 'active'),
('CGCJ', 'CitraGarden City Jakarta', 'Jakarta', 'Jakarta', 'active'),
('CHS', 'Citra Hills Sentul', 'Sentul', 'Sentul', 'active'),
('CGBMW', 'CitraGarden BMW Cilegon', 'Cilegon', 'Cilegon', 'active');

-- Login default:
-- admin / admin123
-- qc / qc123
-- kontraktor / kontraktor123
INSERT INTO users (nama_lengkap, username, email, password, no_hp, role, proyek_id, status, status_akun, must_change_password, created_by_admin) VALUES
('Admin Ciputra SH-1', 'admin', 'admin@ciputra-sh.local', '$2y$12$2aDnclv4fY4POuiuRYPyEuq2FHNrGIX3.Vsiy8owIFTLzMmaYKEaC', '089509753717', 'admin_sh1', NULL, 'active', 'active', 1, NULL),
('QC CitraGarden Serpong', 'qc', 'qc@ciputra-sh.local', '$2y$12$amCkmvKG991FzLorkICMp.8cpPd6F6LeKZ0C8wJcepZM2xhh6f1Y6', '081234560001', 'qc', 5, 'active', 'active', 1, 1),
('Kontraktor CitraGarden Serpong', 'kontraktor', 'kontraktor@ciputra-sh.local', '$2y$12$3IlvXctLPF22TKzl4ZxLY.qfZG6UAZmuajKPPh3QyRfjxX12YoZhu', '081234567890', 'kontraktor', 5, 'active', 'active', 1, 1);

INSERT INTO temuan
(jenis_temuan, sumber_temuan, nomor_kqi, nomor_dokumen, proyek_id, blok_unit, tanggal, tanggal_keluhan, tanggal_diteruskan_kontraktor, tanggal_deadline_kontraktor, nama_pelapor, no_hp, email, keterangan, dampak, fakta, penyebab_utama, antisipasi, deadline, status, status_validasi, status_jawaban, created_by, qc_id, kontraktor_id)
VALUES
('qc', 'qc', 'KQI-CGS-20260524-001', 'QC-DOC-001', 5, 'Blok A/12', '2026-05-24', '2026-05-24', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'QC Lapangan', '081200001111', NULL, 'Sudutan pintu utama terlihat gompal dan perlu pengecekan finishing.', 'Finishing pintu tidak rapi.', 'Sudutan pintu utama gompal.', 'Belum dianalisis.', 'Perlu pengecekan finishing dan perlindungan area pintu.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Diteruskan ke Kontraktor', 'divalidasi', 'proses', 2, 2, 3),
('pelanggan', 'pelanggan', NULL, 'LP-CGS-20260524-001', 5, 'Blok B/09', '2026-05-24', '2026-05-24', NULL, NULL, 'Bapak Rares', '081200002222', 'pelanggan@example.com', 'Terdapat rembesan air pada area plafon kamar.', NULL, NULL, NULL, NULL, NULL, 'Menunggu Validasi Admin', 'menunggu', 'belum_dijawab', NULL, NULL, NULL);

INSERT INTO foto_temuan (temuan_id, foto_path, original_name, mime_type, size, keterangan_foto, area_kerusakan) VALUES
(1, 'assets/reference_pdftemuan.jpeg', 'reference_pdftemuan.jpeg', 'image/jpeg', 0, 'Foto temuan QC pintu utama', 'Pintu utama'),
(2, 'assets/reference_pdftemuan.jpeg', 'reference_pdftemuan.jpeg', 'image/jpeg', 0, 'Foto rembesan plafon kamar', 'Plafon kamar');

INSERT INTO jawaban (temuan_id, kontraktor_id, tanggal_rencana_perbaikan, jam_rencana_perbaikan, penyebab, antisipasi, keterangan_perbaikan, jawaban_kontraktor, tanggal_selesai, status_perbaikan, status_jawaban, ttd_admin, ttd_proyek, ttd_pemilik_rumah, created_by) VALUES
(1, 3, CURDATE(), '09:00:00', 'Finishing kurang rata pada sudut pintu.', 'Area sudut pintu diperkuat dan finishing ulang.', 'Perbaikan sedang diproses oleh tim kontraktor.', 'Perbaikan sedang diproses oleh tim kontraktor.', NULL, 'proses', 'proses', 'QC CitraGarden Serpong', 'Kontraktor', '', 3);

INSERT INTO foto_jawaban (jawaban_id, foto_path, original_name, mime_type, size, keterangan_foto) VALUES
(1, 'assets/reference_pdfjawaban.jpeg', 'reference_pdfjawaban.jpeg', 'image/jpeg', 0, 'Foto hasil perbaikan sementara');
