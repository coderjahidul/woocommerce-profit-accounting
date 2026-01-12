<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle and display Add Expense form.
 */
function wppam_add_expense()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';

    $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
    $edit_expense = null;
    
    // Handle Deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $delete_id = (int)$_GET['id'];
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_expense_' . $delete_id)) {
            $wpdb->delete($table, ['id' => $delete_id]);
            echo '<div class="notice notice-success is-dismissible"><p>Expense deleted successfully!</p></div>';
        }
    }

    if ($edit_id) {
        $edit_expense = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
    }

    if (isset($_POST['wppam_add_expense_nonce']) && wp_verify_nonce($_POST['wppam_add_expense_nonce'], 'wppam_add_expense')) {
        $amount = (float) sanitize_text_field($_POST['amount']);
        $category = sanitize_text_field($_POST['category']);
        $date = sanitize_text_field($_POST['expense_date']);
        $description = sanitize_textarea_field($_POST['note']);
        $id = isset($_POST['expense_id']) ? (int) $_POST['expense_id'] : 0;

        if ($id) {
            $result = $wpdb->update($table, [
                'amount' => $amount,
                'category' => $category,
                'expense_date' => $date,
                'description' => $description
            ], ['id' => $id]);
            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>Expense updated successfully!</p></div>';
                $edit_expense = null; // Clear edit mode after update
                $edit_id = 0;
            }
        } else {
            $result = $wpdb->insert($table, [
                'amount' => $amount,
                'category' => $category,
                'expense_date' => $date,
                'description' => $description
            ]);
            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>Expense added successfully!</p></div>';
            }
        }
    }

    $expenses = $wpdb->get_results("SELECT * FROM $table ORDER BY expense_date DESC LIMIT 50");
    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Manage Business Expenses</h1>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
            <div class="wppam-table-card" style="padding: 24px;">
                <h3 style="margin-top:0;"><?php echo $edit_expense ? 'Edit Expense' : 'Add New Expense'; ?></h3>
                <form method="POST" action="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>">
                    <?php if ($edit_expense): ?>
                        <input type="hidden" name="expense_id" value="<?php echo $edit_expense->id; ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field('wppam_add_expense', 'wppam_add_expense_nonce'); ?>
                    <div style="margin-bottom: 15px;">
                        <label>Amount (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
                        <input type="number" name="amount" step="any" required
                            value="<?php echo $edit_expense ? esc_attr($edit_expense->amount) : ''; ?>"
                            style="width: 100%; border-radius: 6px; padding: 8px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Category</label>
                        <select name="category" style="width: 100%; border-radius: 6px; padding: 8px;">
                            <?php 
                            $categories = ['Rent', 'Salary', 'Godown', 'Marketing', 'Shipping', 'Utility', 'Other'];
                            foreach ($categories as $cat) {
                                $selected = ($edit_expense && $edit_expense->category == $cat) ? 'selected' : '';
                                echo "<option value='$cat' $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Date</label>
                        <input type="date" name="expense_date" required 
                            value="<?php echo $edit_expense ? esc_attr($edit_expense->expense_date) : date('Y-m-d'); ?>"
                            style="width: 100%; border-radius: 6px; padding: 8px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Note (Optional)</label>
                        <textarea name="note" style="width: 100%; border-radius: 6px; padding: 8px;"><?php echo $edit_expense ? esc_textarea($edit_expense->description) : ''; ?></textarea>
                    </div>
                    <button type="submit" class="wppam-btn-primary" style="width:100%;"><?php echo $edit_expense ? 'Update Expense' : 'Save Expense'; ?></button>
                    <?php if ($edit_expense): ?>
                        <div style="text-align: center; margin-top: 10px;">
                            <a href="<?php echo admin_url('admin.php?page=wppam-add-expense'); ?>" style="color: var(--wppam-text-muted); text-decoration: none; font-size: 13px;">✕ Cancel Editing</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <div class="wppam-table-card">
                <h3 style="padding: 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);">Recent Expenses</h3>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Note</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $exp): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($exp->expense_date)); ?></td>
                                <td><span style="font-weight:600;"><?php echo esc_html($exp->category); ?></span></td>
                                <td style="color: var(--wppam-text-muted);"><?php echo esc_html($exp->description); ?></td>
                                <td style="font-weight:700; color: var(--wppam-danger);">-
                                    <?php echo wc_price($exp->amount); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=wppam-add-expense&edit_id=' . $exp->id); ?>" 
                                       class="wppam-btn-secondary" style="font-size: 11px; padding: 4px 10px;">Edit</a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wppam-add-expense&action=delete&id=' . $exp->id), 'delete_expense_' . $exp->id); ?>" 
                                       class="wppam-btn-secondary wppam-text-danger" 
                                       style="font-size: 11px; padding: 4px 10px; margin-left: 5px;"
                                       onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
