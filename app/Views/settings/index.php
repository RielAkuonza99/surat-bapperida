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

<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>

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
    <section class="settings-section" id="developer-tools" data-devtools-live data-metrics-url="<?= e(url('pengaturan/developer-tools/metrics')) ?>" aria-labelledby="system-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="system-heading">Developer tools</h3>
                <p>Status teknis untuk admin. Fokus pada performa aplikasi dan integrasi internal.</p>
            </div>
            <span class="sample-window" data-live-updated role="status">Memuat data terkini...</span>
        </div>

        <div class="system-readings">
            <div class="system-reading">
                <span>Memori PHP</span>
                <strong data-live-metric="php_memory_bytes" data-live-format="bytes"><?= e($formatBytes((int) $current['php_memory_bytes'])) ?></strong>
            </div>
            <div class="system-reading">
                <span>Latency database</span>
                <strong data-live-metric="database_latency_ms" data-live-format="milliseconds"><?= e(number_format((float) $current['database_latency_ms'], 2, ',', '.')) ?> ms</strong>
            </div>
            <div class="system-reading">
                <span>Ruang aplikasi</span>
                <strong data-live-metric="disk_free_bytes" data-live-format="bytes"><?= e($formatBytes($current['disk_free_bytes'])) ?></strong>
                <small data-live-disk-used><?= $diskUsedPercent === null ? 'Kapasitas volume tidak tersedia.' : e($diskUsedPercent . '% terpakai.') ?></small>
            </div>
            <div class="system-reading">
                <span>Runtime</span>
                <strong>PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></strong>
                <small><?= e(APP_ENVIRONMENT) ?> mode</small>
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
                <h3 id="network-heading">Koneksi dan jaringan</h3>
                <p>Statistik koneksi browser saat ini. Nilainya berubah mengikuti kondisi perangkat.</p>
            </div>
        </div>
        <dl class="network-readings">
            <div><dt>Koneksi perangkat</dt><dd data-network-state role="status">Memeriksa koneksi…</dd></div>
            <div><dt>Jenis koneksi browser</dt><dd data-network-type>Tidak tersedia di browser ini</dd></div>
            <div><dt>Perkiraan RTT browser</dt><dd data-network-rtt>Tidak tersedia</dd></div>
        </dl>
        <p class="settings-note">Jenis koneksi dan RTT merupakan estimasi browser dan tidak dikirim ke server.</p>
    </section>

    <section class="settings-section" aria-labelledby="api-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="api-heading">Integrasi dan sinkronisasi</h3>
                <p>Status antrean dan koneksi eksternal untuk pemeliharaan arsip dan Google Drive.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('sinkronisasi')) ?>">Buka antrean</a>
        </div>

        <dl class="api-queue-readings">
            <div><dt>Menunggu</dt><dd data-live-metric="queue_PENDING"><?= (int) $metrics['queue']['PENDING'] ?></dd></div>
            <div><dt>Diproses</dt><dd data-live-metric="queue_PROCESSING"><?= (int) $metrics['queue']['PROCESSING'] ?></dd></div>
            <div><dt>Terkonfirmasi</dt><dd data-live-metric="queue_CONFIRMED"><?= (int) $metrics['queue']['CONFIRMED'] ?></dd></div>
            <div><dt>Gagal</dt><dd data-live-metric="queue_FAILED"><?= (int) $metrics['queue']['FAILED'] ?></dd></div>
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

<?php if ($isAdmin): ?>
    <section class="settings-section settings-log-tools" id="developer-tools-logs" aria-labelledby="logs-heading">
        <div class="settings-section-heading">
            <div>
                <h3 id="logs-heading">Log aplikasi</h3>
                <p>Bersihkan file error teknis. Change log dan riwayat sesi tidak ikut dihapus.</p>
            </div>
            <span class="sample-window"><?= e($formatBytes($appLogBytes)) ?></span>
        </div>
        <form method="post" action="<?= e(url('pengaturan/developer-tools/logs/clear')) ?>" data-confirm-clear-app-log>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button class="btn btn-outline-danger" type="submit">Bersihkan app.log</button>
        </form>
    </section>
<?php endif; ?>

<?php require APP_ROOT . '/app/Views/security/activity-content.php'; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
