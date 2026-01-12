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
            expense_date date NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
