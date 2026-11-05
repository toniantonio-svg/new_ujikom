<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "config.php";
require_once "cek_session.php";
cek_admin();

function e($val) {
    return htmlspecialchars((string)($val ?? '-'), ENT_QUOTES, 'UTF-8');
}

// === Total Siswa ===
$total_siswa = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS x FROM t_siswa"))['x'];

// === Data Siswa ===
$result = mysqli_query($conn, "SELECT * FROM t_siswa ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Data Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .table th, .table td { vertical-align: middle; white-space: nowrap; }
        .btn-sm { padding: 0.2rem 0.5rem; font-size: 0.75rem; }
        #tabelSiswa td:last-child { width: 160px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include "menu.php"; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Kelola Data Siswa</h2>

            <div class="row mb-4">
                <div class="col-md-5 col-lg-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="text-muted fs-6">JUMLAH DATA SISWA</h5>
                            <h2 class="text-primary fw-bold"><?= $total_siswa ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0 fw-semibold">Daftar Siswa</h5>
                        <a href="tambah_data_siswa.php" class="btn btn-success">+ Tambah Siswa</a>
                    </div>

                    <div class="table-responsive">
                        <table id="tabelSiswa" class="table table-bordered table-striped table-hover align-middle w-100">
                            <thead class="table-primary text-white">
                                <tr>
                                    <th class="text-center" width="5%">No.</th>
                                    <th class="text-center" width="5%">ID</th>
                                    <th width="10%">NIS</th>
                                    <th width="15%">Nama</th>
                                    <th width="10%">Jenis Kelamin</th>
                                    <th width="12%">Tanggal Lahir</th>
                                    <th width="18%">Alamat</th>
                                    <th class="text-center" width="10%">Status Aktif</th>
                                    <th width="12%">Dibuat</th>
                                    <th width="12%">Diupdate</th>
                                    <th class="text-center" width="14%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)):
                                    $updated = $row['updated_at'] ?? '';
                                    if (empty($updated) || $updated === '0000-00-00 00:00:00') {
                                        $updated = $row['created_at'] ?? '-';
                                    }
                                    $status_aktif = (int)($row['status_aktif'] ?? 1);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center"><?= e($row['id']) ?></td>
                                    <td><?= e($row['nis']) ?></td>
                                    <td><?= e($row['nama']) ?></td>
                                    <td><?= e($row['jenis_kelamin']) ?></td>
                                    <td><?= e($row['tanggal_lahir']) ?></td>
                                    <td><?= e($row['alamat']) ?></td>
                                    <td class="text-center">
                                        <?php if ($status_aktif === 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($row['created_at']) ?></td>
                                    <td><?= e($updated) ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="edit_siswa.php?id=<?= urlencode((string)$row['id']) ?>"
                                           class="btn btn-sm btn-primary me-1">Ubah</a>
                                        <a href="hapus_siswa.php?id=<?= urlencode((string)$row['id']) ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin ingin menghapus data siswa ini?')">Hapus</a>
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
$(function () {
    $('#tabelSiswa').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.3.4/i18n/id.json' },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, 'Semua']],
        order: [[1, 'asc']],
        columnDefs: [
            { targets: [0, 10], orderable: false, searchable: false },
            { targets: [0, 1, 7, 10], className: 'text-center' }
        ],
        autoWidth: false
    });
});
</script>
</body>
</html>