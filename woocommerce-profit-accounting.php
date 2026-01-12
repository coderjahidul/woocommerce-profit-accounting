<?php
/**
 * Plugin Name: WooCommerce Profit & Accounting Manager
 * Plugin URI:  https://github.com/coderjahidul/woocommerce-profit-accounting
 * Description: Complete Profit, Loss, COGS, Expenses, Monthly/Yearly Reports, Charts, CSV & PDF Export for WooCommerce.
 * Version:     1.1.0
 * Author:      MD JAHIDUL ISLAM SABUZ
 * Author URI:  https://github.com/coderjahidul
 * Text Domain: woocommerce-profit-accounting
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH'))
    exit;

// ============================
// ASSETS & ENQUEUE
// ============================
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'wppam') === false && strpos($hook, 'edit.php') === false && strpos($hook, 'wc-orders') === false) {
        if (get_post_type() !== 'shop_order' && !isset($_GET['page']) || $_GET['page'] !== 'wc-orders') {
            return;
        }
    }
    
    // Check if it's the orders list or single order page
    $is_order_page = (strpos($hook, 'edit.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') || 
                     (strpos($hook, 'post.php') !== false && isset($_GET['post']) && get_post_type($_GET['post']) === 'shop_order') ||
                     (strpos($hook, 'wc-orders') !== false);

    if (strpos($hook, 'wppam') === false && !$is_order_page) {
        return;
    }

    wp_enqueue_style('wppam-google-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap', [], null);
    wp_enqueue_style('wppam-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], '1.0.0');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true);
});

register_activation_hook(__FILE__, 'wppam_create_tables');
function wppam_create_tables()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        category VARCHAR(100),
        amount DECIMAL(10,2),
        expense_date DATE
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// ============================
// PRODUCT COST (COGS)
// ============================
add_action('woocommerce_product_options_pricing', function () {
    woocommerce_wp_text_input([
        'id' => '_cost_price',
        'label' => 'Product Cost (COGS)',
        'placeholder' => 'Enter product cost',
        'type' => 'number',
        'custom_attributes' => ['step' => 'any']
    ]);
});

// Add Cost field to Variations
add_action('woocommerce_product_after_variable_attributes', function ($loop, $variation_data, $variation) {
    woocommerce_wp_text_input([
        'id' => '_cost_price[' . $loop . ']',
        'label' => 'Product Cost (COGS)',
        'placeholder' => 'Enter product cost',
        'value' => get_post_meta($variation->ID, '_cost_price', true),
        'type' => 'number',
        'custom_attributes' => ['step' => 'any'],
        'wrapper_class' => 'form-row form-row-full',
    ]);
}, 10, 3);


add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_cost_price']) && !is_array($_POST['_cost_price'])) {
        $product->update_meta_data('_cost_price', wc_clean($_POST['_cost_price']));
    }
});

// Save Cost field for Variations
add_action('woocommerce_save_product_variation', function ($variation_id, $i) {
    if (isset($_POST['_cost_price'][$i])) {
        update_post_meta($variation_id, '_cost_price', wc_clean($_POST['_cost_price'][$i]));
    }
}, 10, 2);


// ============================
// REVENUE
// ============================
/**
 * Calculate total revenue for a specific year and month.
 * 
 * @param int $year
 * @param int $month
 * @return float
 */
function wppam_get_monthly_revenue($year, $month)
{
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $start_date . '...' . $end_date,
    ]);

    $total_revenue = 0;
    foreach ($orders as $order) {
        $total_revenue += (float) $order->get_total();
    }
    return $total_revenue;
}

// ============================
// COGS CALCULATION
// ============================
/**
 * Calculate total Cost of Goods Sold (COGS) for a specific year and month.
 * 
 * @param int $year
 * @param int $month
 * @return float
 */
function wppam_get_monthly_cogs($year, $month)
{
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $start_date . '...' . $end_date,
    ]);

    $total_cogs = 0;
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $cost = get_post_meta($product_id, '_cost_price', true);
            if (!$cost && $item->get_variation_id()) {
                // Fallback to parent cost if variation doesn't have it
                $cost = get_post_meta($item->get_product_id(), '_cost_price', true);
            }
            $total_cogs += ((float) $cost * $item->get_quantity());
        }
    }
    return $total_cogs;
}

// ============================
// ORDER PROFIT HELPERS
// ============================
/**
 * Calculate profit data for a single order.
 * 
 * @param WC_Order|int $order
 * @return array
 */
function wppam_get_order_profit_data($order)
{
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order);
    }
    if (!$order) {
        return ['revenue' => 0, 'cogs' => 0, 'profit' => 0];
    }

    $revenue = (float) $order->get_total();
    $cogs = 0;
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $cost = get_post_meta($product_id, '_cost_price', true);
        if (!$cost && $item->get_variation_id()) {
            $cost = get_post_meta($item->get_product_id(), '_cost_price', true);
        }
        $cogs += ((float) $cost * $item->get_quantity());
    }

    return [
        'revenue' => $revenue,
        'cogs' => $cogs,
        'profit' => $revenue - $cogs
    ];
}

// ============================
// ORDER LIST COLUMNS
// ============================
add_filter('manage_edit-shop_order_columns', 'wppam_add_profit_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'wppam_add_profit_column');

function wppam_add_profit_column($columns)
{
    $new_columns = [];
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        if ('order_total' === $column_name) {
            $new_columns['wppam_profit'] = 'Profit';
        }
    }
    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'wppam_display_profit_column', 10, 1);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'wppam_display_profit_column', 10, 2);

function wppam_display_profit_column($column, $order_or_id = null)
{
    if ('wppam_profit' === $column) {
        $order = ($order_or_id instanceof WC_Order) ? $order_or_id : wc_get_order($order_or_id);
        if (!$order) {
            global $post;
            $order = wc_get_order($post->ID);
        }

        if ($order) {
            $data = wppam_get_order_profit_data($order);
            $color = $data['profit'] >= 0 ? 'var(--wppam-success, #22c55e)' : 'var(--wppam-danger, #ef4444)';
            echo '<span style="font-weight:700; color:' . $color . '">' . wc_price($data['profit']) . '</span>';
        }
    }
}

// ============================
// SINGLE ORDER META BOX
// ============================
add_action('add_meta_boxes', function () {
    $screens = ['shop_order', 'woocommerce_page_wc-orders'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wppam_order_profit_breakdown',
            'Profit Breakdown',
            'wppam_order_profit_meta_box_html',
            $screen,
            'side',
            'high'
        );
    }
});

function wppam_order_profit_meta_box_html($post_or_order)
{
    $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
    if (!$order) return;

    $data = wppam_get_order_profit_data($order);
    ?>
    <div class="wppam-meta-box-content">
        <p style="display: flex; justify-content: space-between; margin: 8px 0;">
            <span>Revenue:</span>
            <span><?php echo wc_price($data['revenue']); ?></span>
        </p>
        <p style="display: flex; justify-content: space-between; margin: 8px 0;">
            <span>COGS:</span>
            <span><?php echo wc_price($data['cogs']); ?></span>
        </p>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <p style="display: flex; justify-content: space-between; margin: 8px 0; font-weight: 700;">
            <span>Net Profit:</span>
            <span style="color: <?php echo $data['profit'] >= 0 ? '#22c55e' : '#ef4444'; ?>">
                <?php echo wc_price($data['profit']); ?>
            </span>
        </p>
    </div>
    <?php
}

// ============================
// EXPENSES
// ============================
function wppam_get_monthly_expenses($year, $month)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';
    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE YEAR(expense_date)=%d AND MONTH(expense_date)=%d
    ", $year, $month));
}

// ============================
// PROFIT
// ============================
function wppam_calculate_profit($year, $month)
{
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    return wppam_calculate_profit_for_range($start_date, $end_date);
}

function wppam_calculate_profit_for_range($start_date, $end_date)
{
    // Revenue
    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $start_date . '...' . $end_date,
    ]);

    $revenue = 0;
    $cogs = 0;
    foreach ($orders as $order) {
        $revenue += (float) $order->get_total();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $cost = get_post_meta($product_id, '_cost_price', true);
            if (!$cost && $item->get_variation_id()) {
                $cost = get_post_meta($item->get_product_id(), '_cost_price', true);
            }
            $cogs += ((float) $cost * $item->get_quantity());
        }
    }

    // Expenses
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';
    $expenses = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE expense_date BETWEEN %s AND %s
    ", $start_date, $end_date));

    return [
        'revenue' => $revenue,
        'cogs' => $cogs,
        'expenses' => $expenses,
        'profit' => $revenue - ($cogs + $expenses)
    ];
}

// ============================
// ADMIN MENU
// ============================
add_action('admin_menu', function () {
    add_menu_page('Profit Manager', 'Profit Manager', 'manage_options', 'wppam', 'wppam_dashboard', 'dashicons-chart-line');
    add_submenu_page('wppam', 'Daily Report', 'Daily Report', 'manage_options', 'wppam-daily', 'wppam_daily_report');
    add_submenu_page('wppam', 'Yearly Report', 'Yearly Report', 'manage_options', 'wppam-yearly', 'wppam_yearly_report');
    add_submenu_page('wppam', 'Add Expense', 'Add Expense', 'manage_options', 'wppam-add-expense', 'wppam_add_expense');
    add_submenu_page('wppam', 'Plugin Info', 'Plugin Info', 'manage_options', 'wppam-info', 'wppam_info_page');
    // Hidden detailing pages
    add_submenu_page(null, 'Daily Details', 'Daily Details', 'manage_options', 'wppam-daily-details', 'wppam_daily_details_report');
    add_submenu_page(null, 'Monthly Details', 'Monthly Details', 'manage_options', 'wppam-monthly-details', 'wppam_monthly_details_report');
});

// ============================
// DASHBOARD
// ============================
function wppam_dashboard()
{
    $data = wppam_calculate_profit(date('Y'), date('m'));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Profit & Accounting Overview</h1>
            <div class="wppam-actions">
                <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" class="wppam-btn-primary">Add
                    Expense</a>
            </div>
        </div>

        <div class="wppam-stats-grid">
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Monthly Revenue</div>
                <div class="wppam-stat-value revenue"><?php echo wc_price($data['revenue']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Product COGS</div>
                <div class="wppam-stat-value cogs"><?php echo wc_price($data['cogs']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Expenses</div>
                <div class="wppam-stat-value expenses"><?php echo wc_price($data['expenses']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Net Profit</div>
                <div class="wppam-stat-value profit"><?php echo wc_price($data['profit']); ?></div>
            </div>
        </div>

        <div class="wppam-chart-container">
            <h3 style="margin-top:0">Annual Profit Performance</h3>
            <canvas id="wppamChart" style="max-height: 400px;"></canvas>
        </div>
    </div>
    <?php
}

// ============================
// ADD EXPENSE FORM
// ============================
function wppam_add_expense()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';

    if (isset($_POST['submit'])) {
        $wpdb->insert($table, [
            'title' => sanitize_text_field($_POST['title']),
            'category' => sanitize_text_field($_POST['category']),
            'amount' => floatval($_POST['amount']),
            'expense_date' => $_POST['expense_date']
        ]);
        echo '<div class="updated notice is-dismissible"><p>Expense Added Successfully</p></div>';
    }
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <h1>Add New Expense</h1>
        <div class="wppam-form-card">
            <form method="post">
                <div class="wppam-form-group">
                    <label>Expense Title</label>
                    <input type="text" name="title" class="wppam-input" placeholder="e.g. Office Rent" required>
                </div>
                <div class="wppam-form-group">
                    <label>Category</label>
                    <select name="category" class="wppam-select">
                        <option>Office</option>
                        <option>Salary</option>
                        <option>Godown</option>
                        <option>Marketing</option>
                        <option>Shipping</option>
                        <option>Utility</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="wppam-form-group">
                    <label>Amount (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
                    <input type="number" step="any" name="amount" class="wppam-input" placeholder="0.00" required>
                </div>
                <div class="wppam-form-group">
                    <label>Date</label>
                    <input type="date" name="expense_date" class="wppam-input" value="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>
                <button name="submit" class="wppam-btn-primary">Save Expense</button>
            </form>
        </div>
    </div>
    <?php
}

// ============================
// DAILY REPORT
// ============================
function wppam_daily_report()
{
    $selected_month = isset($_GET['report_month']) ? sanitize_text_field($_GET['report_month']) : date('Y-m');
    $month_start = $selected_month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start));
    $days_in_month = date('t', strtotime($month_start));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Daily Profit Report</h1>
            <div class="wppam-filters">
                <form method="get">
                    <input type="hidden" name="page" value="wppam-daily">
                    <input type="month" name="report_month" value="<?php echo $selected_month; ?>"
                        onchange="this.form.submit()" class="wppam-input" style="width: auto;">
                </form>
            </div>
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
                    for ($d = 1; $d <= $days_in_month; $d++) {
                        $current_date = $selected_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);

                        $data = wppam_calculate_profit_for_range($current_date, $current_date);
                        if ($data['revenue'] == 0 && $data['expenses'] == 0 && $data['cogs'] == 0)
                            continue;

                        ?>
                        <tr>
                            <td><strong><?php echo date('jS M, Y', strtotime($current_date)); ?></strong></td>
                            <td><?php echo wc_price($data['revenue']); ?></td>
                            <td><?php echo wc_price($data['cogs']); ?></td>
                            <td><?php echo wc_price($data['expenses']); ?></td>
                            <td
                                style="font-weight:700; color: <?php echo $data['profit'] >= 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>">
                                <?php echo wc_price($data['profit']); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wppam-daily-details&date=' . $current_date); ?>"
                                    class="button button-small">View Details</a>
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

// ============================
// DAILY DETAILS REPORT
// ============================
function wppam_daily_details_report()
{
    $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

    // Get Orders for this day
    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => $date,
    ]);

    // Get Expenses for this day
    global $wpdb;
    $expense_table = $wpdb->prefix . 'wppam_expenses';
    $expenses = $wpdb->get_results($wpdb->prepare("SELECT * FROM $expense_table WHERE expense_date = %s", $date));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Details for <?php echo date('jS M, Y', strtotime($date)); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=wppam-daily'); ?>" class="wppam-btn-primary">Back to Daily
                Report</a>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- COGS Details -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Product Cost
                    Details (COGS)</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_cogs = 0;
                        if ($orders) {
                            foreach ($orders as $order) {
                                foreach ($order->get_items() as $item) {
                                    $product_id = $item->get_variation_id() ?: $item->get_product_id();
                                    $cost = (float) get_post_meta($product_id, '_cost_price', true);
                                    if (!$cost && $item->get_variation_id()) {
                                        $cost = (float) get_post_meta($item->get_product_id(), '_cost_price', true);
                                    }
                                    $line_total = $cost * $item->get_quantity();
                                    $total_cogs += $line_total;
                                    ?>
                                    <tr>
                                        <td><?php echo $item->get_name(); ?></td>
                                        <td><?php echo $item->get_quantity(); ?></td>
                                        <td><?php echo wc_price($cost); ?></td>
                                        <td><?php echo wc_price($line_total); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                        } else {
                            echo '<tr><td colspan="4">No product sales found for this date.</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 700;">Total COGS:</td>
                            <td style="font-weight: 700;"><?php echo wc_price($total_cogs); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expense Details -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Business Expenses
                </h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_exp = 0;
                        if ($expenses) {
                            foreach ($expenses as $exp) {
                                $total_exp += (float) $exp->amount;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($exp->title); ?></strong><br>
                                        <small
                                            style="color: var(--wppam-text-muted);"><?php echo esc_html($exp->category); ?></small>
                                    </td>
                                    <td><?php echo wc_price($exp->amount); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="2">No expenses logged for this date.</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-align: right; font-weight: 700;">Total Expenses:</td>
                            <td style="font-weight: 700;"><?php echo wc_price($total_exp); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// ============================
// YEARLY REPORT
// ============================
function wppam_yearly_report()
{
    $selected_year = isset($_GET['report_year']) ? intval($_GET['report_year']) : (int) date('Y');
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Yearly Financial Report (<?php echo $selected_year; ?>)</h1>
            <div class="wppam-filters">
                <form method="get">
                    <input type="hidden" name="page" value="wppam-yearly">
                    <select name="report_year" onchange="this.form.submit()" class="wppam-select"
                        style="width: auto; min-width: 120px;">
                        <?php
                        $current_year = (int) date('Y');
                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                            printf('<option value="%d" %s>%d</option>', $y, selected($selected_year, $y, false), $y);
                        }
                        ?>
                    </select>
                </form>
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
                        $data = wppam_calculate_profit($selected_year, $m);
                        ?>
                        <tr>
                            <td><strong><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></strong></td>
                            <td><?php echo wc_price($data['revenue']); ?></td>
                            <td><?php echo wc_price($data['cogs']); ?></td>
                            <td><?php echo wc_price($data['expenses']); ?></td>
                            <td
                                style="font-weight:700; color: <?php echo $data['profit'] >= 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>">
                                <?php echo wc_price($data['profit']); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wppam-monthly-details&year=' . $selected_year . '&month=' . $m); ?>"
                                    class="button button-small">View Details</a>
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

// ============================
// MONTHLY DETAILS REPORT
// ============================
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

    // Aggregate COGS
    $product_stats = [];
    $total_cogs = 0;
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
                    'total' => 0
                ];
            }
            $qty = $item->get_quantity();
            $line_total = $product_stats[$product_id]['cost'] * $qty;
            $product_stats[$product_id]['qty'] += $qty;
            $product_stats[$product_id]['total'] += $line_total;
            $total_cogs += $line_total;
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
                            <th>Total Qty</th>
                            <th>Cost/Unit</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($product_stats): ?>
                            <?php foreach ($product_stats as $stat): ?>
                                <tr>
                                    <td><?php echo esc_html($stat['name']); ?></td>
                                    <td><?php echo $stat['qty']; ?></td>
                                    <td><?php echo wc_price($stat['cost']); ?></td>
                                    <td><?php echo wc_price($stat['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No product sales found for this month.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 700;">Monthly Total COGS:</td>
                            <td style="font-weight: 700;"><?php echo wc_price($total_cogs); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expense Aggregation -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Expenses by
                    Category</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_exp = 0;
                        if ($expenses) {
                            foreach ($expenses as $exp) {
                                $total_exp += (float) $exp->total_amount;
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($exp->category); ?></strong></td>
                                    <td><?php echo wc_price($exp->total_amount); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="2">No expenses logged for this month.</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-align: right; font-weight: 700;">Monthly Total:</td>
                            <td style="font-weight: 700;"><?php echo wc_price($total_exp); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// ============================
// CSV EXPORT
// ============================
add_action('admin_post_wppam_csv', function () {
    $year = isset($_GET['year']) ? intval($_GET['year']) : (int) date('Y');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=yearly-profit-' . $year . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Month', 'Profit']);
    for ($m = 1; $m <= 12; $m++) {
        $data = wppam_calculate_profit($year, $m);
        fputcsv($out, [$m, $data['profit']]);
    }
    fclose($out);
    exit;
});

// ============================
// CHART.JS + DASHBOARD GRAPH
// ============================
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

// ============================
// PDF EXPORT (BASIC)
// ============================
add_action('admin_post_wppam_pdf', function () {
    if (!class_exists('Dompdf\Dompdf'))
        return;
    $year = isset($_GET['year']) ? intval($_GET['year']) : (int) date('Y');
    $html = '<h1>Yearly Profit Report (' . $year . ')</h1><table border="1" width="100%">';
    for ($m = 1; $m <= 12; $m++) {
        $data = wppam_calculate_profit($year, $m);
        $html .= '<tr><td>' . date('F', mktime(0, 0, 0, $m, 1)) . '</td><td>' . $data['profit'] . '</td></tr>';
    }
    $html .= '</table>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream('profit-report-' . $year . '.pdf');
    exit;
});

// ============================
// PLUGIN INFO PAGE
// ============================
/**
 * Display the Plugin Info & Documentation page.
 */
function wppam_info_page()
{
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Plugin Documentation & Help</h1>
        </div>

        <div class="wppam-tabs-container">
            <div class="wppam-tabs-nav">
                <button class="wppam-tab-btn active" onclick="wppamOpenTab(event, 'getting-started')">🚀 Getting
                    Started</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'features')">✨ Features</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'calculations')">🧮 Calculations</button>
                <button class="wppam-tab-btn" onclick="wppamOpenTab(event, 'faq')">❓ FAQ</button>
            </div>

            <div id="getting-started" class="wppam-tab-content active" style="display: block;">
                <div class="wppam-table-card" style="padding: 30px;">
                    <h3 style="margin-top:0; color: var(--wppam-primary);">Quick Start Guide</h3>
                    <p>Follow these steps to set up your profit tracking accurately:</p>
                    <div class="wppam-step-list">
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">1</span>
                            <div class="wppam-step-text">
                                <strong>Define Product Costs:</strong>
                                <p>Go to your WooCommerce products. In the "General" tab, you'll find a new field
                                    <strong>"Product Cost (COGS)"</strong>. Enter your purchase price there.
                                </p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">2</span>
                            <div class="wppam-step-text">
                                <strong>Log Business Expenses:</strong>
                                <p>Navigate to the "Add Expense" page to record utilities, rent, or salaries. These will be
                                    deducted from your net profit.</p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">3</span>
                            <div class="wppam-step-text">
                                <strong>Check Order Profit:</strong>
                                <p>Open any WooCommerce order or check the orders list. A new "Profit" column and meta
                                    box show the profitability of each order.</p>
                            </div>
                        </div>
                        <div class="wppam-step-item">
                            <span class="wppam-step-number">4</span>
                            <div class="wppam-step-text">
                                <strong>Review Reports:</strong>
                                <p>Visit the Dashboard, Daily Reports, and Yearly Reports to see your real-time financial
                                    health.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="features" class="wppam-tab-content">
                <div class="wppam-table-card" style="padding: 30px;">
                    <h3 style="margin-top:0; color: var(--wppam-primary);">Key Features</h3>
                    <ul class="wppam-feature-list">
                        <li><strong>Automated COGS Calculation:</strong> Automatically calculates the cost of every sale.
                        </li>
                        <li><strong>Order-Level Profit Visibility:</strong> See profit data directly in the WooCommerce
                            Orders list and single order pages.</li>
                        <li><strong>Expense Category Management:</strong> Group your expenses for better analysis.</li>
                        <li><strong>Interactive Dashboard:</strong> Visual charts powered by Chart.js.</li>
                        <li><strong>Detailed Drill-down:</strong> See exact orders and expenses for any day or month.</li>
                        <li><strong>Data Export:</strong> Download your reports in CSV or PDF format for accounting.</li>
                    </ul>
                </div>
            </div>

            <div id="calculations" class="wppam-tab-content">
                <div class="wppam-table-card" style="padding: 30px;">
                    <h3 style="margin-top:0; color: var(--wppam-primary);">Methodology</h3>
                    <div class="wppam-calc-box">
                        <code>Net Profit = (Total Revenue) - (Total COGS + Total Expenses)</code>
                    </div>
                    <p><strong>Revenue:</strong> Sum of totals from orders marked as 'Processing' or 'Completed'.</p>
                    <p><strong>COGS:</strong> Sum of (Product Cost * Quantity) for all items in those orders.</p>
                    <p><strong>Expenses:</strong> Sum of all entries in the expense table for the specified date range.</p>
                </div>
            </div>

            <div id="faq" class="wppam-tab-content">
                <div class="wppam-table-card" style="padding: 30px;">
                    <h3 style="margin-top:0; color: var(--wppam-primary);">FAQs</h3>
                    <div class="wppam-faq-item">
                        <strong>Q: What happens if I forget to set a product cost?</strong>
                        <p>A: The plugin will treat the cost as 0.00, meaning the entire sale amount will be counted as
                            gross profit before expenses.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: Does it support variable products?</strong>
                        <p>A: Yes! You can set individual costs for each variation. If a variation cost is missing, it
                            falls back to the parent product's cost.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: Where can I see profit for a specific order?</strong>
                        <p>A: Go to WooCommerce > Orders to see the "Profit" column, or click on an order to see the
                            "Profit Breakdown" sidebar meta box.</p>
                    </div>
                    <div class="wppam-faq-item">
                        <strong>Q: How do I export data?</strong>
                        <p>A: Look for the export buttons on the Dashboard or Report pages to generate CSV or PDF
                            files.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wppam-footer-info" style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="wppam-table-card" style="padding: 20px;">
                <h4 style="margin: 0 0 10px 0;">Technical Info</h4>
                <small>Version: 1.1.0 | Text Domain: woocommerce-profit-accounting</small>
            </div>
            <div class="wppam-table-card" style="padding: 20px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 15px;">
                <span>Need help with setup?</span>
                <a href="https://github.com/coderjahidul/woocommerce-profit-accounting" target="_blank" class="wppam-btn-primary"
                    style="text-decoration: none;">GitHub Repo</a>
                <a href="https://jahidulsabuz.com" target="_blank" class="wppam-btn-primary"
                    style="text-decoration: none; background: #22c55e !important;">Support Website</a>
            </div>
        </div>
    </div>

    <script>
        function wppamOpenTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("wppam-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("wppam-tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }
    </script>

    <style>
        .wppam-tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .wppam-tab-btn {
            padding: 12px 20px;
            background: #f1f5f9;
            border: 1px solid var(--wppam-border);
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            transition: all 0.2s;
            color: var(--wppam-text-muted);
        }

        .wppam-tab-btn:hover {
            background: #e2e8f0;
        }

        .wppam-tab-btn.active {
            background: var(--wppam-primary);
            color: white;
            border-color: var(--wppam-primary);
        }

        .wppam-tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .wppam-tab-content.active {
            display: block;
        }

        .wppam-step-list {
            margin-top: 20px;
        }

        .wppam-step-item {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .wppam-step-number {
            width: 32px;
            height: 32px;
            background: var(--wppam-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .wppam-step-text strong {
            display: block;
            margin-bottom: 5px;
            color: var(--wppam-text-main);
        }

        .wppam-step-text p {
            margin: 0;
            color: var(--wppam-text-muted);
        }

        .wppam-feature-list {
            list-style: none;
            padding: 0;
        }

        .wppam-feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--wppam-border);
        }

        .wppam-feature-list li:last-child {
            border-bottom: none;
        }

        .wppam-calc-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--wppam-primary);
            margin-bottom: 20px;
        }

        .wppam-faq-item {
            margin-bottom: 25px;
        }

        .wppam-faq-item strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            color: var(--wppam-text-main);
        }
    </style>
    <?php
}


?>