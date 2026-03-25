<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin activation logic.
 */
class WPPAM_Activator
{
    /**
     * Create necessary database tables.
     */
    public static function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppam_expenses';
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Manual Migration: Rename 'title' to 'description' if 'title' exists and 'description' doesn't
        $column_title = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'title'));
        $column_description = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'description'));

        if (!empty($column_title) && empty($column_description)) {
            $wpdb->query("ALTER TABLE $table_name CHANGE title description text");
        }

        // 2. Use dbDelta for structural updates
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            category varchar(100) NOT NULL,
            account varchar(50) NOT NULL DEFAULT 'cash', -- 'cash', 'bank', 'mfs'
            expense_date date NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        $cash_table = $wpdb->prefix . 'wppam_cash_ledger';
        $sql .= "CREATE TABLE $cash_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            type varchar(20) NOT NULL, -- 'in' or 'out'
            account varchar(50) NOT NULL DEFAULT 'cash', -- 'cash', 'bank', 'mfs'
            category varchar(100) NOT NULL,
            transaction_date date NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        $purchase_table = $wpdb->prefix . 'wppam_purchases';
        $sql .= "CREATE TABLE $purchase_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            invoice_no varchar(100) NOT NULL,
            product_id bigint(20) NOT NULL,
            variation_id bigint(20) NOT NULL DEFAULT 0,
            quantity int(11) NOT NULL,
            purchase_price decimal(10,2) NOT NULL,
            total_amount decimal(10,2) NOT NULL,
            purchase_date date NOT NULL,
            supplier varchar(255),
            payment_account varchar(50) NOT NULL DEFAULT 'cash', -- 'cash', 'bank', 'mfs'
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
