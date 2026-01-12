<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the main Dashboard.
 */
function wppam_dashboard()
{
    $data = wppam_calculate_profit(date('Y'), date('m'));
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Profit & Accounting Overview</h1>
            <div class="wppam-actions">
                <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" class="wppam-btn-primary">Add Expense</a>
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
                <h3 style="margin-top:0;">Monthly Performance Trend</h3>
                <div style="height: 350px;">
                    <canvas id="wppamChart"></canvas>
                </div>
            </div>

            <div class="wppam-table-card" style="padding: 24px;">
                <h3 style="margin-top:0;">Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="<?php echo admin_url('admin.php?page=wppam-daily'); ?>" class="wppam-btn-secondary">View Daily Reports</a>
                    <a href="<?php echo admin_url('admin.php?page=wppam-yearly'); ?>" class="wppam-btn-secondary">View Yearly Summary</a>
                    <a href="<?php echo admin_url('admin.php?page=wppam-inventory'); ?>" class="wppam-btn-secondary">Check Inventory Valuation</a>
                    <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" class="wppam-btn-secondary">Add New Expense</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
