<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display Daily Report.
 */
function wppam_daily_report()
{
    $year = date('Y');
    $month = date('m');
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Daily Profit Report: <?php echo date('F Y'); ?></h1>
        </div>

        <div class="wppam-table-card">
            <table class="wppam-custom-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Revenue</th>
                        <th>COGS</th>
                        <th>Expenses</th>
                        <th>Net Profit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($d = $days_in_month; $d >= 1; $d--) {
                        $date = "$year-$month-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                        $data = wppam_calculate_profit_for_range($date, $date);
                        if ($data['revenue'] == 0 && $data['expenses'] == 0) continue;
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($date)); ?></td>
                            <td><?php echo wc_price($data['revenue']); ?></td>
                            <td><?php echo wc_price($data['cogs']); ?></td>
                            <td><?php echo wc_price($data['expenses']); ?></td>
                            <td style="font-weight:700; color: <?php echo $data['profit'] >= 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>">
                                <?php echo wc_price($data['profit']); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wppam-daily-details&date=' . $date); ?>" 
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
 * Display Detailed Report for a specific day.
 */
function wppam_daily_details_report()
{
    $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

    // Get Orders for this day
    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $date . '...' . $date,
    ]);

    // Get Expenses for this day
    global $wpdb;
    $expense_table = $wpdb->prefix . 'wppam_expenses';
    $expenses = $wpdb->get_results($wpdb->prepare("SELECT * FROM $expense_table WHERE expense_date = %s", $date));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Daily Details: <?php echo date('M d, Y', strtotime($date)); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=wppam-daily'); ?>" class="wppam-btn-primary">Back to Daily Report</a>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Orders Selection -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Orders Received</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Revenue</th>
                            <th style="color: var(--wppam-danger);">COGS</th>
                            <th style="color: var(--wppam-success);">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): ?>
                            <?php foreach ($orders as $order): 
                                $data = wppam_get_order_profit_data($order);
                            ?>
                                <tr>
                                    <td><a href="<?php echo get_edit_post_link($order->get_id()); ?>">#<?php echo $order->get_id(); ?></a></td>
                                    <td><?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?></td>
                                    <td><?php echo $order->get_item_count(); ?> pcs</td>
                                    <td><?php echo wc_price($order->get_total()); ?></td>
                                    <td style="color: var(--wppam-danger);"><?php echo wc_price($data['cogs']); ?></td>
                                    <td style="font-weight:700; color: <?php echo $data['profit'] >= 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>">
                                        <?php echo wc_price($data['profit']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No orders found for this day.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Expenses selection -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Expenses Incurred</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses): 
                            $total_exp = 0;
                        ?>
                            <?php foreach ($expenses as $exp): 
                                $total_exp += $exp->amount;
                            ?>
                                <tr>
                                    <td><?php echo esc_html($exp->category); ?><br><small><?php echo esc_html($exp->description); ?></small></td>
                                    <td style="font-weight:700; color: var(--wppam-danger);"><?php echo wc_price($exp->amount); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tfoot>
                                <tr>
                                    <td style="text-align: right; font-weight: 700;">Total:</td>
                                    <td style="font-weight: 700;"><?php echo wc_price($total_exp); ?></td>
                                </tr>
                            </tfoot>
                        <?php else: ?>
                            <tr><td colspan="2">No expenses recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
