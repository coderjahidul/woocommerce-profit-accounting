<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the main Dashboard.
 */
function wppam_dashboard()
{
    $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : 'this-month';
    $start_date = '';
    $end_date = date('Y-m-d');

    switch ($range) {
        case 'today':
            $start_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = $start_date;
            break;
        case 'last-7-days':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'last-30-days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'this-month':
            $start_date = date('Y-m-01');
            break;
        case 'last-month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this-year':
            $start_date = date('Y-01-01');
            break;
        case 'last-year':
            $start_date = date('Y-01-01', strtotime('-1 year'));
            $end_date = date('Y-12-31', strtotime('-1 year'));
            break;
        case 'custom':
            $start_date = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-01');
            $end_date = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-d');
            break;
        default:
            $start_date = date('Y-m-01');
    }

    // Main Stats always show CURRENT MONTH
    $data = wppam_calculate_profit(date('Y'), date('m'));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <div>
                <h1>Profit & Accounting Overview</h1>
                <p style="margin: 5px 0 0 0; color: var(--wppam-text-muted);">Main metrics for <strong><?php echo date('F Y'); ?></strong></p>
            </div>
            <div class="wppam-actions">
                <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" class="wppam-btn-primary">Add Expense</a>
            </div>
        </div>

        <div class="wppam-stats-grid">
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Total Revenue</div>
                <div class="wppam-stat-value revenue"><?php echo wc_price($data['revenue']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Product COGS</div>
                <div class="wppam-stat-value cogs"><?php echo wc_price($data['cogs']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Operating Expenses</div>
                <div class="wppam-stat-value expenses"><?php echo wc_price($data['expenses']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Net Profit</div>
                <div class="wppam-stat-value profit"><?php echo wc_price($data['profit']); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
            <div class="wppam-table-card" style="padding: 24px;">
                <h3 style="margin-top:0;">Performance Trend</h3>
                <div style="height: 350px;">
                    <canvas id="wppamChart"></canvas>
                </div>
            </div>

            <div class="wppam-table-card" style="padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin:0;">Delivery Status</h3>
                    <select id="wppam-delivery-filter" class="wppam-select" style="width: 140px; font-size: 11px; padding: 4px 8px; height: auto; cursor: pointer;">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last-7-days">Last 7 Days</option>
                        <option value="last-30-days" selected>Last 30 Days</option>
                        <option value="this-month">This Month</option>
                        <option value="last-month">Last Month</option>
                    </select>
                </div>
                <div class="wppam-status-canvas-container" style="height: 250px; position: relative;">
                    <canvas id="wppamStatusChart"></canvas>
                </div>
                <div id="wppamStatusLegend" style="margin-top: 20px;"></div>
            </div>
        </div>

        <div class="wppam-table-card" style="padding: 24px; margin-top: 30px;">
            <h3 style="margin-top:0;">Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="<?php echo admin_url('admin.php?page=wppam-daily'); ?>" class="wppam-btn-secondary">View Daily
                    Reports</a>
                <a href="<?php echo admin_url('admin.php?page=wppam-yearly'); ?>" class="wppam-btn-secondary">View Yearly
                    Summary</a>
                <a href="<?php echo admin_url('admin.php?page=wppam-inventory'); ?>" class="wppam-btn-secondary">Check
                    Inventory Valuation</a>
                <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" class="wppam-btn-secondary">Add New
                    Expense</a>
            </div>
        </div>
    </div>
    <?php
}
