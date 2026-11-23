<?php
// Chart.js charts for the report pages

const CHART_PALETTE = ['#2d6a4f', '#52b788', '#c9913d', '#1a3d2b', '#95d5b2', '#e8b96a', '#40916c', '#7c8f80'];

// Colour by meaning
function chart_semantic_color(string $label): ?string
{
    $l = strtolower($label);
    $map = [
        'not classified' => '#9aa59c', 'unknown' => '#9aa59c',
        'high' => '#b5294a', 'medium' => '#e09f3e', 'low' => '#2d9d5c',
        'pending' => '#e09f3e', 'approved' => '#2d9d5c', 'rejected' => '#b5294a',
        'myth' => '#b5294a', 'fact' => '#2d9d5c',
        'dhana potofu' => '#b5294a', 'ukweli' => '#2d9d5c',
        'juu' => '#b5294a', 'wastani' => '#e09f3e', 'chini' => '#2d9d5c',
    ];
    foreach ($map as $word => $color) {
        if (str_contains($l, $word)) {
            return $color;
        }
    }
    return null;
}

// Single-series chart: doughnut, pie, bar or hbar
function chart(string $type, array $rows, string $label_key, string $empty_text, array $opts = []): string
{
    $rows = array_values(array_filter($rows, fn($r) => (int)($r['n'] ?? 0) > 0));
    if (!$rows) {
        return '<p class="empty">' . htmlspecialchars($empty_text) . '</p>';
    }

    $labels = array_map(fn($r) => (string)$r[$label_key], $rows);
    $values = array_map(fn($r) => (int)$r['n'], $rows);
    $colors = [];
    foreach ($labels as $i => $label) {
        $colors[] = (!empty($opts['semantic']) ? chart_semantic_color($label) : null)
                    ?? CHART_PALETTE[$i % count(CHART_PALETTE)];
    }

    $config = [
        'type'     => $type,
        'labels'   => $labels,
        'datasets' => [['label' => $opts['series'] ?? 'Count', 'data' => $values, 'colors' => $colors]],
    ];
    return chart_box($config, $opts['height'] ?? 260, bar_chart($rows, $label_key, $empty_text));
}

// Multi-series line chart over time
function trend_chart(array $labels, array $series, string $empty_text, int $height = 280): string
{
    $total = 0;
    foreach ($series as $s) {
        $total += array_sum($s['data']);
    }
    if ($total === 0) {
        return '<p class="empty">' . htmlspecialchars($empty_text) . '</p>';
    }

    $datasets = [];
    foreach ($series as $i => $s) {
        $datasets[] = [
            'label'  => $s['label'],
            'data'   => array_values($s['data']),
            'colors' => [$s['color'] ?? CHART_PALETTE[$i % count(CHART_PALETTE)]],
        ];
    }
    return chart_box(['type' => 'line', 'labels' => array_values($labels), 'datasets' => $datasets], $height, '');
}

// Canvas with fallback
function chart_box(array $config, int $height, string $fallback): string
{
    $json = htmlspecialchars(json_encode($config, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    return '<div class="chart-box" style="height:' . $height . 'px">'
         . '<canvas data-chart="' . $json . '"></canvas>'
         . '<div class="chart-fallback">' . $fallback . '</div>'
         . '</div>';
}

// Time buckets for the selected period
function trend_buckets(int $days): array
{
    $keys = [];
    $labels = [];
    if ($days > 90) {
        for ($i = 11; $i >= 0; $i--) {
            $t = strtotime(date('Y-m-01') . " -$i months");
            $keys[] = date('Y-m', $t);
            $labels[] = date('M Y', $t);
        }
        return ['expr' => "DATE_FORMAT(created_at, '%Y-%m')", 'keys' => $keys, 'labels' => $labels];
    }
    for ($i = $days - 1; $i >= 0; $i--) {
        $t = strtotime("-$i days");
        $keys[] = date('Y-m-d', $t);
        $labels[] = date('d M', $t);
    }
    return ['expr' => 'DATE(created_at)', 'keys' => $keys, 'labels' => $labels];
}

// Counts per bucket for each group
function trend_fill(array $rows, array $keys, array $groups): array
{
    $out = [];
    foreach ($groups as $g) {
        $out[$g] = array_fill_keys($keys, 0);
    }
    foreach ($rows as $r) {
        if (isset($out[$r['g']]) && array_key_exists($r['k'], $out[$r['g']])) {
            $out[$r['g']][$r['k']] = (int)$r['n'];
        }
    }
    return $out;
}

// Chart.js library, styles and set-up
function chart_scripts(): string
{
    return <<<'HTML'
<style>
.chart-box { position: relative; width: 100%; }
.chart-box canvas { max-width: 100%; }
.chart-fallback { display: none; }
.chart-box.no-chart { height: auto !important; }
.chart-box.no-chart canvas { display: none; }
.chart-box.no-chart .chart-fallback { display: block; }
.panel.wide { grid-column: 1 / -1; }
.grid-2.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.grid-2.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media (max-width: 900px) {
    .grid-2.cols-2, .grid-2.cols-3 { grid-template-columns: minmax(0, 1fr); }
}
.panel .panel-note { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.6rem; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    var canvases = document.querySelectorAll('canvas[data-chart]');
    if (!canvases.length) return;

    // Fallback bars when the library is unavailable
    if (!window.Chart) {
        canvases.forEach(function (c) { c.parentNode.classList.add('no-chart'); });
        return;
    }

    var body  = getComputedStyle(document.body);
    var ink   = body.color || '#2c3e2d';
    var muted = body.getPropertyValue('--text-muted').trim() || '#6b7c6d';
    var grid  = 'rgba(128,128,128,0.15)';

    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = muted;

    // Total in the middle of doughnuts
    var centreTotal = {
        id: 'centreTotal',
        afterDraw: function (chart) {
            if (chart.config.type !== 'doughnut') return;
            var meta = chart.getDatasetMeta(0);
            if (!meta.data.length) return;
            var total = chart.data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
            var x = meta.data[0].x, y = meta.data[0].y, ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = ink;
            ctx.font = "700 22px 'Lora', serif";
            ctx.fillText(total, x, y - 7);
            ctx.fillStyle = muted;
            ctx.font = "500 11px 'Poppins', sans-serif";
            ctx.fillText('total', x, y + 13);
            ctx.restore();
        }
    };

    function alpha(hex, a) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    function build(cfg) {
        var type = cfg.type, round = type === 'doughnut' || type === 'pie';
        var datasets = cfg.datasets.map(function (d) {
            if (type === 'line') {
                return {
                    label: d.label, data: d.data,
                    borderColor: d.colors[0], backgroundColor: alpha(d.colors[0], 0.12),
                    fill: true, cubicInterpolationMode: 'monotone', borderWidth: 2.5,
                    pointRadius: d.data.length > 31 ? 0 : 3, pointHoverRadius: 5
                };
            }
            return {
                label: d.label, data: d.data,
                backgroundColor: d.colors, borderColor: round ? body.backgroundColor : d.colors,
                borderWidth: round ? 2 : 0, borderRadius: round ? 0 : 6,
                maxBarThickness: 38, hoverOffset: round ? 6 : 0
            };
        });

        var options = {
            responsive: true, maintainAspectRatio: false,
            animation: { duration: 700 },
            plugins: {
                legend: {
                    display: round || datasets.length > 1,
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, padding: 14 }
                },
                tooltip: { backgroundColor: '#1a3d2b', padding: 10, cornerRadius: 8 }
            }
        };

        if (round) {
            options.cutout = type === 'doughnut' ? '62%' : 0;
        } else if (type === 'line') {
            options.interaction = { mode: 'index', intersect: false };
            options.scales = {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0 } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
            };
        } else {
            var count = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } };
            var cats  = { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0 } };
            if (type === 'hbar') {
                options.indexAxis = 'y';
                options.scales = { x: count, y: cats };
            } else {
                options.scales = { x: cats, y: count };
            }
        }

        return {
            type: type === 'hbar' ? 'bar' : type,
            data: { labels: cfg.labels, datasets: datasets },
            options: options,
            plugins: [centreTotal]
        };
    }

    canvases.forEach(function (c) {
        try {
            new Chart(c, build(JSON.parse(c.getAttribute('data-chart'))));
        } catch (e) {
            c.parentNode.classList.add('no-chart');
        }
    });

    // Resize for printing
    window.addEventListener('beforeprint', function () {
        Object.values(Chart.instances).forEach(function (ch) { ch.resize(); });
    });
})();
</script>
HTML;
}
