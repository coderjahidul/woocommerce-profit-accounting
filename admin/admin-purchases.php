<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle and display Add Purchase form with invoice support.
 */
function wppam_purchases_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_purchases';

    // Auto-check if invoice_no column exists (Schema Update Self-Healing)
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE 'invoice_no'");
    if (empty($column_exists)) {
        if (class_exists('WPPAM_Activator')) {
            WPPAM_Activator::activate();
        }
    }

    // Handle Invoice Deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete_invoice' && isset($_GET['invoice_no'])) {
        $invoice_no = sanitize_text_field($_GET['invoice_no']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_invoice_' . $invoice_no)) {
            wppam_delete_invoice($invoice_no);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Invoice %s deleted successfully!', 'woocommerce-profit-accounting'), $invoice_no) . '</p></div>';
        }
    }

    // Handle Bulk Form Submission (Invoice Based)
    if (isset($_POST['wppam_bulk_purchase_nonce']) && wp_verify_nonce($_POST['wppam_bulk_purchase_nonce'], 'wppam_bulk_purchase')) {
        $items = [];
        $invoice_no = sanitize_text_field($_POST['invoice_no']);

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                $product_data = explode(':', sanitize_text_field($item['product_id_combined']));
                $product_id = isset($product_data[0]) ? (int)$product_data[0] : 0;
                $variation_id = isset($product_data[1]) ? (int)$product_data[1] : 0;
                
                if ($product_id > 0) {
                    $items[] = [
                        'product_id' => $product_id,
                        'variation_id' => $variation_id,
                        'quantity' => (int)$item['quantity'],
                        'purchase_price' => (float)$item['purchase_price'],
                    ];
                }
            }
        }

        $common_data = [
            'invoice_no' => $invoice_no,
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'supplier' => sanitize_text_field($_POST['supplier']),
            'notes' => sanitize_textarea_field($_POST['notes']),
        ];

        if (!empty($items) && !empty($invoice_no)) {
            $result = wppam_add_bulk_purchase($items, $common_data);
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Purchase Invoice recorded successfully!', 'woocommerce-profit-accounting') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to record purchase invoice.', 'woocommerce-profit-accounting') . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>' . __('Invoice number and items are required.', 'woocommerce-profit-accounting') . '</p></div>';
        }
    }

    $invoices = wppam_get_invoices(50);
    $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1><?php _e('Purchase Invoice Management', 'woocommerce-profit-accounting'); ?></h1>
        </div>

        <form method="POST" action="">
            <?php wp_nonce_field('wppam_bulk_purchase', 'wppam_bulk_purchase_nonce'); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start;">
                <!-- Main Items Table -->
                <div class="wppam-table-card" style="padding: 0;">
                    <div style="padding: 20px 24px; border-bottom: 1px solid var(--wppam-border); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin:0;"><?php _e('Invoice Items', 'woocommerce-profit-accounting'); ?></h3>
                        <button type="button" id="wppam-add-row" class="wppam-btn-secondary" style="font-size: 11px;">+ Add Product</button>
                    </div>
                    
                    <table class="wppam-custom-table" id="wppam-purchase-items">
                        <thead>
                            <tr>
                                <th style="width: 50%;"><?php _e('Product', 'woocommerce-profit-accounting'); ?></th>
                                <th><?php _e('Quantity', 'woocommerce-profit-accounting'); ?></th>
                                <th><?php _e('Unit Price', 'woocommerce-profit-accounting'); ?></th>
                                <th><?php _e('Total', 'woocommerce-profit-accounting'); ?></th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="wppam-item-row">
                                <td>
                                    <select name="items[0][product_id_combined]" class="wppam-select2-custom" style="width: 100%;" required>
                                        <option value=""><?php _e('-- Select Product --', 'woocommerce-profit-accounting'); ?></option>
                                        <?php foreach ($products as $product): ?>
                                            <?php if ($product->is_type('variable')): ?>
                                                <optgroup label="<?php echo esc_attr($product->get_name()); ?>">
                                                    <?php foreach ($product->get_children() as $variation_id): ?>
                                                        <?php $variation = wc_get_product($variation_id); ?>
                                                        <option value="<?php echo $product->get_id() . ':' . $variation_id; ?>">
                                                            <?php echo esc_html($variation->get_name()); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php else: ?>
                                                <option value="<?php echo $product->get_id() . ':0'; ?>">
                                                    <?php echo esc_html($product->get_name()); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="wppam-qty" min="1" required style="width: 80px;"></td>
                                <td><input type="number" name="items[0][purchase_price]" class="wppam-price" step="any" min="0" required style="width: 100px;"></td>
                                <td class="wppam-row-total">0.00</td>
                                <td><button type="button" class="wppam-remove-row" style="background:none; border:none; color:var(--wppam-danger); cursor:pointer;">&times;</button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: 700; padding: 15px;"><?php _e('Grand Total:', 'woocommerce-profit-accounting'); ?></td>
                                <td id="wppam-grand-total" style="font-weight: 800; font-size: 16px; color: var(--wppam-primary);">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Secondary Data sidebar -->
                <div class="wppam-table-card" style="padding: 24px;">
                    <h3 style="margin-top:0;"><?php _e('Invoice Details', 'woocommerce-profit-accounting'); ?></h3>
                    <div style="margin-bottom: 15px;">
                        <label><?php _e('Invoice Number', 'woocommerce-profit-accounting'); ?> <span style="color:red;">*</span></label>
                        <input type="text" name="invoice_no" placeholder="INV-001" required style="width: 100%; border-radius: 6px; padding: 8px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label><?php _e('Purchase Date', 'woocommerce-profit-accounting'); ?></label>
                        <input type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; border-radius: 6px; padding: 8px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label><?php _e('Supplier', 'woocommerce-profit-accounting'); ?></label>
                        <input type="text" name="supplier" style="width: 100%; border-radius: 6px; padding: 8px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label><?php _e('Notes', 'woocommerce-profit-accounting'); ?></label>
                        <textarea name="notes" style="width: 100%; border-radius: 6px; padding: 8px;"></textarea>
                    </div>
                    <button type="submit" class="wppam-btn-primary" style="width:100%;"><?php _e('Save Purchase Invoice', 'woocommerce-profit-accounting'); ?></button>
                </div>
            </div>
        </form>

        <!-- History -->
        <div class="wppam-table-card" style="margin-top: 30px;">
            <h3 style="padding: 24px; margin: 0; border-bottom: 1px solid var(--wppam-border);"><?php _e('Purchase Invoice History', 'woocommerce-profit-accounting'); ?></h3>
            <div style="overflow-x: auto;">
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th><?php _e('Invoice #', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Date', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Supplier', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Items', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Total Amount', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Action', 'woocommerce-profit-accounting'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($invoices): ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($inv->invoice_no); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($inv->purchase_date)); ?></td>
                                    <td><?php echo esc_html($inv->supplier); ?></td>
                                    <td><span class="wppam-badge"><?php echo (int)$inv->items_count; ?> <?php _e('Items', 'woocommerce-profit-accounting'); ?></span></td>
                                    <td style="font-weight:700; color: var(--wppam-danger);"><?php echo wc_price($inv->total_amount); ?></td>
                                    <td>
                                        <button type="button" class="wppam-btn-secondary wppam-view-invoice" 
                                                data-invoice="<?php echo esc_attr($inv->invoice_no); ?>" 
                                                data-date="<?php echo date('M d, Y', strtotime($inv->purchase_date)); ?>"
                                                data-supplier="<?php echo esc_attr($inv->supplier); ?>"
                                                style="font-size: 11px; padding: 4px 10px;">
                                            <?php _e('View Invoice', 'woocommerce-profit-accounting'); ?>
                                        </button>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wppam-purchases&action=delete_invoice&invoice_no=' . urlencode($inv->invoice_no)), 'delete_invoice_' . $inv->invoice_no); ?>" 
                                           class="wppam-btn-secondary wppam-text-danger" style="font-size: 11px; padding: 4px 10px;"
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this entire invoice?', 'woocommerce-profit-accounting'); ?>');"><?php _e('Delete', 'woocommerce-profit-accounting'); ?></a>
                                    </td>
                                </tr>
                                <!-- Data for Modal -->
                                <template id="items-data-<?php echo esc_attr(sanitize_title($inv->invoice_no)); ?>">
                                    <?php 
                                    $items = wppam_get_invoice_items($inv->invoice_no);
                                    foreach ($items as $item): 
                                        $target_id = $item->variation_id ?: $item->product_id;
                                        $product_obj = wc_get_product($target_id);
                                        $p_name = $product_obj ? $product_obj->get_name() : __('Deleted Product', 'woocommerce-profit-accounting');
                                    ?>
                                        <tr>
                                            <td style="padding:12px; border-bottom:1px solid #eee;"><?php echo esc_html($p_name); ?></td>
                                            <td style="padding:12px; border-bottom:1px solid #eee;"><?php echo (int)$item->quantity; ?></td>
                                            <td style="padding:12px; border-bottom:1px solid #eee;"><?php echo wc_price($item->purchase_price); ?></td>
                                            <td style="padding:12px; border-bottom:1px solid #eee; font-weight:700;"><?php echo wc_price($item->total_amount); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </template>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6"><?php _e('No invoices recorded yet.', 'woocommerce-profit-accounting'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reusable Modal -->
    <div id="wppam-invoice-modal" class="wppam-modal" style="display:none;">
        <div class="wppam-modal-content">
            <div class="wppam-modal-header">
                <h2 id="modal-title"></h2>
                <span class="wppam-modal-close">&times;</span>
            </div>
            <div class="wppam-modal-body">
                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; font-size: 13px; color: #666;">
                    <span id="modal-date"></span>
                    <span id="modal-supplier"></span>
                </div>
                <table class="wppam-custom-table">
                    <thead>
                        <tr>
                            <th><?php _e('Product Name', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Qty', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Unit Price', 'woocommerce-profit-accounting'); ?></th>
                            <th><?php _e('Total', 'woocommerce-profit-accounting'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="modal-items-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal CSS -->
    <style>
        .wppam-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .wppam-modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 800px;
            max-width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .wppam-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .wppam-modal-header h2 { margin: 0; font-size: 20px; color: #1e293b; }
        .wppam-modal-close { font-size: 28px; font-weight: bold; cursor: pointer; color: #94a3b8; line-height: 1; }
        .wppam-modal-close:hover { color: #1e293b; }
    </style>

    <!-- Template for JS -->
    <script type="text/template" id="wppam-row-template">
        <tr class="wppam-item-row">
            <td>
                <select name="items[REPLACE_ID][product_id_combined]" class="wppam-select2-custom" style="width: 100%;" required>
                    <option value=""><?php _e('-- Select Product --', 'woocommerce-profit-accounting'); ?></option>
                    <?php foreach ($products as $product): ?>
                        <?php if ($product->is_type('variable')): ?>
                            <optgroup label="<?php echo esc_attr($product->get_name()); ?>">
                                <?php foreach ($product->get_children() as $variation_id): ?>
                                    <?php $variation = wc_get_product($variation_id); ?>
                                    <option value="<?php echo $product->get_id() . ':' . $variation_id; ?>">
                                        <?php echo esc_html($variation->get_name()); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php else: ?>
                            <option value="<?php echo $product->get_id() . ':0'; ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" name="items[REPLACE_ID][quantity]" class="wppam-qty" min="1" required style="width: 80px;"></td>
            <td><input type="number" name="items[REPLACE_ID][purchase_price]" class="wppam-price" step="any" min="0" required style="width: 100px;"></td>
            <td class="wppam-row-total">0.00</td>
            <td><button type="button" class="wppam-remove-row" style="background:none; border:none; color:var(--wppam-danger); cursor:pointer;">&times;</button></td>
        </tr>
    </script>

    <script>
        jQuery(document).ready(function($) {
            let rowCount = 1;

            function initSelect2(element) {
                if ($.fn.select2) {
                    // Destroy if already initialized (precaution)
                    if (element.hasClass('select2-hidden-accessible')) {
                        element.select2('destroy');
                    }
                    element.select2({
                        placeholder: 'Select a product',
                        allowClear: true,
                        width: '100%'
                    });
                }
            }

            // Init first row - use unique class to avoid conflict with global init
            initSelect2($('#wppam-purchase-items .wppam-select2-custom'));

            $('#wppam-add-row').on('click', function() {
                let templateHtml = $('#wppam-row-template').html();
                let cleanHtml = templateHtml.replace(/REPLACE_ID/g, rowCount);
                let newRow = $(cleanHtml);
                
                $('#wppam-purchase-items tbody').append(newRow);
                
                // Init Select2 only on the new row's specific select
                initSelect2(newRow.find('.wppam-select2-custom'));
                
                rowCount++;
            });

            $(document).on('click', '.wppam-remove-row', function() {
                if ($('#wppam-purchase-items tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateGrandTotal();
                }
            });

            $(document).on('input', '.wppam-qty, .wppam-price', function() {
                let row = $(this).closest('tr');
                let qty = parseFloat(row.find('.wppam-qty').val()) || 0;
                let price = parseFloat(row.find('.wppam-price').val()) || 0;
                let total = qty * price;
                row.find('.wppam-row-total').text(total.toFixed(2));
                calculateGrandTotal();
            });

            function calculateGrandTotal() {
                let grandTotal = 0;
                $('.wppam-row-total').each(function() {
                    grandTotal += parseFloat($(this).text()) || 0;
                });
                $('#wppam-grand-total').text(grandTotal.toFixed(2));
            }

            // Modal Logic
            $('.wppam-view-invoice').on('click', function() {
                let invoice = $(this).data('invoice');
                let date = $(this).data('date');
                let supplier = $(this).data('supplier');
                let sanitizedId = invoice.toLowerCase().replace(/[^a-z0-9]/g, '-');
                let itemsHtml = $('#items-data-' + sanitizedId).html();

                $('#modal-title').text('<?php _e('Invoice:', 'woocommerce-profit-accounting'); ?> ' + invoice);
                $('#modal-date').text('<?php _e('Date:', 'woocommerce-profit-accounting'); ?> ' + date);
                $('#modal-supplier').text('<?php _e('Supplier:', 'woocommerce-profit-accounting'); ?> ' + supplier);
                $('#modal-items-body').html(itemsHtml);
                $('#wppam-invoice-modal').fadeIn(200);
            });

            $('.wppam-modal-close, .wppam-modal').on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('wppam-modal-close')) {
                    $('#wppam-invoice-modal').fadeOut(200);
                }
            });
        });
    </script>
    <?php
}
