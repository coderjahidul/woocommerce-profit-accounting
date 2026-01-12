<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu and Submenus.
 */
add_action('admin_menu', function () {
    add_menu_page('Profit Manager', 'Profit Manager', 'manage_options', 'wppam', 'wppam_dashboard', 'dashicons-chart-line');
    add_submenu_page('wppam', 'Daily Report', 'Daily Report', 'manage_options', 'wppam-daily', 'wppam_daily_report');
    add_submenu_page('wppam', 'Yearly Report', 'Yearly Report', 'manage_options', 'wppam-yearly', 'wppam_yearly_report');
    add_submenu_page('wppam', 'Inventory Report', 'Inventory Report', 'manage_options', 'wppam-inventory', 'wppam_inventory_report');
    add_submenu_page('wppam', 'Add Expense', 'Add Expense', 'manage_options', 'wppam-add-expense', 'wppam_add_expense');
    add_submenu_page('wppam', 'Plugin Info', 'Plugin Info', 'manage_options', 'wppam-info', 'wppam_info_page');
    
    // Hidden detailing pages
    add_submenu_page(null, 'Daily Details', 'Daily Details', 'manage_options', 'wppam-daily-details', 'wppam_daily_details_report');
    add_submenu_page(null, 'Monthly Details', 'Monthly Details', 'manage_options', 'wppam-monthly-details', 'wppam_monthly_details_report');
});
