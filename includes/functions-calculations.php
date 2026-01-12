<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate total revenue for a specific year and month.
 */
function wppam_get_monthly_revenue($year, $month)
{
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

/**
 * Calculate total Cost of Goods Sold (COGS) for a specific year and month.
 */
function wppam_get_monthly_cogs($year, $month)
{
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
                $cost = get_post_meta($item->get_product_id(), '_cost_price', true);
            }
            $total_cogs += ((float) $cost * $item->get_quantity());
        }
    }
    return $total_cogs;
}

/**
 * Calculate profit data for a single order.
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

/**
 * Calculate net profit for a specific year and month.
 */
function wppam_calculate_profit($year, $month)
{
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    return wppam_calculate_profit_for_range($start_date, $end_date);
}

/**
 * Calculate profit data for a custom date range.
 */
function wppam_calculate_profit_for_range($start_date, $end_date)
{
    // Revenue & COGS
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
