<?php
require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($value) {
    return htmlspecialchars(
        (string)($value ?? '-'),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Hitung total penempatan
$query_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM t_kelas_siswa");
if (!$query_total) die("Query total gagal: " . mysqli_error($conn));
$total = (int)(mysqli_fetch_assoc($query_total)['total'] ?? 0);

// Ambil semua data penempatan
$result = mysqli_query($conn, "
    SELECT ks.id, ks.siswa_id, ks.kelas_id, ks.tahun_ajaran_id,
           s.nama AS nama_siswa, 
           k.nama AS nama_kelas, 
           ta.nama AS nama_tahun_ajaran,
           ks.created_at, ks.updated_at
    FROM t_kelas_siswa ks
    LEFT JOIN t_siswa s ON ks.siswa_id = s.id
    LEFT JOIN t_kelas k ON ks.kelas_id = k.id
    LEFT JOIN t_tahun_ajaran ta ON ks.tahun_ajaran_id = ta.id
    ORDER BY ks.id DESC
");
if (!$result) die("Ambil data gagal: " . mysqli_error($conn));

// Daftar Siswa: HANYA yang BELUM ditempatkan yang muncul
$siswa_list = mysqli_query($conn, "
    SELECT id, nama 
    FROM t_siswa 
    WHERE status_aktif = 1
      AND id NOT IN (
          SELECT DISTINCT siswa_id 
          FROM t_kelas_siswa
      )
    ORDER BY nama ASC
");

// ✅ DIUBAH: HANYA kelas yang AKTIF yang muncul di pilihan
$kelas_list = mysqli_query($conn, "SELECT id, nama FROM t_kelas WHERE status_aktif = 1 ORDER BY nama");

$tahun_list = mysqli_query($conn, "SELECT id, nama FROM t_tahun_ajaran WHERE status_aktif = 1 ORDER BY nama");

// Proses simpan / ubah
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'tambah';
    $id_edit = isset($_POST['id_edit']) ? (int)$_POST['id_edit'] : 0;
    $siswa_id = trim($_POST['siswa_id'] ?? '');
    $kelas_id = trim($_POST['kelas_id'] ?? '');
    $tahun_ajaran_id = trim($_POST['tahun_ajaran_id'] ?? '');

    if ($siswa_id && $kelas_id && $tahun_ajaran_id) {
        // Cek duplikasi
        $cek_stmt = mysqli_prepare($conn, "
            SELECT id FROM t_kelas_siswa 
            WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran_id = ? AND id != ?
        ");
        $cek_stmt->bind_param("iiii", $siswa_id, $kelas_id, $tahun_ajaran_id, $id_edit);
        $cek_stmt->execute();
        if (mysqli_num_rows($cek_stmt->get_result()) > 0) {
            $pesan = '<div class="alert alert-warning">Siswa sudah ditempatkan di kelas ini untuk tahun ajaran tersebut!</div>';
        } else {
            if ($mode === 'ubah' && $id_edit > 0) {
                $upd_stmt = mysqli_prepare($conn, "
                    UPDATE t_kelas_siswa 
                    SET siswa_id=?, kelas_id=?, tahun_ajaran_id=?
                    WHERE id=?
                ");
                $upd_stmt->bind_param("iiii", $siswa_id, $kelas_id, $tahun_ajaran_id, $id_edit);
                if ($upd_stmt->execute()) {
                    header("Location: kelola_penempatan_siswa.php");
                    exit;
                }
            } else {
                $ins_stmt = mysqli_prepare($conn, "
                    INSERT INTO t_kelas_siswa (siswa_id, kelas_id, tahun_ajaran_id) 
                    VALUES (?, ?, ?)
                ");
                $ins_stmt->bind_param("iii", $siswa_id, $kelas_id, $tahun_ajaran_id);
                if ($ins_stmt->execute()) {
                    header("Location: kelola_penempatan_siswa.php");
                    exit;
                }
            }
            $pesan = '<div class="alert alert-danger">Terjadi kesalahan saat menyimpan data!</div>';
        }
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $del_stmt = mysqli_prepare($conn, "DELETE FROM t_kelas_siswa WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
    }
    header("Location: kelola_penempatan_siswa.php");
    exit;
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['ubah'])) {
    $id_ubah = (int)$_GET['ubah'];
    $edt_stmt = mysqli_prepare($conn, "SELECT * FROM t_kelas_siswa WHERE id = ?");
    $edt_stmt->bind_param("i", $id_ubah);
    $edt_stmt->execute();
    $edit_data = $edt_stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Penempatan Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Kelola Penempatan Siswa ke Kelas</h2>

            <!-- Jumlah Data -->
            <div class="row mb-4">
                <div class="col-md-5 col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">JUMLAH PENEMPATAN</h5>
                            <h2 class="text-primary fw-bold"><?= $total ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Tambah / Ubah -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><?= $edit_data ? 'Ubah Penempatan' : 'Tambah Penempatan' ?></h5>
                    <?php if ($pesan) echo $pesan; ?>
                    
                    <form method="post" class="row g-3">
                        <input type="hidden" name="mode" value="<?= $edit_data ? 'ubah' : 'tambah' ?>">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <label class="form-label">Siswa</label>
                            <select name="siswa_id" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php 
                                mysqli_data_seek($siswa_list, 0);
                                while ($s = mysqli_fetch_assoc($siswa_list)): 
                                    $sel = ($edit_data && $edit_data['siswa_id'] == $s['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $s['id'] ?>" <?= $sel ?>>
                                    <?= e($s['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                                
                                <!-- Jika sedang UBAH, siswa yang sedang diedit tetap muncul -->
                                <?php if ($edit_data): ?>
                                    <?php
                                    $current_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, nama FROM t_siswa WHERE id = ".(int)$edit_data['siswa_id']));
                                    if ($current_siswa):
                                    ?>
                                    <option value="<?= $current_siswa['id'] ?>" selected>
                                        <?= e($current_siswa['nama']) ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php 
                                mysqli_data_seek($kelas_list, 0);
                                while ($k = mysqli_fetch_assoc($kelas_list)): 
                                    $sel = ($edit_data && $edit_data['kelas_id'] == $k['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $k['id'] ?>" <?= $sel ?>>
                                    <?= e($k['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                                
                                <!-- ✅ Jika UBAH, kelas yang sedang dipilih tetap muncul meskipun tidak aktif -->
                                <?php if ($edit_data): ?>
                                    <?php
                                    $current_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, nama FROM t_kelas WHERE id = ".(int)$edit_data['kelas_id']));
                                    if ($current_kelas):
                                    ?>
                                    <option value="<?= $current_kelas['id'] ?>" selected>
                                        <?= e($current_kelas['nama']) ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" class="form-select" required>
                                <option value="">-- Pilih Tahun --</option>
                                <?php 
                                mysqli_data_seek($tahun_list, 0);
                                while ($t = mysqli_fetch_assoc($tahun_list)): 
                                    $sel = ($edit_data && $edit_data['tahun_ajaran_id'] == $t['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $t['id'] ?>" <?= $sel ?>>
                                    <?= e($t['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <?php if ($edit_data): ?>
                                <a href="kelola_penempatan_siswa.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <?= $edit_data ? 'Simpan Perubahan' : 'Simpan Penempatan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Data dengan DataTables -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0 fw-semibold">Daftar Penempatan</h5>
                    </div>
                    <div class="table-responsive">
                        <table id="tabelPenempatan" class="table table-bordered table-striped table-hover align-middle w-100">
                            <thead class="table-primary text-white">
                                <tr>
                                    <th class="text-center" width="5%">No.</th>
                                    <th width="8%">ID</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th width="12%">Dibuat</th>
                                    <th width="12%">Diupdate</th>
                                    <th class="text-center" width="14%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): 
                                    $updated = !empty($row['updated_at']) && $row['updated_at'] !== '0000-00-00 00:00:00' 
                                        ? $row['updated_at'] 
                                        : ($row['created_at'] ?? '-');
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= e($row['id']) ?></td>
                                    <td><?= e($row['nama_siswa']) ?></td>
                                    <td><?= e($row['nama_kelas']) ?></td>
                                    <td><?= e($row['nama_tahun_ajaran']) ?></td>
                                    <td><?= e($row['created_at'] ?? '-') ?></td>
                                    <td><?= e($updated) ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="?ubah=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-primary me-1">Ubah</a>
                                        <a href="?hapus=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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
<?php include "footer.php"; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    $('#tabelPenempatan').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/id.json' },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, 'Semua']],
        order: [[1, 'desc']],
        columnDefs: [
            { targets: [0, 7], orderable: false, searchable: false },
            { targets: [0, 7], className: 'text-center' }
        ],
        autoWidth: false,
        layout: {
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        }
    });
});
</script>
</body>
</html>