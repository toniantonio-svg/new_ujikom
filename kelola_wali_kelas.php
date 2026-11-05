<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($v) {
    return htmlspecialchars((string)($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

// === AKTIFKAN KEMBALI ===
if (isset($_GET['aktifkan']) && is_numeric($_GET['aktifkan'])) {
    $id = (int)$_GET['aktifkan'];
    $transaksi = false;
    try {
        mysqli_begin_transaction($conn);
        $transaksi = true;

        $stmt = $conn->prepare("SELECT wk.guru_id, wk.kelas_id, wk.tahun_ajaran_id, g.user_id 
                                FROM t_wali_kelas wk LEFT JOIN t_guru g ON wk.guru_id = g.id WHERE wk.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) throw new Exception("Data tidak ditemukan!");

        $stmt = $conn->prepare("SELECT id FROM t_wali_kelas 
                                WHERE guru_id = ? AND tahun_ajaran_id = ? AND status_aktif = 1 AND id <> ?");
        $stmt->bind_param("iii", $data['guru_id'], $data['tahun_ajaran_id'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows)
            throw new Exception("Guru ini sudah jadi wali kelas aktif di tahun yang sama!");

        $stmt = $conn->prepare("SELECT id FROM t_wali_kelas 
                                WHERE kelas_id = ? AND tahun_ajaran_id = ? AND status_aktif = 1 AND id <> ?");
        $stmt->bind_param("iii", $data['kelas_id'], $data['tahun_ajaran_id'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows)
            throw new Exception("Kelas ini sudah punya wali kelas aktif di tahun yang sama!");

        $stmt = $conn->prepare("UPDATE t_wali_kelas SET status_aktif = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if (!empty($data['user_id'])) {
            $stmt = $conn->prepare("UPDATE t_users SET role = 'wali_kelas' WHERE id = ?");
            $stmt->bind_param("i", $data['user_id']);
            $stmt->execute();
        }

        mysqli_commit($conn);
        header("Location: ?diaktifkan=1");
        exit;
    } catch (Throwable $e) {
        if ($transaksi) mysqli_rollback($conn);
        $pesan = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}

// === CABUT STATUS ===
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $transaksi = false;
    try {
        mysqli_begin_transaction($conn);
        $transaksi = true;

        $stmt = $conn->prepare("SELECT wk.guru_id, g.user_id 
                                FROM t_wali_kelas wk LEFT JOIN t_guru g ON wk.guru_id = g.id WHERE wk.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) throw new Exception("Data tidak ditemukan!");

        $stmt = $conn->prepare("UPDATE t_wali_kelas SET status_aktif = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if (!empty($data['user_id'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS jml FROM t_wali_kelas 
                                    WHERE guru_id = ? AND status_aktif = 1 AND id <> ?");
            $stmt->bind_param("ii", $data['guru_id'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['jml'] == 0) {
                $stmt = $conn->prepare("UPDATE t_users SET role = 'guru' WHERE id = ?");
                $stmt->bind_param("i", $data['user_id']);
                $stmt->execute();
            }
        }

        mysqli_commit($conn);
        header("Location: ?dihapus=1");
        exit;
    } catch (Throwable $e) {
        if ($transaksi) mysqli_rollback($conn);
        $pesan = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}

// === SIMPAN BARU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $gid = (int)($_POST['guru_id'] ?? 0);
    $kid = (int)($_POST['kelas_id'] ?? 0);
    $tid = (int)($_POST['tahun_ajaran_id'] ?? 0);
    $transaksi = false;

    try {
        if (!$gid || !$kid || !$tid) throw new Exception("Lengkapi semua data!");
        mysqli_begin_transaction($conn);
        $transaksi = true;

        $stmt = $conn->prepare("SELECT * FROM t_guru WHERE id = ? AND status_aktif = 1");
        $stmt->bind_param("i", $gid); $stmt->execute();
        $guru = $stmt->get_result()->fetch_assoc();
        if (!$guru) throw new Exception("Guru tidak ditemukan!");

        $stmt = $conn->prepare("SELECT id FROM t_kelas WHERE id = ? AND status_aktif = 1");
        $stmt->bind_param("i", $kid); $stmt->execute();
        if (!$stmt->get_result()->num_rows) throw new Exception("Kelas tidak ditemukan atau sudah dinonaktifkan!");

        $stmt = $conn->prepare("SELECT id, tanggal_mulai, tanggal_selesai FROM t_tahun_ajaran WHERE id = ? AND status_aktif = 1");
        $stmt->bind_param("i", $tid); $stmt->execute();
        $tahun = $stmt->get_result()->fetch_assoc();
        if (!$tahun) throw new Exception("Tahun ajaran tidak ditemukan!");

        $stmt = $conn->prepare("SELECT id FROM t_wali_kelas 
                                WHERE guru_id = ? AND tahun_ajaran_id = ? AND status_aktif = 1");
        $stmt->bind_param("ii", $gid, $tid); $stmt->execute();
        if ($stmt->get_result()->num_rows)
            throw new Exception("Guru sudah jadi wali kelas di tahun ini! Gunakan tombol Aktifkan.");

        $stmt = $conn->prepare("SELECT id FROM t_wali_kelas 
                                WHERE kelas_id = ? AND tahun_ajaran_id = ? AND status_aktif = 1");
        $stmt->bind_param("ii", $kid, $tid); $stmt->execute();
        if ($stmt->get_result()->num_rows)
            throw new Exception("Kelas sudah punya wali kelas di tahun ini!");

        $email = trim($guru['email'] ?? '') ?: ($guru['nip'] ? $guru['nip'].'@sekolah.sch.id' : 'g'.$gid.'@sekolah.sch.id');
        $akun = null;
        if (!empty($guru['user_id'])) {
            $stmt = $conn->prepare("SELECT id,role FROM t_users WHERE id = ?");
            $stmt->bind_param("i", $guru['user_id']); $stmt->execute();
            $akun = $stmt->get_result()->fetch_assoc();
        }
        if (!$akun) {
            $stmt = $conn->prepare("SELECT id,role FROM t_users WHERE email = ?");
            $stmt->bind_param("s", $email); $stmt->execute();
            $akun = $stmt->get_result()->fetch_assoc();
        }

        if ($akun) {
            if ($akun['role'] === 'admin') throw new Exception("Email dipakai administrator!");
            $user_id = $akun['id'];
            $stmt = $conn->prepare("UPDATE t_users SET name=?, role='wali_kelas' WHERE id=?");
            $stmt->bind_param("si", $guru['nama'], $user_id); $stmt->execute();
        } else {
            $pass = password_hash('guru123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO t_users (name,email,email_verified_at,password,role) VALUES (?,?,NOW(),?,'wali_kelas')");
            $stmt->bind_param("sss", $guru['nama'], $email, $pass); $stmt->execute();
            $user_id = $conn->insert_id;
        }

        $stmt = $conn->prepare("UPDATE t_guru SET user_id=? WHERE id=?");
        $stmt->bind_param("ii", $user_id, $gid); $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO t_wali_kelas (tahun_ajaran_id,kelas_id,guru_id,tanggal_mulai,tanggal_selesai,status_aktif) 
                                VALUES (?,?,?,?,?,1)");
        $stmt->bind_param("iiiss", $tid, $kid, $gid, $tahun['tanggal_mulai'], $tahun['tanggal_selesai']);
        $stmt->execute();

        mysqli_commit($conn);
        header("Location: ?berhasil=1");
        exit;
    } catch (Throwable $e) {
        if ($transaksi) mysqli_rollback($conn);
        $pesan = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}

// Pesan notifikasi
if (isset($_GET['berhasil'])) $pesan = '<div class="alert alert-success">✅ Berhasil ditetapkan!</div>';
if (isset($_GET['dihapus'])) $pesan = '<div class="alert alert-success">✅ Status dicabut!</div>';
if (isset($_GET['diaktifkan'])) $pesan = '<div class="alert alert-success">✅ Diaktifkan kembali!</div>';

// === DATA ===
$total_aktif = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) x FROM t_wali_kelas WHERE status_aktif=1"))['x'];
$total_tidak_aktif = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) x FROM t_wali_kelas WHERE status_aktif=0"))['x'];

$result = mysqli_query($conn, "
    SELECT wk.*, g.nama guru, g.nip, k.nama kelas, k.status_aktif kelas_status, ta.nama tahun
    FROM t_wali_kelas wk
    LEFT JOIN t_guru g ON wk.guru_id=g.id
    LEFT JOIN t_kelas k ON wk.kelas_id=k.id
    LEFT JOIN t_tahun_ajaran ta ON wk.tahun_ajaran_id=ta.id
    ORDER BY wk.status_aktif DESC, ta.nama DESC, wk.id DESC
");

$daftar_g = [];
$guru_list = mysqli_query($conn, "SELECT id,nama,nip FROM t_guru WHERE status_aktif=1 AND id NOT IN (
    SELECT guru_id FROM t_wali_kelas WHERE status_aktif=1 AND guru_id IS NOT NULL
) ORDER BY nama");
while ($g = mysqli_fetch_assoc($guru_list)) $daftar_g[] = $g;
$sisa_g = count($daftar_g);

$daftar_k = [];
$kelas_list = mysqli_query($conn, "SELECT id,nama FROM t_kelas WHERE status_aktif=1 ORDER BY nama");
while ($k = mysqli_fetch_assoc($kelas_list)) $daftar_k[] = $k;

$tahun_terpilih = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id,nama FROM t_tahun_ajaran WHERE nama='2026/2027' LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Wali Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h4 class="mb-4">Kelola Wali Kelas</h4>

            <?= $pesan ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted fs-6">Wali Kelas Aktif</h6>
                            <h3 class="text-primary fw-bold"><?= $total_aktif ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted fs-6">Tidak Aktif / Dicabut</h6>
                            <h3 class="text-secondary fw-bold"><?= $total_tidak_aktif ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Tambah -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Tetapkan Wali Kelas Baru</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Guru</label>
                                <select name="guru_id" class="form-select" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php foreach ($daftar_g as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= e($g['nama']) ?> (<?= e($g['nip']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Tersedia: <?= $sisa_g ?> guru</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kelas</label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($daftar_k as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= e($k['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="tahun_ajaran_id" class="form-select" required>
                                    <option value="<?= $tahun_terpilih['id'] ?>"><?= e($tahun_terpilih['nama']) ?></option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-success">Tetapkan Sebagai Wali Kelas</button>
                    </form>
                </div>
            </div>

            <!-- Daftar Wali Kelas -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Daftar Wali Kelas</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Guru</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Status Wali Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): 
                                    $kelas_nama = e($row['kelas'] ?? '-');
                                    $kelas_aktif = (int)($row['kelas_status'] ?? 1);
                                    $status_wali = (int)($row['status_aktif'] ?? 0);

                                    if ($kelas_aktif !== 1) {
                                        $kelas_nama .= ' <span class="badge bg-danger ms-1">KELAS INI DINONAKTIFKAN</span>';
                                    }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($row['guru']) ?></td>
                                    <td><?= $kelas_nama ?></td>
                                    <td><?= e($row['tahun']) ?></td>
                                    <td>
                                        <?php if ($status_wali === 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status_wali === 1): ?>
                                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cabut status wali kelas ini?')">Cabut</a>
                                        <?php else: ?>
                                            <a href="?aktifkan=<?= $row['id'] ?>" class="btn btn-sm btn-success">Aktifkan</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>