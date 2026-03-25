<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display and handle Cash Management page.
 */
function wppam_cash_management_page()
{
    // Handle Form Submission
    if (isset($_POST['wppam_add_cash_nonce']) && wp_verify_nonce($_POST['wppam_add_cash_nonce'], 'wppam_add_cash')) {
        $result = wppam_add_cash_transaction([
            'amount'           => $_POST['amount'],
            'type'             => $_POST['type'],
            'account'          => $_POST['account'],
            'category'         => $_POST['category'],
            'transaction_date' => $_POST['transaction_date'],
            'description'      => $_POST['description'],
        ]);

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Transaction added successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to add transaction.</p></div>';
        }
    }

    // Handle Delete
    if (isset($_GET['action']) && $_GET['action'] == 'delete_cash' && isset($_GET['id'])) {
        check_admin_referer('wppam_delete_cash_' . $_GET['id']);
        wppam_delete_cash_transaction($_GET['id']);
        echo '<div class="notice notice-success is-dismissible"><p>Transaction deleted.</p></div>';
    }

    $cash_balance = wppam_get_cash_balance('cash');
    $bank_balance = wppam_get_cash_balance('bank');
    $mfs_balance  = wppam_get_cash_balance('mfs');
    
    $cash_expenses = wppam_get_account_expenses('cash');
    $bank_expenses = wppam_get_account_expenses('bank');
    $mfs_expenses  = wppam_get_account_expenses('mfs');
    // Summary for current month (All accounts)
    $start_m = date('Y-m-01');
    $end_m = date('Y-m-t');
    $total_in = wppam_get_total_cash_in($start_m, $end_m);
    $total_out = wppam_get_total_cash_out($start_m, $end_m);

    $transactions = wppam_get_cash_transactions(50); // Get last 50
    $categories = ['Investment', 'Bank Withdrawal', 'Cash Sale', 'Purchase', 'Owner Draw', 'Petty Cash', 'Other'];
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Business Cash Management</h1>
            <button onclick="document.getElementById('wppam-add-cash-modal').style.display='block'" class="wppam-btn-primary">Add Cash Entry</button>
        </div>

        <div class="wppam-stats-grid">
            <div class="wppam-stat-card <?php echo $cash_balance >= 0 ? 'revenue' : 'expenses'; ?>">
                <div class="wppam-stat-label">Cash Balance</div>
                <div class="wppam-stat-value <?php echo $cash_balance >= 0 ? 'revenue' : 'expenses'; ?>">
                    <?php echo wc_price($cash_balance); ?>
                </div>
                <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">
                    Total Expenses: <?php echo wc_price($cash_expenses); ?>
                </div>
            </div>
            <div class="wppam-stat-card revenue">
                <div class="wppam-stat-label">Bank Balance</div>
                <div class="wppam-stat-value revenue"><?php echo wc_price($bank_balance); ?></div>
                <div style="font-size: 12px; margin-top: 5px; opacity: 0.8; color: var(--wppam-danger);">
                    Total Expenses: <?php echo wc_price($bank_expenses); ?>
                </div>
            </div>
            <div class="wppam-stat-card profit">
                <div class="wppam-stat-label">MFS Balance</div>
                <div class="wppam-stat-value profit"><?php echo wc_price($mfs_balance); ?></div>
                <div style="font-size: 12px; margin-top: 5px; opacity: 0.8; color: var(--wppam-danger);">
                    Total Expenses: <?php echo wc_price($mfs_expenses); ?>
                </div>
            </div>
            <div class="wppam-stat-card expenses">
                <div class="wppam-stat-label">Monthly Cash Out</div>
                <div class="wppam-stat-value expenses"><?php echo wc_price($total_out); ?></div>
            </div>
        </div>

        <div class="wppam-table-card wppam-mt-30" style="padding: 24px;">
            <h3 style="margin-top:0;">Recent Transactions</h3>
            <table class="wppam-custom-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" style="text-align:center;">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($tx->transaction_date)); ?></td>
                                <td><span style="text-transform: capitalize; font-weight: 600;"><?php echo esc_html($tx->account); ?></span></td>
                                <td>
                                    <span class="wppam-badge <?php echo $tx->type == 'in' ? 'wppam-badge-success' : 'wppam-badge-danger'; ?>">
                                        <?php echo strtoupper($tx->type); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($tx->category); ?></td>
                                <td class="<?php echo $tx->type == 'in' ? 'wppam-text-success' : 'wppam-text-danger'; ?> wppam-text-bold">
                                    <?php echo wc_price($tx->amount); ?>
                                </td>
                                <td style="color: var(--wppam-text-muted); font-size: 13px;"><?php echo esc_html($tx->description); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wppam-cash&action=delete_cash&id=' . $tx->id), 'wppam_delete_cash_' . $tx->id); ?>" 
                                       class="wppam-text-danger" 
                                       onclick="return confirm('Are you sure you want to delete this entry?')">
                                        <span class="dashicons dashicons-trash" style="font-size: 18px;"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Simple Modal for adding entry -->
    <div id="wppam-add-cash-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; height:100vh; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);">
        <div class="wppam-form-card" style="margin: 100px auto; background:white; position:relative;">
            <span onclick="document.getElementById('wppam-add-cash-modal').style.display='none'" 
                  style="position:absolute; right:20px; top:15px; cursor:pointer; font-size:24px; color:var(--wppam-text-muted);">&times;</span>
            <h2 style="margin-top:0;">Add Cash Transaction</h2>
            <form method="POST">
                <?php wp_nonce_field('wppam_add_cash', 'wppam_add_cash_nonce'); ?>
                
                <div class="wppam-form-group">
                    <label>Account</label>
                    <select name="account" class="wppam-select" required>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="mfs">MFS (Bkash, Nagad, etc)</option>
                    </select>
                </div>

                <div class="wppam-form-group">
                    <label>Transaction Type</label>
                    <select name="type" class="wppam-select" required>
                        <option value="in">In (+)</option>
                        <option value="out">Out (-)</option>
                    </select>
                </div>

                <div class="wppam-form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount" class="wppam-input" required placeholder="0.00">
                </div>

                <div class="wppam-form-group">
                    <label>Category</label>
                    <select name="category" class="wppam-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wppam-form-group">
                    <label>Date</label>
                    <input type="date" name="transaction_date" class="wppam-input" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="wppam-form-group">
                    <label>Description</label>
                    <textarea name="description" class="wppam-input" rows="3" placeholder="Notes..."></textarea>
                </div>

                <button type="submit" class="wppam-btn-primary" style="width:100%;">Save Transaction</button>
            </form>
        </div>
    </div>
    <?php
}
