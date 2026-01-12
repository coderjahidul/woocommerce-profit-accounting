<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get comprehensive inventory data.
 */
function wppam_get_inventory_data()
{
    $products = wc_get_products([
        'limit' => -1,
        'status' => 'publish',
    ]);

    $total_value_cost = 0;
    $total_units = 0;
    $out_of_stock_count = 0;
    $product_stats = [];

    foreach ($products as $product) {
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation) continue;
                $data = wppam_process_inventory_item($variation);
                $total_value_cost += $data['stock_value'];
                $total_units += $data['stock'];
                if ($data['stock'] <= 0) $out_of_stock_count++;
                $product_stats[] = $data;
            }
        } else {
            $data = wppam_process_inventory_item($product);
            $total_value_cost += $data['stock_value'];
            $total_units += $data['stock'];
            if ($data['stock'] <= 0) $out_of_stock_count++;
            $product_stats[] = $data;
        }
    }

    // Dead Stock (No sales in 30 days)
    $recent_order_items = [];
    $recent_orders = wc_get_orders([
        'limit' => -1,
        'status' => ['processing', 'completed'],
        'date_created' => '>' . date('Y-m-d', strtotime('-30 days')),
    ]);
    foreach ($recent_orders as $order) {
        foreach ($order->get_items() as $item) {
            $id = $item->get_variation_id() ?: $item->get_product_id();
            $recent_order_items[$id] = true;
        }
    }

    $dead_stock = [];
    foreach ($product_stats as $stat) {
        if (!isset($recent_order_items[$stat['id']]) && $stat['stock'] > 0) {
            $dead_stock[] = $stat;
        }
    }

    return [
        'total_value_cost' => $total_value_cost,
        'total_units' => $total_units,
        'out_of_stock_count' => $out_of_stock_count,
        'product_stats' => $product_stats,
        'dead_stock' => $dead_stock,
    ];
}

/**
 * Process individual product/variation for inventory stats.
 */
function wppam_process_inventory_item($product)
{
    $id = $product->get_id();
    $cost = (float) get_post_meta($id, '_cost_price', true);
    if (!$cost && $product->is_type('variation')) {
        $cost = (float) get_post_meta($product->get_parent_id(), '_cost_price', true);
    }
    $stock = (int) $product->get_stock_quantity();
    $sold = (int) $product->get_total_sales();
    $price = (float) $product->get_price();

    return [
        'id' => $id,
        'edit_id' => $product->is_type('variation') ? $product->get_parent_id() : $id,
        'name' => $product->get_name(),
        'cost' => $cost,
        'price' => $price,
        'stock' => $stock,
        'sold' => $sold,
        'stock_value' => $stock * $cost,
        'potential_profit' => $stock * ($price - $cost)
    ];
}
