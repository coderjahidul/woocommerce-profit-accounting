<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scripts and styles for the admin dashboard.
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'wppam') === false && strpos($hook, 'edit.php') === false && strpos($hook, 'wc-orders') === false) {
        if (get_post_type() !== 'shop_order' && !isset($_GET['page']) || $_GET['page'] !== 'wc-orders') {
            return;
        }
    }

    $is_order_page = (strpos($hook, 'edit.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') ||
        (strpos($hook, 'post.php') !== false && isset($_GET['post']) && get_post_type($_GET['post']) === 'shop_order') ||
        (strpos($hook, 'wc-orders') !== false);

    if (strpos($hook, 'wppam') === false && !$is_order_page) {
        return;
    }

    wp_enqueue_style('wppam-google-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap', [], null);
    wp_enqueue_style('wppam-admin-style', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-style.css', [], '2.0.0');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true);
});

// AJAX Handler for Delivery Stats
add_action('wp_ajax_wppam_get_delivery_stats', 'wppam_get_delivery_stats_ajax');
function wppam_get_delivery_stats_ajax()
{
    $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : 'this-month';
    $start_range = '';
    $end_range = date('Y-m-d');

    switch ($range) {
        case 'today':
            $start_range = date('Y-m-d');
            break;
        case 'yesterday':
            $start_range = date('Y-m-d', strtotime('-1 day'));
            $end_range = $start_range;
            break;
        case 'last-7-days':
            $start_range = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'last-30-days':
            $start_range = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'this-month':
            $start_range = date('Y-m-01');
            break;
        case 'last-month':
            $start_range = date('Y-m-01', strtotime('first day of last month'));
            $end_range = date('Y-m-t', strtotime('last day of last month'));
            break;
        default:
            $start_range = date('Y-m-01');
    }

    $statuses = ['completed', 'processing', 'on-hold', 'cancelled', 'refunded'];
    $status_counts = [];
    $total_orders = 0;
    foreach ($statuses as $status) {
        $count = count(wc_get_orders([
            'status' => $status,
            'limit' => -1,
            'return' => 'ids',
            'date_created' => $start_range . '...' . $end_range
        ]));
        $status_counts[] = $count;
        $total_orders += $count;
    }

    wp_send_json_success([
        'counts' => $status_counts,
        'total' => $total_orders,
        'labels' => ['Completed', 'Processing', 'On-hold', 'Cancelled', 'Refunded']
    ]);
}

/**
 * Add Chart.js initialization in the footer for the dashboard.
 */
add_action('admin_footer', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'wppam')
        return;
    $profits = [];
    $revenues = [];
    for ($m = 1; $m <= 12; $m++) {
        $data = wppam_calculate_profit(date('Y'), $m);
        $profits[] = round($data['profit'], 2);
        $revenues[] = round($data['revenue'], 2);
    }

    // Initial Data for Status Chart (This Month)
    $start_m = date('Y-m-01');
    $end_m = date('Y-m-t');
    $statuses = ['completed', 'processing', 'on-hold', 'cancelled', 'refunded'];
    $initial_counts = [];
    $initial_total = 0;
    foreach ($statuses as $st) {
        $c = count(wc_get_orders(['status' => $st, 'limit' => -1, 'return' => 'ids', 'date_created' => $start_m . '...' . $end_m]));
        $initial_counts[] = $c;
        $initial_total += $c;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Main Line Chart
            const lineCtx = document.getElementById('wppamChart');
            if (lineCtx) {
                new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Net Profit',
                            data: <?php echo json_encode($profits); ?>,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#6366f1'
                        }, {
                            label: 'Total Revenue',
                            data: <?php echo json_encode($revenues); ?>,
                            borderColor: '#22c55e',
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    font: { family: 'Outfit', size: 13 }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { borderDash: [2, 2] },
                                ticks: { font: { family: 'Outfit' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Outfit' } }
                            }
                        }
                    }
                });
            }

            // Order Status Pie Chart (AJAX)
            const statusCtxOriginal = document.getElementById('wppamStatusChart');
            const statusColors = ['#22c55e', '#6366f1', '#f59e0b', '#ef4444', '#94a3b8'];
            const statusLabels = ['Completed', 'Processing', 'On-hold', 'Cancelled', 'Refunded'];
            let statusChart = null;

            function updateStatusUI(counts, total) {
                if (statusChart) statusChart.destroy();
                const legendTarget = document.getElementById('wppamStatusLegend');
                const canvasContainer = document.querySelector('.wppam-status-canvas-container');
                const originalCtx = document.getElementById('wppamStatusChart');

                if (total > 0) {
                    canvasContainer.innerHTML = '<canvas id="wppamStatusChart"></canvas>';
                    const newCtx = document.getElementById('wppamStatusChart');
                    statusChart = new Chart(newCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                data: counts,
                                backgroundColor: statusColors,
                                borderWidth: 0,
                                cutout: '70%'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }
                        }
                    });

                    let html = '<div style="display:grid; grid-template-columns: 1fr; gap: 8px;">';
                    statusLabels.forEach((label, i) => {
                        const count = counts[i];
                        const percent = ((count / total) * 100).toFixed(1);
                        if (count > 0) {
                            html += `
                                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="width:8px; height:8px; border-radius:50%; background:${statusColors[i]};"></span>
                                        <span style="color:var(--wppam-text-muted); font-family: 'Outfit';">${label}</span>
                                    </div>
                                    <div style="font-weight:700; font-family: 'Outfit';">${count} <span style="font-weight:400; color:var(--wppam-text-muted); margin-left:4px;">(${percent}%)</span></div>
                                </div>
                            `;
                        }
                    });
                    html += '</div>';
                    legendTarget.innerHTML = html;
                } else {
                    canvasContainer.innerHTML = '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:var(--wppam-text-muted); font-family: \'Outfit\';">No orders in this range.</div>';
                    legendTarget.innerHTML = '';
                }
            }

            if (statusCtxOriginal) {
                updateStatusUI(<?php echo json_encode($initial_counts); ?>, <?php echo $initial_total; ?>);

                const filterSelect = document.getElementById('wppam-delivery-filter');
                if (filterSelect) {
                    filterSelect.addEventListener('change', function (e) {
                        const range = e.target.value;
                        const container = document.querySelector('.wppam-status-canvas-container');
                        container.style.opacity = '0.5';

                        fetch(ajaxurl + '?action=wppam_get_delivery_stats&range=' + range)
                            .then(res => res.json())
                            .then(response => {
                                if (response.success) {
                                    updateStatusUI(response.data.counts, response.data.total);
                                }
                                container.style.opacity = '1';
                            });
                    });
                }
            }
        });
    </script>
    <?php
});
