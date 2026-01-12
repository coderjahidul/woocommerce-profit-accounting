<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle PDF Export request.
 */
add_action('admin_post_wppam_pdf', function () {
    if (!class_exists('Dompdf\Dompdf'))
        return;
    $year = isset($_GET['year']) ? intval($_GET['year']) : (int) date('Y');
    $html = '<h1>Yearly Profit Report (' . $year . ')</h1><table border="1" width="100%">';
    $html .= '<thead><tr><th>Month</th><th>Profit</th></tr></thead>';
    for ($m = 1; $m <= 12; $m++) {
        $data = wppam_calculate_profit($year, $m);
        $html .= '<tr><td>' . date('F', mktime(0, 0, 0, $m, 1)) . '</td><td>' . wc_price($data['profit']) . '</td></tr>';
    }
    $html .= '</table>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream('profit-report-' . $year . '.pdf');
    exit;
});
