<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';

$profileId = (int) ($profile['id'] ?? 0);
$profileUsername = (string) ($profile['username'] ?? '');
$profileRole = (string) ($profile['role'] ?? '');
$formatBytes = static function (?int $bytes): string {
    if ($bytes === null) {
        return 'Tidak tersedia';
    }
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
    $value = (float) $bytes;
    $unit = 0;
    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }

    return number_format($value, $unit === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unit];
};
$chart = static function (string $title, string $unit, array $history, string $key, bool $bytes = false) use ($formatBytes): void {
    $values = array_map(
        static fn (array $sample): float => (float) $sample[$key],
        $history
    );
    $label = $bytes ? 'memori proses PHP' : $title;
    ?>
    <figure class="metric-chart">
        <figcaption>
            <span><?= e($title) ?></span>
            <span class="metric-chart-unit"><?= e($unit) ?></span>
        </figcaption>
        <?php if (count($values) < 2): ?>
            <p class="metric-chart-empty">Grafik tren muncul setelah ada sampel pada waktu yang berbeda. Sampel dicatat maksimal setiap lima menit saat admin membuka halaman ini.</p>
        <?php else:
            $width = 600;
            $height = 176;
            $left = 54;
            $right = 14;
            $top = 16;
            $bottom = 28;
            $plotWidth = $width - $left - $right;
            $plotHeight = $height - $top - $bottom;
            $minimum = min($values);
            $maximum = max($values);
            $range = max(1.0, $maximum - $minimum);
            $padding = $range * 0.12;
            $minimum -= $padding;
            $maximum += $padding;
            $range = $maximum - $minimum;
            $points = [];
            foreach ($values as $index => $value) {
                $x = $left + (count($values) === 1 ? 0 : $index * $plotWidth / (count($values) - 1));
                $y = $top + ($maximum - $value) * $plotHeight / $range;
                $points[] = number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
            }
            $latestValue = $values[array_key_last($values)];
            $displayValue = $bytes
                ? $formatBytes((int) $latestValue)
                : number_format($latestValue, 2, ',', '.') . ' ' . $unit;
            ?>
            <svg class="metric-chart-svg" viewBox="0 0 <?= $width ?> <?= $height ?>" role="img" aria-label="<?= e($title . ', sampel terbaru ' . $displayValue) ?>">
                <title><?= e($title) ?></title>
                <desc>Grafik garis dari <?= count($values) ?> sampel yang dicatat pada 24 jam terakhir.</desc>
                <?php for ($line = 0; $line <= 3; $line++):
                    $y = $top + $line * $plotHeight / 3;
                    $tick = $maximum - $line * $range / 3;
                    $tickLabel = $bytes
                        ? $formatBytes((int) $tick)
                        : number_format($tick, 0, ',', '.') . ' ' . $unit;
                    ?>
                    <line x1="<?= $left ?>" y1="<?= number_format($y, 1, '.', '') ?>" x2="<?= $width - $right ?>" y2="<?= number_format($y, 1, '.', '') ?>" class="metric-gridline" />
                    <text x="<?= $left - 8 ?>" y="<?= number_format($y + 4, 1, '.', '') ?>" text-anchor="end" class="metric-axis-label"><?= e($tickLabel) ?></text>
                <?php endfor; ?>
                <polyline points="<?= e(implode(' ', $points)) ?>" class="metric-line" />
                <?php foreach ($points as $point):
                    [$x, $y] = explode(',', $point);
                    ?>
                    <circle cx="<?= e($x) ?>" cy="<?= e($y) ?>" r="3.5" class="metric-point" />
                <?php endforeach; ?>
                <text x="<?= $left ?>" y="<?= $height - 5 ?>" class="metric-axis-label"><?= e(date('H:i', strtotime((string) $history[0]['sampled_at'] . ' UTC'))) ?></text>
                <text x="<?= $width - $right ?>" y="<?= $height - 5 ?>" text-anchor="end" class="metric-axis-label"><?= e(date('H:i', strtotime((string) $history[array_key_last($history)]['sampled_at'] . ' UTC'))) ?></text>
            </svg>
        <?php endif; ?>
    </figure>
    <?php
};
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Pengaturan</h2>
        <p class="page-sub">Profil akun dan informasi penggunaan aplikasi.</p>
    </div>
</div>

<section class="settings-profile" aria-labelledby="profile-heading">
    <div class="settings-profile-heading">
        <div class="profile-mark" aria-hidden="true"><?= e(strtoupper(mb_substr($profileUsername, 0, 1))) ?></div>
        <div>
            <h3 id="profile-heading">Profil pengguna</h3>
            <p>Identitas akun yang sedang digunakan.</p>
        </div>
    </div>
    <dl class="profile-details">
        <div><dt>Username</dt><dd><?= e($profileUsername) ?></dd></div>
        <div><dt>Role</dt><dd><?= e(ucfirst($profileRole)) ?></dd></div>
        <div><dt>ID pengguna</dt><dd class="profile-id">#<?= $profileId ?></dd></div>
    </dl>
</section>

<?php if ($isAdmin && is_array($metrics)): ?>
    <?php
    $current = $metrics['current'];
    $diskTotal = $current['disk_total_bytes'];
    $diskFree = $current['disk_free_bytes'];
    $diskUsedPercent = $diskTotal && $diskFree !== null
        ? max(0, min(100, (int) round((1 - $diskFree / $diskTotal) * 100)))
        : null;
    ?>
    <section class="settings-section" aria-labelledby="system-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="system-heading">Performa sistem</h3>
                <p>Snapshot lokal aplikasi dan database. Bukan pemantauan server secara menyeluruh.</p>
            </div>
            <span class="sample-window">Sampel 24 jam, retensi 30 hari</span>
        </div>

        <div class="system-readings">
            <div class="system-reading">
                <span>Memori proses PHP saat ini</span>
                <strong><?= e($formatBytes((int) $current['php_memory_bytes'])) ?></strong>
            </div>
            <div class="system-reading">
                <span>Respons database</span>
                <strong><?= e(number_format((float) $current['database_latency_ms'], 2, ',', '.')) ?> ms</strong>
            </div>
            <div class="system-reading">
                <span>Ruang kosong pada volume aplikasi</span>
                <strong><?= e($formatBytes($current['disk_free_bytes'])) ?></strong>
                <small><?= $diskUsedPercent === null ? 'Kapasitas volume tidak tersedia.' : e($diskUsedPercent . '% terpakai pada volume ini.') ?></small>
            </div>
            <div class="system-reading">
                <span>Runtime</span>
                <strong>PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></strong>
                <small><?= e(APP_ENVIRONMENT) ?> environment</small>
            </div>
        </div>

        <div class="settings-chart-grid">
            <?php $chart('Memori proses PHP', 'bytes', $metrics['history'], 'php_memory_bytes', true); ?>
            <?php $chart('Respons database', 'ms', $metrics['history'], 'database_latency_ms'); ?>
        </div>
    </section>

    <section class="settings-section" aria-labelledby="network-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="network-heading">Analisis server dan jaringan</h3>
                <p>Waktu respons MySQL diukur dari aplikasi. Kualitas koneksi perangkat berasal dari informasi browser bila tersedia.</p>
            </div>
        </div>
        <dl class="network-readings">
            <div><dt>Koneksi perangkat</dt><dd data-network-state role="status">Memeriksa koneksi…</dd></div>
            <div><dt>Jenis koneksi browser</dt><dd data-network-type>Tidak tersedia di browser ini</dd></div>
            <div><dt>Perkiraan RTT browser</dt><dd data-network-rtt>Tidak tersedia</dd></div>
            <div><dt>Respons query MySQL</dt><dd><?= e(number_format((float) $current['database_latency_ms'], 2, ',', '.')) ?> ms</dd></div>
        </dl>
        <p class="settings-note">RTT dan jenis koneksi browser merupakan perkiraan perangkat, tidak dikirim ke server. Tidak ada ping provider eksternal.</p>
    </section>

    <section class="settings-section" aria-labelledby="api-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="api-heading">Performa API dan sinkronisasi</h3>
                <p>Status berasal dari antrean Supabase dan pemeriksaan Google Drive yang sudah dilakukan admin.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('sinkronisasi')) ?>">Buka antrean sinkronisasi</a>
        </div>

        <dl class="api-queue-readings">
            <div><dt>Menunggu</dt><dd><?= (int) $metrics['queue']['PENDING'] ?></dd></div>
            <div><dt>Diproses</dt><dd><?= (int) $metrics['queue']['PROCESSING'] ?></dd></div>
            <div><dt>Terkonfirmasi</dt><dd><?= (int) $metrics['queue']['CONFIRMED'] ?></dd></div>
            <div><dt>Gagal</dt><dd><?= (int) $metrics['queue']['FAILED'] ?></dd></div>
        </dl>

        <?php if ($metrics['drive_apis']): ?>
            <div class="table-responsive settings-api-table">
                <table class="table">
                    <thead><tr><th>API</th><th>Status</th><th>Kesehatan</th><th>Pemeriksaan terakhir</th><th>Terakhir digunakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($metrics['drive_apis'] as $api): ?>
                        <tr>
                            <td><?= e($api['name']) ?></td>
                            <td><?= (int) $api['is_enabled'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                            <td><?= e(ucfirst((string) $api['health_status'])) ?></td>
                            <td><?= e($api['last_health_check'] ?: 'Belum diperiksa') ?></td>
                            <td><?= e($api['last_used_at'] ?: 'Belum digunakan') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="settings-empty">Konfigurasi Google Drive belum tersedia. Admin dapat mengaturnya pada halaman Google Drive API.</p>
        <?php endif; ?>
    </section>
<?php elseif (!$isAdmin): ?>
    <p class="settings-note">Informasi performa sistem dan status API hanya tersedia untuk administrator.</p>
<?php elseif ($metricsUnavailable): ?>
    <div class="settings-error-state" role="alert">
        <strong>Metrik sistem belum tersedia</strong>
        <p>Profil tetap dapat digunakan. Periksa koneksi MySQL dan pastikan migrasi 006 sudah diterapkan, lalu muat ulang halaman ini.</p>
    </div>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
