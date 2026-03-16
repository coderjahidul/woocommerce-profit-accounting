<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get cash transactions from the ledger.
 */
function wppam_get_cash_transactions($limit = -1, $offset = 0)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    $query = "SELECT * FROM $table ORDER BY transaction_date DESC, created_at DESC";
    
    if ($limit > 0) {
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Add a new cash transaction.
 */
function wppam_add_cash_transaction($data)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    
    return $wpdb->insert($table, [
        'amount'           => sanitize_text_field($data['amount']),
        'type'             => sanitize_text_field($data['type']), // 'in' or 'out'
        'category'         => sanitize_text_field($data['category']),
        'transaction_date' => sanitize_text_field($data['transaction_date']),
        'description'      => sanitize_textarea_field($data['description']),
    ]);
}

/**
 * Delete a cash transaction.
 */
function wppam_delete_cash_transaction($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    return $wpdb->delete($table, ['id' => $id], ['%d']);
}

/**
 * Calculate the current available cash balance.
 * Balance = Opening Balance + Total Revenue + Total Cash In - Total Expenses - Total Cash Out
 */
function wppam_get_cash_balance()
{
    global $wpdb;
    
    // 1. Opening Balance
    $opening_balance = (float) get_option('wppam_opening_balance_amount', 0);
    $opening_date    = get_option('wppam_opening_balance_date', '1970-01-01');

    // 2. Revenue (from opening date to now)
    $orders = wc_get_orders([
        'limit'        => -1,
        'status'       => ['processing', 'completed'],
        'date_created' => $opening_date . '...' . date('Y-m-d'),
    ]);
    
    $total_revenue = 0;
    foreach ($orders as $order) {
        $total_revenue += (float) $order->get_total();
    }

    // 3. Expenses (from opening date to now)
    $expense_table = $wpdb->prefix . 'wppam_expenses';
    $total_expenses = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $expense_table
        WHERE expense_date >= %s
    ", $opening_date));

    // 4. Cash Inflows & Outflows
    $ledger_table = $wpdb->prefix . 'wppam_cash_ledger';
    $cash_in = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $ledger_table
        WHERE type = 'in' AND transaction_date >= %s
    ", $opening_date));
    
    $cash_out = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $ledger_table
        WHERE type = 'out' AND transaction_date >= %s
    ", $opening_date));

    return $opening_balance + $total_revenue + $cash_in - $total_expenses - $cash_out;
}

/**
 * Get total cash in for a specific range.
 */
function wppam_get_total_cash_in($start_date, $end_date)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE type = 'in' AND transaction_date BETWEEN %s AND %s
    ", $start_date, $end_date));
}

/**
 * Get total cash out for a specific range.
 */
function wppam_get_total_cash_out($start_date, $end_date)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE type = 'out' AND transaction_date BETWEEN %s AND %s
    ", $start_date, $end_date));
}
