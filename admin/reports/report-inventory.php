<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display Inventory Valuation Report.
 */
function wppam_inventory_report()
{
    $inventory = wppam_get_inventory_data();
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Inventory Valuation Report</h1>
        </div>

        <div class="wppam-stats-grid">
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Total Inventory Value (at cost)</div>
                <div class="wppam-stat-value profit"><?php echo wc_price($inventory['total_value_cost']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Total Units in Stock</div>
                <div class="wppam-stat-value"><?php echo number_format($inventory['total_units']); ?></div>
            </div>
            <div class="wppam-stat-card">
                <div class="wppam-stat-label">Out of Stock Items</div>
                <div class="wppam-stat-value expenses"><?php echo number_format($inventory['out_of_stock_count']); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
            <!-- Sold vs Remaining -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Sold vs Remaining
                    Insight</h3>
                <div style="overflow-x: auto;">
                    <table class="wppam-custom-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Cost</th>
                                <th>Price</th>
                                <th>Sold (All Time)</th>
                                <th>Remaining</th>
                                <th>Current Value (Cost)</th>
                                <th>Est. Profit (Remaining)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory['product_stats'] as $stat): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($stat['name']); ?></strong></td>
                                    <td><?php echo wc_price($stat['cost']); ?></td>
                                    <td><?php echo wc_price($stat['price']); ?></td>
                                    <td><?php echo number_format($stat['sold']); ?></td>
                                    <td>
                                        <span
                                            style="padding: 4px 8px; border-radius: 4px; background: <?php echo $stat['stock'] > 0 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; color: <?php echo $stat['stock'] > 0 ? 'var(--wppam-success)' : 'var(--wppam-danger)'; ?>; font-weight: 700;">
                                            <?php echo number_format($stat['stock']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo wc_price($stat['stock_value']); ?></td>
                                    <td style="color: var(--wppam-primary); font-weight: 700;">
                                        <?php echo wc_price($stat['potential_profit']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dead Stock -->
            <div class="wppam-table-card">
                <h3 style="padding: 20px 24px; margin: 0; border-bottom: 1px solid var(--wppam-border); color: var(--wppam-danger);">
                    Dead Stock (No Sales in 30 Days)</h3>
                <div style="overflow-x: auto;">
                    <table class="wppam-custom-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Stock Quantity</th>
                                <th>Value Locked (Cost)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($inventory['dead_stock']): ?>
                                <?php foreach ($inventory['dead_stock'] as $stat): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($stat['name']); ?></strong></td>
                                        <td><?php echo number_format($stat['stock']); ?></td>
                                        <td><?php echo wc_price($stat['stock_value']); ?></td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link($stat['edit_id']); ?>" class="wppam-btn-secondary"
                                               style="font-size: 11px; padding: 4px 10px;" target="_blank">Edit Product</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No dead stock identified. Good job!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}
