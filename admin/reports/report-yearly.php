<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display Yearly Report.
 */
function wppam_yearly_report()
{
    $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Yearly Profit Summary: <?php echo $year; ?></h1>
            <div class="wppam-actions">
                <form method="GET" style="display:inline-block;">
                    <input type="hidden" name="page" value="wppam-yearly">
                    <select name="year" onchange="this.form.submit()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php selected($year, $y); ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
                <a href="<?php echo admin_url('admin-post.php?action=wppam_csv&year=' . $year); ?>"
                    class="wppam-btn-secondary" style="font-size: 13px;">Export CSV</a>
            </div>
        </div>

        <div class="wppam-table-card">
            <table class="wppam-custom-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Expenses</th>
                        <th>Net Profit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $data = wppam_calculate_profit($year, $m);
                        if ($data['revenue'] == 0 && $data['expenses'] == 0)
                            continue;
                        ?>
                        <tr>
                            <td><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></td>
                            <td><?php echo wc_price($data['revenue']); ?></td>
                            <td><?php echo wc_price($data['cogs']); ?></td>
                            <td><?php echo wc_price($data['expenses']); ?></td>
                            <td
                                style="font-weight:700; color: <?php echo $data['profit'] >= 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>">
                                <?php echo wc_price($data['profit']); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wppam-monthly-details&year=' . $year . '&month=' . $m); ?>"
                                    class="wppam-btn-secondary" style="font-size: 11px; padding: 4px 10px;">View Details</a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Display Detailed Report for a specific month.
 */
function wppam_monthly_details_report()
{
    $year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('m');
    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT);
    $start_date = "$year-$month_str-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    // Get Orders for this month
    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $start_date . '...' . $end_date,
    ]);

    // Aggregate Revenue & COGS
    $product_stats = [];
    $total_cogs = 0;
    $total_revenue = 0;
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_variation_id() ?: $item->get_product_id();
            if (!isset($product_stats[$product_id])) {
                $cost = (float) get_post_meta($product_id, '_cost_price', true);
                if (!$cost && $item->get_variation_id()) {
                    $cost = (float) get_post_meta($item->get_product_id(), '_cost_price', true);
                }
                $product_stats[$product_id] = [
                    'name' => $item->get_name(),
                    'qty' => 0,
                    'cost' => $cost,
                    'total_cost' => 0,
                    'revenue' => 0
                ];
            }
            $qty = $item->get_quantity();
            $item_revenue = (float) $item->get_total();
            $line_cost = $product_stats[$product_id]['cost'] * $qty;

            $product_stats[$product_id]['qty'] += $qty;
            $product_stats[$product_id]['total_cost'] += $line_cost;
            $product_stats[$product_id]['revenue'] += $item_revenue;

            $total_cogs += $line_cost;
            $total_revenue += $item_revenue;
        }
    }

    // Get Expenses for this month aggregated by category
    global $wpdb;
    $expense_table = $wpdb->prefix . 'wppam_expenses';
    $expenses = $wpdb->get_results($wpdb->prepare("
        SELECT category, SUM(amount) as total_amount 
        FROM $expense_table 
        WHERE expense_date BETWEEN %s AND %s 
        GROUP BY category
    ", $start_date, $end_date));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Monthly Details: <?php echo date('F Y', strtotime($start_date)); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=wppam-yearly'); ?>" class="wppam-btn-primary">Back to Yearly
                Report</a>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- COGS Aggregation -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Product Cost
                    Aggregation (Monthly)</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Cost</th>
                            <th>Qty</th>
                            <th>Revenue</th>
                            <th class="wppam-text-danger">Total Cost</th>
                            <th class="wppam-text-success">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($product_stats): ?>
                            <?php foreach ($product_stats as $stat):
                                $profit = $stat['revenue'] - $stat['total_cost'];
                                ?>
                                <tr>
                                    <td><?php echo esc_html($stat['name']); ?></td>
                                    <td><?php echo wc_price($stat['cost']); ?></td>
                                    <td><?php echo $stat['qty']; ?> pcs</td>
                                    <td><?php echo wc_price($stat['revenue']); ?></td>
                                    <td class="wppam-text-danger"><?php echo wc_price($stat['total_cost']); ?></td>
                                    <td
                                        class="wppam-text-bold<?php echo $profit >= 0 ? ' wppam-text-success' : ' wppam-text-danger'; ?>">
                                        <?php echo wc_price($profit); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: 700;">Monthly Totals:</td>
                                <td style="font-weight: 700; color: var(--wppam-success);">
                                    <?php echo wc_price($total_revenue); ?></td>
                                <td style="font-weight: 700; color: var(--wppam-danger);"><?php echo wc_price($total_cogs); ?>
                                </td>
                                <td style="font-weight: 700; color: var(--wppam-primary);">
                                    <?php echo wc_price($total_revenue - $total_cogs); ?></td>
                            </tr>
                        </tfoot>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No sales data found for this month.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Expenses Aggregation -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Expenses Summary
                </h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses):
                            $total_exp = 0;
                            ?>
                            <?php foreach ($expenses as $exp):
                                $total_exp += $exp->total_amount;
                                ?>
                                <tr>
                                    <td><?php echo esc_html($exp->category); ?></td>
                                    <td style="font-weight:700; color: var(--wppam-danger);">
                                        <?php echo wc_price($exp->total_amount); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <tfoot>
                            <tr>
                                <td style="text-align: right; font-weight: 700;">Monthly Total:</td>
                                <td style="font-weight: 700;"><?php echo wc_price($total_exp); ?></td>
                            </tr>
                        </tfoot>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No expenses recorded.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
