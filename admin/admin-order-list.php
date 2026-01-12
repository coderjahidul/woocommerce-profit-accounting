<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Profit column to WooCommerce Orders list.
 */
add_filter('manage_edit-shop_order_columns', 'wppam_add_profit_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'wppam_add_profit_column');

function wppam_add_profit_column($columns)
{
    $new_columns = [];
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        if ('order_total' === $column_name) {
            $new_columns['wppam_profit'] = 'Profit';
        }
    }
    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'wppam_display_profit_column', 10, 1);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'wppam_display_profit_column', 10, 2);

function wppam_display_profit_column($column, $order_or_id = null)
{
    if ('wppam_profit' === $column) {
        $order = ($order_or_id instanceof WC_Order) ? $order_or_id : wc_get_order($order_or_id);
        if (!$order) {
            global $post;
            $order = wc_get_order($post->ID);
        }

        if ($order) {
            $data = wppam_get_order_profit_data($order);
            $color = $data['profit'] >= 0 ? 'var(--wppam-success, #22c55e)' : 'var(--wppam-danger, #ef4444)';
            echo '<span style="font-weight:700; color:' . $color . '">' . wc_price($data['profit']) . '</span>';
        }
    }
}
