<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get total expenses for a specific month and year.
 */
function wppam_get_monthly_expenses($year, $month)
{
    global $wpdb;
    $table = $wpdb->prefix . 'wppam_expenses';
    return (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount) FROM $table
        WHERE YEAR(expense_date)=%d AND MONTH(expense_date)=%d
    ", $year, $month));
}
