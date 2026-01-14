<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display and handle Settings page.
 */
function wppam_settings_page()
{
    // Handle Savings
    if (isset($_POST['wppam_save_settings_nonce']) && wp_verify_nonce($_POST['wppam_save_settings_nonce'], 'wppam_save_settings')) {
        // Only update if the field exists in POST to avoid overwriting with empty values from other tabs (though we use 1 form now)
        if (isset($_POST['fb_access_token']))
            update_option('wppam_fb_access_token', sanitize_text_field($_POST['fb_access_token']));
        if (isset($_POST['fb_ad_account_id']))
            update_option('wppam_fb_ad_account_id', sanitize_text_field($_POST['fb_ad_account_id']));
        if (isset($_POST['fb_expense_category']))
            update_option('wppam_fb_expense_category', sanitize_text_field($_POST['fb_expense_category']));
        update_option('wppam_fb_auto_sync', isset($_POST['fb_auto_sync']) ? 'yes' : 'no');

        // Google Ads Settings
        if (isset($_POST['google_developer_token']))
            update_option('wppam_google_developer_token', sanitize_text_field($_POST['google_developer_token']));
        if (isset($_POST['google_client_id']))
            update_option('wppam_google_client_id', sanitize_text_field($_POST['google_client_id']));
        if (isset($_POST['google_client_secret']))
            update_option('wppam_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        if (isset($_POST['google_refresh_token']))
            update_option('wppam_google_refresh_token', sanitize_text_field($_POST['google_refresh_token']));
        if (isset($_POST['google_customer_id']))
            update_option('wppam_google_customer_id', sanitize_text_field($_POST['google_customer_id']));
        if (isset($_POST['google_expense_category']))
            update_option('wppam_google_expense_category', sanitize_text_field($_POST['google_expense_category']));
        update_option('wppam_google_auto_sync', isset($_POST['google_auto_sync']) ? 'yes' : 'no');

        // TikTok Ads Settings
        if (isset($_POST['tiktok_access_token']))
            update_option('wppam_tiktok_access_token', sanitize_text_field($_POST['tiktok_access_token']));
        if (isset($_POST['tiktok_advertiser_id']))
            update_option('wppam_tiktok_advertiser_id', sanitize_text_field($_POST['tiktok_advertiser_id']));
        if (isset($_POST['tiktok_expense_category']))
            update_option('wppam_tiktok_expense_category', sanitize_text_field($_POST['tiktok_expense_category']));
        update_option('wppam_tiktok_auto_sync', isset($_POST['tiktok_auto_sync']) ? 'yes' : 'no');

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    // Handle Manual Syncs (Before rendering to show notices at top)
    $sync_notice = '';
    if (isset($_POST['wppam_fb_sync_now']) && $_POST['wppam_fb_sync_now'] == '1') {
        if (function_exists('wppam_sync_fb_ads_spend')) {
            $result = wppam_sync_fb_ads_spend(date('Y-m-d'));
            if (is_wp_error($result)) {
                $sync_notice = '<div class="notice notice-error is-dismissible"><p>Facebook Sync Error: ' . $result->get_error_message() . '</p></div>';
            } else {
                $sync_notice = '<div class="notice notice-success is-dismissible"><p>Successfully synced ' . wc_price($result) . ' from Facebook for today!</p></div>';
            }
        }
    } elseif (isset($_POST['wppam_google_sync_now']) && $_POST['wppam_google_sync_now'] == '1') {
        if (function_exists('wppam_sync_google_ads_spend')) {
            $result = wppam_sync_google_ads_spend(date('Y-m-d'));
            if (is_wp_error($result)) {
                $sync_notice = '<div class="notice notice-error is-dismissible"><p>Google Ads Sync Error: ' . $result->get_error_message() . '</p></div>';
            } else {
                $sync_notice = '<div class="notice notice-success is-dismissible"><p>Successfully synced ' . wc_price($result) . ' from Google Ads for today!</p></div>';
            }
        }
    } elseif (isset($_POST['wppam_tiktok_sync_now']) && $_POST['wppam_tiktok_sync_now'] == '1') {
        if (function_exists('wppam_sync_tiktok_ads_spend')) {
            $result = wppam_sync_tiktok_ads_spend(date('Y-m-d'));
            if (is_wp_error($result)) {
                $sync_notice = '<div class="notice notice-error is-dismissible"><p>TikTok Ads Sync Error: ' . $result->get_error_message() . '</p></div>';
            } else {
                $sync_notice = '<div class="notice notice-success is-dismissible"><p>Successfully synced ' . wc_price($result) . ' from TikTok Ads for today!</p></div>';
            }
        }
    }

    $fb_access_token = get_option('wppam_fb_access_token', '');
    $fb_ad_account_id = get_option('wppam_fb_ad_account_id', '');
    $fb_expense_category = get_option('wppam_fb_expense_category', 'Marketing');
    $fb_auto_sync = get_option('wppam_fb_auto_sync', 'no');

    $google_developer_token = get_option('wppam_google_developer_token', '');
    $google_client_id = get_option('wppam_google_client_id', '');
    $google_client_secret = get_option('wppam_google_client_secret', '');
    $google_refresh_token = get_option('wppam_google_refresh_token', '');
    $google_customer_id = get_option('wppam_google_customer_id', '');
    $google_expense_category = get_option('wppam_google_expense_category', 'Marketing');
    $google_auto_sync = get_option('wppam_google_auto_sync', 'no');

    $tiktok_access_token = get_option('wppam_tiktok_access_token', '');
    $tiktok_advertiser_id = get_option('wppam_tiktok_advertiser_id', '');
    $tiktok_expense_category = get_option('wppam_tiktok_expense_category', 'Marketing');
    $tiktok_auto_sync = get_option('wppam_tiktok_auto_sync', 'no');

    $categories = ['Rent', 'Salary', 'Godown', 'Marketing', 'Shipping', 'Utility', 'Other'];
    $active_tab = isset($_POST['active_tab']) ? sanitize_text_field($_POST['active_tab']) : 'facebook';

    echo $sync_notice;
    ?>
        <div class="wrap wppam-dashboard-wrapper wppam-animate">
            <div class="wppam-header">
                <h1>Settings & Integrations</h1>
            </div>

            <form method="POST" id="wppam-settings-form">
                <?php wp_nonce_field('wppam_save_settings', 'wppam_save_settings_nonce'); ?>
                <input type="hidden" name="active_tab" id="wppam_active_tab" value="<?php echo esc_attr($active_tab); ?>">

                <!-- Navigation Tabs -->
                <h2 class="nav-tab-wrapper wppam-tabs-nav" style="border-bottom: 2px solid var(--wppam-border); margin-bottom: 25px; padding-left: 0;">
                    <a href="#facebook" class="nav-tab wppam-tab-btn <?php echo $active_tab == 'facebook' ? 'active' : ''; ?>" data-tab="facebook">
                        <span class="dashicons dashicons-facebook" style="font-size: 17px; margin-top: 3px; margin-right: 5px;"></span> Facebook Ads
                    </a>
                    <a href="#google" class="nav-tab wppam-tab-btn <?php echo $active_tab == 'google' ? 'active' : ''; ?>" data-tab="google">
                        <span class="dashicons dashicons-google" style="font-size: 17px; margin-top: 3px; margin-right: 5px;"></span> Google Ads
                    </a>
                    <a href="#tiktok" class="nav-tab wppam-tab-btn <?php echo $active_tab == 'tiktok' ? 'active' : ''; ?>" data-tab="tiktok">
                        <span class="dashicons dashicons-video-alt3" style="font-size: 17px; margin-top: 3px; margin-right: 5px;"></span> TikTok Ads
                    </a>
                </h2>

                <!-- Facebook Tab -->
                <div id="facebook" class="wppam-tab-content <?php echo $active_tab == 'facebook' ? 'active' : ''; ?>">
                    <div class="wppam-table-card" style="max-width: 650px; padding: 30px;">
                        <h2 style="margin-top:0;">Facebook Ads Integration</h2>
                        <p style="color: var(--wppam-text-muted); margin-bottom: 25px;">Automatically fetch your daily Facebook Ad spend and record it as a business expense.</p>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Facebook Access Token</label>
                            <input type="password" name="fb_access_token" value="<?php echo esc_attr($fb_access_token); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="EAA×××">
                            <p class="description">Generate a "Long-lived" Access Token with <code>ads_read</code> permission.</p>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Ad Account ID</label>
                            <input type="text" name="fb_ad_account_id" value="<?php echo esc_attr($fb_ad_account_id); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="act_123456789">
                            <p class="description">Include the "act_" prefix.</p>
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
                                        <span class="dashicons dashicons-update" style="font-size: 18px; margin-top: 2px;"></span> Sync Spend for Today
                                    </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Google Tab -->
                <div id="google" class="wppam-tab-content <?php echo $active_tab == 'google' ? 'active' : ''; ?>">
                    <div class="wppam-table-card" style="max-width: 650px; padding: 30px;">
                        <h2 style="margin-top:0;">Google Ads Integration</h2>
                        <p style="color: var(--wppam-text-muted); margin-bottom: 25px;">Automatically fetch your daily Google Ads spend and record it as a business expense.</p>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Developer Token</label>
                            <input type="password" name="google_developer_token" value="<?php echo esc_attr($google_developer_token); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="Abc123Xyz...">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Client ID</label>
                            <input type="text" name="google_client_id" value="<?php echo esc_attr($google_client_id); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="xxx.apps.googleusercontent.com">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Client Secret</label>
                            <input type="password" name="google_client_secret" value="<?php echo esc_attr($google_client_secret); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="GOCSPX-...">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Refresh Token</label>
                            <input type="password" name="google_refresh_token" value="<?php echo esc_attr($google_refresh_token); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="1//...">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Customer ID</label>
                            <input type="text" name="google_customer_id" value="<?php echo esc_attr($google_customer_id); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="1234567890">
                            <p class="description">Digits only, no dashes.</p>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Expense Category</label>
                            <select name="google_expense_category" style="width: 100%; border-radius: 6px; padding: 8px;">
                                <?php
                                foreach ($categories as $cat) {
                                    $selected = ($google_expense_category == $cat) ? 'selected' : '';
                                    echo "<option value='$cat' $selected>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; font-weight: 600;">
                                <input type="checkbox" name="google_auto_sync" value="yes" <?php checked($google_auto_sync, 'yes'); ?>
                                style="margin-right: 10px;">
                                Enable Auto Daily Sync (WP-Cron)
                            </label>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <button type="submit" class="wppam-btn-primary" style="padding: 10px 25px;">Save Settings</button>
                            <?php if ($google_refresh_token && $google_customer_id): ?>
                                    <button type="submit" name="wppam_google_sync_now" value="1" class="wppam-btn-secondary"
                                        style="padding: 10px 25px; border: 1px solid var(--wppam-primary); color: var(--wppam-primary);">
                                        <span class="dashicons dashicons-update" style="font-size: 18px; margin-top: 2px;"></span> Sync Spend for Today
                                    </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TikTok Tab -->
                <div id="tiktok" class="wppam-tab-content <?php echo $active_tab == 'tiktok' ? 'active' : ''; ?>">
                    <div class="wppam-table-card" style="max-width: 650px; padding: 30px;">
                        <h2 style="margin-top:0;">TikTok Ads Integration</h2>
                        <p style="color: var(--wppam-text-muted); margin-bottom: 25px;">Automatically fetch your daily TikTok Ads spend and record it as a business expense.</p>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Access Token</label>
                            <input type="password" name="tiktok_access_token" value="<?php echo esc_attr($tiktok_access_token); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="act_...">
                            <p class="description">Long-lived Access Token from TikTok Developers platform.</p>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Advertiser ID</label>
                            <input type="text" name="tiktok_advertiser_id" value="<?php echo esc_attr($tiktok_advertiser_id); ?>"
                                style="width: 100%; border-radius: 6px; padding: 10px;" placeholder="7000000000000000000">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Expense Category</label>
                            <select name="tiktok_expense_category" style="width: 100%; border-radius: 6px; padding: 8px;">
                                <?php
                                foreach ($categories as $cat) {
                                    $selected = ($tiktok_expense_category == $cat) ? 'selected' : '';
                                    echo "<option value='$cat' $selected>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; font-weight: 600;">
                                <input type="checkbox" name="tiktok_auto_sync" value="yes" <?php checked($tiktok_auto_sync, 'yes'); ?>
                                style="margin-right: 10px;">
                                Enable Auto Daily Sync (WP-Cron)
                            </label>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <button type="submit" class="wppam-btn-primary" style="padding: 10px 25px;">Save Settings</button>
                            <?php if ($tiktok_access_token && $tiktok_advertiser_id): ?>
                                    <button type="submit" name="wppam_tiktok_sync_now" value="1" class="wppam-btn-secondary"
                                        style="padding: 10px 25px; border: 1px solid var(--wppam-primary); color: var(--wppam-primary);">
                                        <span class="dashicons dashicons-update" style="font-size: 18px; margin-top: 2px;"></span> Sync Spend for Today
                                    </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.wppam-tab-btn');
                const contents = document.querySelectorAll('.wppam-tab-content');
                const hiddenInput = document.getElementById('wppam_active_tab');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = this.dataset.tab;

                        tabs.forEach(t => t.classList.remove('active'));
                        contents.forEach(c => c.classList.remove('active'));

                        this.classList.add('active');
                        document.getElementById(target).classList.add('active');
                    
                        // Update hidden input and URL hash
                        hiddenInput.value = target;
                        window.location.hash = target;
                    });
                });

                // Handle Hash on load (if exists and no POST active_tab)
                if (window.location.hash && !'<?php echo isset($_POST['active_tab']); ?>') {
                    const hash = window.location.hash.substring(1);
                    const targetTab = document.querySelector(`.wppam-tab-btn[data-tab="${hash}"]`);
                    if (targetTab) {
                        targetTab.click();
                    }
                }
            });
        </script>
        <?php
}
