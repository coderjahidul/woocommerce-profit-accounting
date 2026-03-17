<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all purchases.
 */
function wppam_get_purchases($limit = -1, $offset = 0)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';
    $query = "SELECT * FROM $table ORDER BY purchase_date DESC, created_at DESC";

    if ($limit > 0) {
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
    }

    return $wpdb->get_results($query);
}

/**
 * Add a new product purchase.
 */
function wppam_add_purchase($data)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';

    $product_id = intval($data['product_id']);
    $variation_id = isset($data['variation_id']) ? intval($data['variation_id']) : 0;
    $quantity = intval($data['quantity']);
    $purchase_price = floatval($data['purchase_price']);
    $total_amount = $quantity * $purchase_price;
    $purchase_date = sanitize_text_field($data['purchase_date']);
    $supplier = sanitize_text_field($data['supplier']);
    $notes = sanitize_textarea_field($data['notes']);

    // 1. Insert into purchases table
    $inserted = $wpdb->insert($table, [
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'quantity' => $quantity,
        'purchase_price' => $purchase_price,
        'total_amount' => $total_amount,
        'purchase_date' => $purchase_date,
        'supplier' => $supplier,
        'notes' => $notes,
    ]);

    if ($inserted) {
        $target_id = $variation_id ?: $product_id;
        $product = wc_get_product($target_id);

        if ($product) {
            // 2. Update Stock
            if ($product->managing_stock()) {
                $current_stock = $product->get_stock_quantity();
                wc_update_product_stock($product, $current_stock + $quantity);
            }

            // 3. Update Cost Price Meta
            update_post_meta($target_id, '_cost_price', $purchase_price);

            // 4. Record Cash Transaction
            if (function_exists('wppam_add_cash_transaction')) {
                wppam_add_cash_transaction([
                    'amount' => $total_amount,
                    'type' => 'out',
                    'category' => 'Product Purchase',
                    'transaction_date' => $purchase_date,
                    'description' => sprintf(__('Purchased %d units of %s', 'woocommerce-profit-accounting'), $quantity, $product->get_name()),
                ]);
            }
        }
    }

    return $inserted;
}

/**
 * Process multiple product purchases.
 */
function wppam_add_bulk_purchase($items, $common_data)
{
    global $wpdb;
    $total_transaction_amount = 0;
    $purchased_items_desc = [];

    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
        $quantity = intval($item['quantity']);
        $purchase_price = floatval($item['purchase_price']);
        
        if ($product_id <= 0 || $quantity <= 0) continue;

        $total_amount = $quantity * $purchase_price;
        $total_transaction_amount += $total_amount;

        // 1. Insert into purchases table
        $wpdb->insert($wpdb->prefix . 'wppam_purchases', [
            'invoice_no'     => $common_data['invoice_no'],
            'product_id'     => $product_id,
            'variation_id'   => $variation_id,
            'quantity'       => $quantity,
            'purchase_price' => $purchase_price,
            'total_amount'   => $total_amount,
            'purchase_date'  => $common_data['purchase_date'],
            'supplier'       => $common_data['supplier'],
            'notes'          => $common_data['notes'],
        ]);

        $target_id = $variation_id ?: $product_id;
        $product = wc_get_product($target_id);

        if ($product) {
            // 2. Update Stock
            if (!$product->managing_stock()) {
                $product->set_manage_stock(true);
            }

            $current_stock = (int)$product->get_stock_quantity();
            $product->set_stock_quantity($current_stock + $quantity);
            $product->save();

            // 3. Update Cost Price Meta
            update_post_meta($target_id, '_cost_price', $purchase_price);
            
            $purchased_items_desc[] = "{$quantity}x " . $product->get_name();
        }
    }

    // 4. Record single Cash Transaction for the whole purchase
    if ($total_transaction_amount > 0 && function_exists('wppam_add_cash_transaction')) {
        wppam_add_cash_transaction([
            'amount'           => $total_transaction_amount,
            'type'             => 'out',
            'category'         => 'Product Purchase',
            'transaction_date' => $common_data['purchase_date'],
            'description'      => sprintf(__('Purchase Invoice %s: %s', 'woocommerce-profit-accounting'), $common_data['invoice_no'], implode(', ', $purchased_items_desc)),
        ]);
    }

    return true;
}

/**
 * Get grouped invoices.
 */
function wppam_get_invoices($limit = 20)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';
    return $wpdb->get_results("SELECT invoice_no, purchase_date, supplier, SUM(total_amount) as total_amount, COUNT(id) as items_count 
                               FROM $table 
                               GROUP BY invoice_no 
                               ORDER BY id DESC 
                               LIMIT $limit");
}

/**
 * Get items for a specific invoice.
 */
function wppam_get_invoice_items($invoice_no)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE invoice_no = %s", $invoice_no));
}

/**
 * Delete an entire invoice.
 */
function wppam_delete_invoice($invoice_no)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';
    return $wpdb->delete($table, ['invoice_no' => $invoice_no], ['%s']);
}

/**
 * Delete a purchase.
 */
function wppam_delete_purchase($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';
    return $wpdb->delete($table, ['id' => $id], ['%d']);
}