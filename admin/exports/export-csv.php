<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle CSV Export request.
 */
add_action('admin_post_wppam_csv', function () {
    $year = isset($_GET['year']) ? intval($_GET['year']) : (int) date('Y');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=yearly-profit-' . $year . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Month', 'Revenue', 'COGS', 'Expenses', 'Profit']);
    for ($m = 1; $m <= 12; $m++) {
        $data = wppam_calculate_profit($year, $m);
        fputcsv($out, [
            date('F', mktime(0, 0, 0, $m, 1)),
            $data['revenue'],
            $data['cogs'],
            $data['expenses'],
            $data['profit']
        ]);
    }
    fclose($out);
    exit;
});
