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
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('wppamChart');
            if (!ctx) return;

            new Chart(ctx, {
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
        });
    </script>
    <?php
});
