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
        'account'          => isset($data['account']) ? sanitize_text_field($data['account']) : 'cash',
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
function wppam_get_cash_balance($account = 'cash')
{
    global $wpdb;
    
    // 1. Opening Balance
    $option_name = 'wppam_opening_balance_amount';
    if ($account == 'bank') $option_name = 'wppam_bank_balance';
    if ($account == 'mfs') $option_name = 'wppam_mfs_balance';

    $opening_balance = (float) get_option($option_name, 0);
    $opening_date    = get_option('wppam_opening_balance_date', '1970-01-01');

    $total_revenue = 0;
    $total_expenses = 0;

    // 2. Revenue from mapped payment methods
    $payment_mapping = get_option('wppam_payment_mapping', []);
    $mapped_gateways = [];
    
    foreach ($payment_mapping as $gateway_id => $mapped_account) {
        if ($mapped_account === $account) {
            $mapped_gateways[] = $gateway_id;
        }
    }

    // Special case: if account is 'cash', include methods that aren't mapped or are explicitly mapped to 'cash'
    if ($account === 'cash') {
        $all_gateways = WC()->payment_gateways->get_available_payment_gateways();
        foreach ($all_gateways as $id => $gw) {
            if (!isset($payment_mapping[$id]) || $payment_mapping[$id] === 'cash') {
                if (!in_array($id, $mapped_gateways)) {
                    $mapped_gateways[] = $id;
                }
            }
        }
    }

    if (!empty($mapped_gateways)) {
        $orders = wc_get_orders([
            'limit'          => -1,
            'status'         => ['processing', 'completed'],
            'date_created'   => $opening_date . '...' . date('Y-m-d'),
            'payment_method' => $mapped_gateways,
        ]);
        
        foreach ($orders as $order) {
            $total_revenue += (float) $order->get_total();
        }
    }

    // 3. Expenses (from opening date to now)
    $expense_table = $wpdb->prefix . 'wppam_expenses';
    $total_expenses = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $expense_table
        WHERE account = %s AND expense_date >= %s
    ", $account, $opening_date));

    // 4. Cash Inflows & Outflows for this specific account
    $ledger_table = $wpdb->prefix . 'wppam_cash_ledger';
    $cash_in = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $ledger_table
        WHERE type = 'in' AND account = %s AND transaction_date >= %s
    ", $account, $opening_date));
    
    $cash_out = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $ledger_table
        WHERE type = 'out' AND account = %s AND transaction_date >= %s
    ", $account, $opening_date));

    return $opening_balance + $total_revenue + $cash_in - $total_expenses - $cash_out;
}

/**
 * Get total cash in for a specific range.
 */
function wppam_get_total_cash_in($start_date, $end_date, $account = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    $where = "type = 'in' AND transaction_date BETWEEN %s AND %s";
    $params = [$start_date, $end_date];

    if ($account) {
        $where .= " AND account = %s";
        $params[] = $account;
    }

    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE $where
    ", ...$params));
}

/**
 * Get total cash out for a specific range.
 */
function wppam_get_total_cash_out($start_date, $end_date, $account = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_cash_ledger';
    $where = "type = 'out' AND transaction_date BETWEEN %s AND %s";
    $params = [$start_date, $end_date];

    if ($account) {
        $where .= " AND account = %s";
        $params[] = $account;
    }

    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE $where
    ", ...$params));
}

/**
 * Get total expenses for a specific account.
 */
function wppam_get_account_expenses($account, $start_date = '', $end_date = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';
    $where = "account = %s";
    $params = [$account];

    if ($start_date && $end_date) {
        $where .= " AND expense_date BETWEEN %s AND %s";
        $params[] = $start_date;
        $params[] = $end_date;
    }

    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE $where
    ", ...$params));
}
