<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch spend from TikTok Ads API and save as an expense.
 * 
 * @param string $date Date in YYYY-MM-DD format.
 * @return float|WP_Error Amount synced or error.
 */
function wppam_sync_tiktok_ads_spend($date)
{
    $access_token = get_option('wppam_tiktok_access_token');
    $advertiser_id = get_option('wppam_tiktok_advertiser_id');
    $category = get_option('wppam_tiktok_expense_category', 'Marketing');

    if (empty($access_token) || empty($advertiser_id)) {
        return new WP_Error('missing_credentials', 'TikTok Ads credentials not configured.');
    }

    // TikTok Business API URL
    // Endpoint: /report/integrated/get/
    $url = 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/';

    $params = [
        'advertiser_id' => $advertiser_id,
        'report_type' => 'BASIC',
        'data_level' => 'AUCTION_ADVERTISER',
        'dimensions' => json_encode(['stat_time_day']),
        'metrics' => json_encode(['spend']),
        'start_date' => $date,
        'end_date' => $date,
    ];

    $query_url = add_query_arg($params, $url);

    $response = wp_remote_get($query_url, [
        'headers' => [
            'Access-Token' => $access_token,
            'Content-Type' => 'application/json',
        ]
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['code']) && $body['code'] !== 0) {
        return new WP_Error('tiktok_api_error', $body['message'] ?? 'Unknown TikTok API error');
    }

    $spend = 0;
    if (!empty($body['data']['list'])) {
        foreach ($body['data']['list'] as $row) {
            if (isset($row['metrics']['spend'])) {
                $spend += (float) $row['metrics']['spend'];
            }
        }
    }

    if ($spend > 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'wppam_expenses';
        $description = "TikTok Ads Spend (Auto-synced)";

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
    if (get_option('wppam_tiktok_auto_sync') === 'yes') {
        if (!wp_next_scheduled('wppam_tiktok_ads_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'wppam_tiktok_ads_daily_sync');
        }
    } else {
        $timestamp = wp_next_scheduled('wppam_tiktok_ads_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wppam_tiktok_ads_daily_sync');
        }
    }
});

/**
 * Hook the cron event.
 */
add_action('wppam_tiktok_ads_daily_sync', function () {
    // Sync for yesterday as today's spend might be incomplete
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    wppam_sync_tiktok_ads_spend($yesterday);
});
