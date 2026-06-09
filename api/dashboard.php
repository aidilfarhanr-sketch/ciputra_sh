<?php
require __DIR__ . '/config.php';
$user = require_login();
$where = ['1=1']; $params = [];
if ($user['role'] === 'qc') { $where[] = 't.proyek_id=?'; $params[] = (int)$user['proyek_id']; }
if ($user['role'] === 'kontraktor') { $where[] = 't.proyek_id=?'; $where[] = 't.kontraktor_id=?'; $params[] = (int)$user['proyek_id']; $params[] = (int)$user['id']; }
$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(t.jenis_temuan='qc'),0) total_qc, COALESCE(SUM(t.jenis_temuan='pelanggan'),0) total_pelanggan, COALESCE(SUM(t.status_validasi='menunggu'),0) validasi_menunggu, COALESCE(SUM(t.status_jawaban='belum_dijawab'),0) belum_dijawab, COALESCE(SUM(t.status_jawaban='proses'),0) proses, COALESCE(SUM(t.status_jawaban='selesai'),0) selesai FROM temuan t WHERE {$whereSql}");
$stmt->execute($params);
$summary = $stmt->fetch();
$projectWhere = $user['role'] === 'admin_sh1' ? '' : 'WHERE p.id=' . (int)$user['proyek_id'];
$joinExtra = $user['role'] === 'kontraktor' ? ' AND t.kontraktor_id=' . (int)$user['id'] : '';
$stmt = $pdo->query("SELECT p.nama_proyek, p.lokasi, COUNT(t.id) total, COALESCE(SUM(t.jenis_temuan='qc'),0) qc, COALESCE(SUM(t.jenis_temuan='pelanggan'),0) pelanggan, COALESCE(SUM(t.status_jawaban='selesai'),0) selesai FROM proyek p LEFT JOIN temuan t ON t.proyek_id=p.id {$joinExtra} {$projectWhere} GROUP BY p.id, p.nama_proyek, p.lokasi ORDER BY p.nama_proyek ASC");
json_out(['success'=>true,'summary'=>$summary,'proyek'=>$stmt->fetchAll()]);
