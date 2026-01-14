<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch spend from Google Ads API and save as an expense.
 * 
 * Note: This implements the logic for fetching daily spend from Google Ads API.
 * 
 * @param string $date Date in YYYY-MM-DD format.
 * @return float|WP_Error Amount synced or error.
 */
function wppam_sync_google_ads_spend($date)
{
    $developer_token = get_option('wppam_google_developer_token');
    $client_id = get_option('wppam_google_client_id');
    $client_secret = get_option('wppam_google_client_secret');
    $refresh_token = get_option('wppam_google_refresh_token');
    $customer_id = get_option('wppam_google_customer_id'); // Format: 1234567890 (no dashes)
    $category = get_option('wppam_google_expense_category', 'Marketing');

    if (empty($developer_token) || empty($client_id) || empty($client_secret) || empty($refresh_token) || empty($customer_id)) {
        return new WP_Error('missing_credentials', 'Google Ads credentials not configured.');
    }

    // 1. Get Access Token using Refresh Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_response = wp_remote_post($token_url, [
        'body' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ]
    ]);

    if (is_wp_error($token_response)) {
        return $token_response;
    }

    $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
    if (!isset($token_data['access_token'])) {
        return new WP_Error('google_auth_error', 'Failed to retrieve access token: ' . ($token_data['error_description'] ?? 'Unknown error'));
    }

    $access_token = $token_data['access_token'];

    // 2. Query Google Ads API (SearchStream)
    // The date format for Google Ads API is YYYY-MM-DD (already provided)
    $query = "SELECT metrics.cost_micros FROM customer WHERE segments.date = '$date'";

    $search_url = "https://googleads.googleapis.com/v17/customers/$customer_id/googleAds:search";

    $response = wp_remote_post($search_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'developer-token' => $developer_token,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'query' => $query
        ])
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error'])) {
        return new WP_Error('google_api_error', $body['error']['message'] ?? 'Unknown Google API error');
    }

    $total_cost = 0;
    if (!empty($body['results'])) {
        foreach ($body['results'] as $result) {
            // Google Ads returns cost in micros (1/1,000,000)
            if (isset($result['metrics']['costMicros'])) {
                $total_cost += (float) $result['metrics']['costMicros'] / 1000000;
            }
        }
    }

    if ($total_cost > 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'wppam_expenses';
        $description = "Google Ads Spend (Auto-synced)";

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE expense_date = %s AND description = %s",
            $date,
            $description
        ));

        if ($exists) {
            $wpdb->update($table, [
                'amount' => $total_cost,
                'category' => $category
            ], ['id' => $exists]);
        } else {
            $wpdb->insert($table, [
                'amount' => $total_cost,
                'category' => $category,
                'expense_date' => $date,
                'description' => $description
            ]);
        }
    }

    return $total_cost;
}

/**
 * Schedule WP-Cron for daily sync.
 */
add_action('wp', function () {
    if (get_option('wppam_google_auto_sync') === 'yes') {
        if (!wp_next_scheduled('wppam_google_ads_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'wppam_google_ads_daily_sync');
        }
    } else {
        $timestamp = wp_next_scheduled('wppam_google_ads_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wppam_google_ads_daily_sync');
        }
    }
});

/**
 * Hook the cron event.
 */
add_action('wppam_google_ads_daily_sync', function () {
    // Sync for yesterday as today's spend might be incomplete
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    wppam_sync_google_ads_spend($yesterday);
});
