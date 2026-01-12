<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Cost (COGS) Field in Product Pricing.
 */
add_action('woocommerce_product_options_pricing', function () {
    woocommerce_wp_text_input([
        'id' => '_cost_price',
        'label' => 'Product Cost (COGS)',
        'description' => 'Enter the purchase cost of this product for profit calculation.',
        'type' => 'number',
        'custom_attributes' => ['step' => 'any', 'min' => '0'],
    ]);
});

add_action('woocommerce_product_after_variable_attributes', function ($loop, $variation_data, $variation) {
    woocommerce_wp_text_input([
        'id' => '_cost_price[' . $loop . ']',
        'label' => 'Product Cost (COGS)',
        'description' => 'Enter the purchase cost of this variation.',
        'value' => get_post_meta($variation->ID, '_cost_price', true),
        'type' => 'number',
        'custom_attributes' => ['step' => 'any', 'min' => '0'],
    ]);
}, 10, 3);

add_action('woocommerce_process_product_meta', function ($post_id) {
    if (isset($_POST['_cost_price'])) {
        update_post_meta($post_id, '_cost_price', wc_clean($_POST['_cost_price']));
    }
});

add_action('woocommerce_save_product_variation', function ($variation_id, $i) {
    if (isset($_POST['_cost_price'][$i])) {
        update_post_meta($variation_id, '_cost_price', wc_clean($_POST['_cost_price'][$i]));
    }
}, 10, 2);

/**
 * Add Profit Breakdown meta box to Single Order page.
 */
add_action('add_meta_boxes', function () {
    $screens = ['shop_order', 'woocommerce_page_wc-orders'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wppam_order_profit_breakdown',
            'Profit Breakdown',
            'wppam_order_profit_meta_box_html',
            $screen,
            'side',
            'high'
        );
    }
});

function wppam_order_profit_meta_box_html($post_or_order)
{
    $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
    if (!$order) return;

    $data = wppam_get_order_profit_data($order);
    ?>
    <div class="wppam-meta-box-content">
        <p style="display: flex; justify-content: space-between; margin: 8px 0;">
            <span>Revenue:</span>
            <span><?php echo wc_price($data['revenue']); ?></span>
        </p>
        <p style="display: flex; justify-content: space-between; margin: 8px 0;">
            <span>COGS:</span>
            <span><?php echo wc_price($data['cogs']); ?></span>
        </p>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <p style="display: flex; justify-content: space-between; margin: 8px 0; font-weight: 700;">
            <span>Net Profit:</span>
            <span style="color: <?php echo $data['profit'] >= 0 ? '#22c55e' : '#ef4444'; ?>">
                <?php echo wc_price($data['profit']); ?>
            </span>
        </p>
    </div>
    <?php
}
