<?php

if (!defined('INDEX_AUTH')) {
    die("can not access this file directly");
} elseif (INDEX_AUTH != 1) {
    die("can not access this file directly");
}

require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';

$opac->page_title = __('Statistik Peminjaman');

$period = $_GET['period'] ?? 'week';
if (!in_array($period, ['week', 'month', 'year'], true)) {
    $period = 'week';
}

$bulan_pendek = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$hari_pendek = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

$chart_labels = [];
$bucket_keys = [];

if ($period === 'month') {
    $range_start = date('Y-m-01');
    $range_end = date('Y-m-t');
    $days_in_month = (int) date('t');
    for ($d = 1; $d <= $days_in_month; $d++) {
        $chart_labels[] = (string) $d;
        $bucket_keys[] = date('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
    }
    $group_expr = 'DATE(l.loan_date)';
} elseif ($period === 'year') {
    $range_start = date('Y-01-01');
    $range_end = date('Y-12-31');
    for ($m = 1; $m <= 12; $m++) {
        $chart_labels[] = $bulan_pendek[$m - 1];
        $bucket_keys[] = date('Y-') . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }
    $group_expr = "DATE_FORMAT(l.loan_date, '%Y-%m')";
} else {
    $dow = (int) date('N');
    $mondayTs = strtotime('-' . ($dow - 1) . ' days');
    $range_start = date('Y-m-d', $mondayTs);
    $range_end = date('Y-m-d', strtotime('+6 days', $mondayTs));
    for ($i = 0; $i < 7; $i++) {
        $ts = strtotime("+{$i} days", $mondayTs);
        $chart_labels[] = $hari_pendek[$i];
        $bucket_keys[] = date('Y-m-d', $ts);
    }
    $group_expr = 'DATE(l.loan_date)';
}

$trend_q = $dbs->query("SELECT {$group_expr} AS bucket, COUNT(*) AS c
    FROM loan AS l
    WHERE l.loan_date BETWEEN '{$range_start}' AND '{$range_end}'
    GROUP BY bucket");
$counts_by_bucket = [];
while ($r = $trend_q->fetch_assoc()) {
    $counts_by_bucket[$r['bucket']] = (int) $r['c'];
}
$trend_values = [];
foreach ($bucket_keys as $k) {
    $trend_values[] = $counts_by_bucket[$k] ?? 0;
}

$top_q = $dbs->query("SELECT m.member_name, COUNT(*) AS c
    FROM loan AS l LEFT JOIN member AS m ON l.member_id = m.member_id
    WHERE l.loan_date BETWEEN '{$range_start}' AND '{$range_end}'
    GROUP BY l.member_id
    ORDER BY c DESC
    LIMIT 10");
$top_labels = [];
$top_values = [];
while ($r = $top_q->fetch_assoc()) {
    $top_labels[] = $r['member_name'] ?? __('Tidak diketahui');
    $top_values[] = (int) $r['c'];
}


$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$keywords = trim($_GET['keywords'] ?? '');
$where = '';
if ($keywords !== '') {
    $kw = $dbs->escape_string($keywords);
    $where = " WHERE (m.member_name LIKE '%{$kw}%' OR m.inst_name LIKE '%{$kw}%' OR b.title LIKE '%{$kw}%') ";
}

$table_spec = 'loan AS l
    LEFT JOIN member AS m ON l.member_id = m.member_id
    LEFT JOIN item AS i ON l.item_code = i.item_code
    LEFT JOIN biblio AS b ON i.biblio_id = b.biblio_id';

$count_q = $dbs->query("SELECT COUNT(*) FROM {$table_spec} {$where}");
$total = (int) $count_q->fetch_row()[0];

$data_q = $dbs->query("SELECT m.member_name, m.inst_name, b.title, l.loan_date, l.due_date, l.is_return, l.return_date
    FROM {$table_spec}
    {$where}
    ORDER BY l.loan_date DESC
    LIMIT {$offset}, {$per_page}");
?>
<div class="per_title">
    <h2><?php echo __('Statistik Peminjaman'); ?></h2>
</div>
<div class="sub_section">
    <div class="btn-group mb-3" role="group" aria-label="<?php echo __('Pilih Periode'); ?>">
        <a href="index.php?p=loan_activity&period=week" class="btn btn-<?php echo $period === 'week' ? 'primary' : 'outline-primary'; ?>"><?php echo __('Minggu Ini'); ?></a>
        <a href="index.php?p=loan_activity&period=month" class="btn btn-<?php echo $period === 'month' ? 'primary' : 'outline-primary'; ?>"><?php echo __('Bulan Ini'); ?></a>
        <a href="index.php?p=loan_activity&period=year" class="btn btn-<?php echo $period === 'year' ? 'primary' : 'outline-primary'; ?>"><?php echo __('Tahun Ini'); ?></a>
    </div>

    <div class="row">
        <div class="col-md-7 mb-4">
            <h5><?php echo __('Tren Peminjaman'); ?></h5>
            <?php if (array_sum($trend_values) > 0): ?>
                <canvas id="loanTrendChart" height="200" role="img" aria-label="<?php echo __('Grafik jumlah peminjaman per periode'); ?>"></canvas>
            <?php else: ?>
                <div class="alert alert-secondary"><?php echo __('Belum ada peminjaman pada periode ini'); ?></div>
            <?php endif; ?>
        </div>
        <div class="col-md-5 mb-4">
            <h5><?php echo __('Top 10 Pembaca'); ?></h5>
            <?php if (count($top_values) > 0): ?>
                <canvas id="topReadersChart" height="<?php echo max(200, count($top_values) * 35); ?>" role="img" aria-label="<?php echo __('Grafik peringkat anggota paling aktif meminjam buku'); ?>"></canvas>
            <?php else: ?>
                <div class="alert alert-secondary"><?php echo __('Belum ada pembaca pada periode ini'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo JWB . 'chartjs/chart-2.9.3.min.js'; ?>"></script>
    <script>
        (function() {
            var trendCanvas = document.getElementById('loanTrendChart');
            if (trendCanvas) {
                new Chart(trendCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: <?php echo json_encode(__('Jumlah Peminjaman')); ?>,
                            data: <?php echo json_encode($trend_values); ?>,
                            backgroundColor: '#007bff'
                        }]
                    },
                    options: {
                        legend: {
                            display: false
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0
                                },
                                gridLines: {
                                    color: '#eee'
                                }
                            }],
                            xAxes: [{
                                gridLines: {
                                    display: false
                                }
                            }]
                        }
                    }
                });
            }

            var topCanvas = document.getElementById('topReadersChart');
            if (topCanvas) {
                new Chart(topCanvas.getContext('2d'), {
                    type: 'horizontalBar',
                    data: {
                        labels: <?php echo json_encode($top_labels); ?>,
                        datasets: [{
                            label: <?php echo json_encode(__('Jumlah Buku Dipinjam')); ?>,
                            data: <?php echo json_encode($top_values); ?>,
                            backgroundColor: '#007bff'
                        }]
                    },
                    options: {
                        legend: {
                            display: false
                        },
                        scales: {
                            xAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }]
                        }
                    }
                });
            }
        })();
    </script>

    <hr class="my-4" />

    <h5><?php echo __('Detail Transaksi Peminjaman'); ?></h5>
    <form method="get" action="index.php" class="form-inline mb-3">
        <input type="hidden" name="p" value="loan_activity" />
        <input type="text" name="keywords" class="form-control" placeholder="<?php echo __('Cari nama, jurusan, atau judul buku'); ?>" value="<?php echo htmlspecialchars($keywords); ?>" />
        <input type="submit" class="btn btn-primary ml-2" value="<?php echo __('Cari'); ?>" />
    </form>

    <div class="alert alert-info"><?php echo sprintf(__('Ditemukan %d transaksi peminjaman'), $total); ?></div>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th><?php echo __('Nama Anggota'); ?></th>
                <th><?php echo __('Jurusan/Institusi'); ?></th>
                <th><?php echo __('Judul Buku'); ?></th>
                <th><?php echo __('Tanggal Pinjam'); ?></th>
                <th><?php echo __('Tanggal Kembali'); ?></th>
                <th><?php echo __('Status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total > 0): ?>
                <?php while ($row = $data_q->fetch_assoc()): ?>
                    <?php
                    if ($row['is_return']) {
                        $status = '<span class="badge badge-success">' . __('Sudah Dikembalikan') . '</span>';
                    } elseif ($row['due_date'] < date('Y-m-d')) {
                        $status = '<span class="badge badge-danger">' . __('Terlambat') . '</span>';
                    } else {
                        $status = '<span class="badge badge-warning">' . __('Dipinjam') . '</span>';
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['member_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['inst_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['title'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['loan_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center"><?php echo __('Belum ada data peminjaman'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php echo simbio_paging::paging($total, $per_page, 5); ?>
</div>