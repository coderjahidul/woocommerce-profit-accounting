<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display and handle Settings page.
 */
function wppam_settings_page()
{
    if (isset($_POST['wppam_save_settings_nonce']) && wp_verify_nonce($_POST['wppam_save_settings_nonce'], 'wppam_save_settings')) {
        update_option('wppam_fb_access_token', sanitize_text_field($_POST['fb_access_token']));
        update_option('wppam_fb_ad_account_id', sanitize_text_field($_POST['fb_ad_account_id']));
        update_option('wppam_fb_expense_category', sanitize_text_field($_POST['fb_expense_category']));
        update_option('wppam_fb_auto_sync', isset($_POST['fb_auto_sync']) ? 'yes' : 'no');

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    $fb_access_token = get_option('wppam_fb_access_token', '');
    $fb_ad_account_id = get_option('wppam_fb_ad_account_id', '');
    $fb_expense_category = get_option('wppam_fb_expense_category', 'Marketing');
    $fb_auto_sync = get_option('wppam_fb_auto_sync', 'no');

    $categories = ['Rent', 'Salary', 'Godown', 'Marketing', 'Shipping', 'Utility', 'Other'];

    ?>
    <div class="wrap wppam-dashboard-wrapper wppam-animate">
        <div class="wppam-header">
            <h1>Settings & Integrations</h1>
        </div>

        <div class="wppam-table-card" style="max-width: 600px; padding: 30px;">
            <h2 style="margin-top:0;"><span class="dashicons dashicons-facebook"
                    style="vertical-align: middle; margin-right: 10px;"></span>Facebook Ads Integration</h2>
            <p style="color: var(--wppam-text-muted); margin-bottom: 25px;">Automatically fetch your daily Facebook Ad spend
                and record it as a business expense.</p>

            <form method="POST">
                <?php wp_nonce_field('wppam_save_settings', 'wppam_save_settings_nonce'); ?>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Facebook Access Token</label>
                    <input type="password" name="fb_access_token" value="<?php echo esc_attr($fb_access_token); ?>"
                        style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="EAA×××">
                    <p class="description">Generate a "Long-lived" User Access Token or System User Access Token from
                        Facebook Business Manager with <code>ads_read</code> permission.</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Ad Account ID</label>
                    <input type="text" name="fb_ad_account_id" value="<?php echo esc_attr($fb_ad_account_id); ?>"
                        style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="act_123456789">
                    <p class="description">Include the "act_" prefix. You can find this in your Facebook Ads Manager URL.
                    </p>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Expense Category</label>
                    <select name="fb_expense_category" style="width: 100%; border-radius: 6px; padding: 8px;">
                        <?php
                        foreach ($categories as $cat) {
                            $selected = ($fb_expense_category == $cat) ? 'selected' : '';
                            echo "<option value='$cat' $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; font-weight: 600;">
                        <input type="checkbox" name="fb_auto_sync" value="yes" <?php checked($fb_auto_sync, 'yes'); ?>
                        style="margin-right: 10px;">
                        Enable Auto Daily Sync (WP-Cron)
                    </label>
                </div>

                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="wppam-btn-primary" style="padding: 10px 25px;">Save Settings</button>
                    <?php if ($fb_access_token && $fb_ad_account_id): ?>
                        <button type="submit" name="wppam_fb_sync_now" value="1" class="wppam-btn-secondary"
                            style="padding: 10px 25px; border: 1px solid var(--wppam-primary); color: var(--wppam-primary);">
                            <span class="dashicons dashicons-update" style="font-size: 18px; margin-top: 2px;"></span> Sync
                            Spend for Today
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php
            // Handle Manual Sync
            if (isset($_POST['wppam_fb_sync_now']) && $_POST['wppam_fb_sync_now'] == '1') {
                if (function_exists('wppam_sync_fb_ads_spend')) {
                    $result = wppam_sync_fb_ads_spend(date('Y-m-d'));
                    if (is_wp_error($result)) {
                        echo '<div class="notice notice-error is-dismissible" style="margin-top:20px;"><p>Error: ' . $result->get_error_message() . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success is-dismissible" style="margin-top:20px;"><p>Successfully synced ' . wc_price($result) . ' for today!</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-error is-dismissible" style="margin-top:20px;"><p>Sync function not found. Please ensure the implementation is complete.</p></div>';
                }
            }
            ?>
        </div>
    </div>
    <?php
}
