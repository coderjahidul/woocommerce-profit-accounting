<?php
/**
 * Plugin Name: WooCommerce Profit & Accounting Manager
 * Plugin URI:  https://github.com/coderjahidul/woocommerce-profit-accounting
 * Description: Real-time net profit tracking, COGS management, and detailed financial reports for WooCommerce.
 * Version:     2.0.0
 * Author:      Jahidul Islam
 * Author URI:  https://github.com/coderjahidul
 * Text Domain: woocommerce-profit-accounting
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('WPPAM_VERSION', '2.0.0');
define('WPPAM_PATH', plugin_dir_path(__FILE__));
define('WPPAM_URL', plugin_dir_url(__FILE__));

/**
 * Activation Logic
 */
require_once WPPAM_PATH . 'includes/class-wppam-activator.php';
register_activation_hook(__FILE__, ['WPPAM_Activator', 'activate']);

/**
 * Core Logic & Helpers
 */
require_once WPPAM_PATH . 'includes/functions-calculations.php';
require_once WPPAM_PATH . 'includes/functions-expenses.php';
require_once WPPAM_PATH . 'includes/functions-inventory.php';
require_once WPPAM_PATH . 'includes/functions-facebook-ads.php';
require_once WPPAM_PATH . 'includes/functions-google-ads.php';

/**
 * Admin Interface
 */
if (is_admin()) {
    require_once WPPAM_PATH . 'admin/admin-assets.php';
    require_once WPPAM_PATH . 'admin/admin-menu.php';
    require_once WPPAM_PATH . 'admin/admin-meta-boxes.php';
    require_once WPPAM_PATH . 'admin/admin-order-list.php';

    // UI Pages
    require_once WPPAM_PATH . 'admin/admin-dashboard.php';
    require_once WPPAM_PATH . 'admin/admin-expenses.php';
    require_once WPPAM_PATH . 'admin/admin-info.php';
    require_once WPPAM_PATH . 'admin/admin-settings.php';

    // Reports
    require_once WPPAM_PATH . 'admin/reports/report-daily.php';
    require_once WPPAM_PATH . 'admin/reports/report-yearly.php';
    require_once WPPAM_PATH . 'admin/reports/report-inventory.php';

    // Exports
    require_once WPPAM_PATH . 'admin/exports/export-csv.php';
    require_once WPPAM_PATH . 'admin/exports/export-pdf.php';
}