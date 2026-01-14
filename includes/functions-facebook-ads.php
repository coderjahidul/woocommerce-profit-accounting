<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch spend from Facebook Marketing API and save as an expense.
 * 
 * @param string $date Date in YYYY-MM-DD format.
 * @return float|WP_Error Amount synced or error.
 */
function wppam_sync_fb_ads_spend($date)
{
    $access_token = get_option('wppam_fb_access_token');
    $ad_account_id = get_option('wppam_fb_ad_account_id');
    $category = get_option('wppam_fb_expense_category', 'Marketing');

    if (empty($access_token) || empty($ad_account_id)) {
        return new WP_Error('missing_credentials', 'Facebook Ads credentials not configured.');
    }

    // Prepare API URL
    // Endpoint: /{ad-account-id}/insights
    $url = sprintf(
        'https://graph.facebook.com/v18.0/%s/insights?fields=spend&time_range={"since":"%s","until":"%s"}&access_token=%s',
        $ad_account_id,
        $date,
        $date,
        $access_token
    );

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error'])) {
        return new WP_Error('fb_api_error', $body['error']['message']);
    }

    $spend = 0;
    if (!empty($body['data']) && isset($body['data'][0]['spend'])) {
        $spend = (float) $body['data'][0]['spend'];
    }

    if ($spend > 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'wppam_expenses';

        // Check if expense already exists for this date and 'Facebook Ads' description to avoid duplicates
        $description = "Facebook Ads Spend (Auto-synced)";
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE expense_date = %s AND description = %s",
            $date,
            $description
        ));

        if ($exists) {
            $wpdb->update($table, [
                'amount' => $spend,
                'category' => $category
            ], ['id' => $exists]);
        } else {
            $wpdb->insert($table, [
                'amount' => $spend,
                'category' => $category,
                'expense_date' => $date,
                'description' => $description
            ]);
        }
    }

    return $spend;
}

/**
 * Schedule WP-Cron for daily sync.
 */
add_action('wp', function () {
    if (get_option('wppam_fb_auto_sync') === 'yes') {
        if (!wp_next_scheduled('wppam_fb_ads_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'wppam_fb_ads_daily_sync');
        }
    } else {
        $timestamp = wp_next_scheduled('wppam_fb_ads_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wppam_fb_ads_daily_sync');
        }
    }
});

/**
 * Hook the cron event.
 */
add_action('wppam_fb_ads_daily_sync', function () {
    // Sync for yesterday as today's spend might be incomplete
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    wppam_sync_fb_ads_spend($yesterday);
});
