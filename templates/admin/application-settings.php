<?php
if (! defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// Retrieve options for the first and second servers
$discord_server_id         = sanitize_text_field(trim(get_option('discord_server_id')));
$discord_client_id         = sanitize_text_field(trim(get_option('discord_client_id')));
$discord_client_secret     = sanitize_text_field(trim(get_option('discord_client_secret')));
$discord_bot_token         = sanitize_text_field(trim(get_option('discord_bot_token')));
$discord_bot_redirect_url  = Woo_Discord_Steam_Integration_Utils::get_bot_redirect_url();
$discord_auth_redirect_url = wc_get_checkout_url() . '?via=discord';
$discord_purchase_channel  = sanitize_text_field(trim(get_option('discord_purchase_channel')));
$discord_channels          = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $discord_server_id);

// Retrieve options for the second server
$discord_server_id_2         = sanitize_text_field(trim(get_option('discord_server_id_2')));
$discord_client_id_2         = sanitize_text_field(trim(get_option('discord_client_id_2')));
$discord_client_secret_2     = sanitize_text_field(trim(get_option('discord_client_secret_2')));
$discord_purchase_channel_2  = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $discord_server_id_2);

$first_server_status = Woo_Discord_Steam_Integration_Utils::get_single_server_status($discord_server_id, $discord_bot_token, $discord_client_id, 'First Server');

$steam_web_api_key = sanitize_text_field(trim(get_option('steam_web_api_key')));

?>

<div class="wrap">
    <img class="ets-discord-steam-integration-image" src="<?php echo Woo_Discord_Steam_Integration_Utils::get_settings_page_icon(); ?>" />
    <h1 class="ets-discord-steam-integration-title"><?php esc_html_e('Admin Coalition Login', 'admin-coalition'); ?></h1>

    <!-- First Server Settings -->
    <h2><?php esc_html_e('Discord Details (First Server)', 'admin-coalition'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="discord_server_id"><?php esc_html_e('Discord Server ID', 'admin-coalition'); ?></label></th>
            <td><input type="text" id="discord_server_id" name="discord_server_id" value="<?php echo esc_attr($discord_server_id); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="discord_client_id"><?php esc_html_e('Discord Client ID', 'admin-coalition'); ?></label></th>
            <td><input type="text" id="discord_client_id" name="discord_client_id" value="<?php echo esc_attr($discord_client_id); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="discord_client_secret"><?php esc_html_e('Discord Client Secret', 'admin-coalition'); ?></label></th>
            <td><input type="text" id="discord_client_secret" name="discord_client_secret" value="<?php echo esc_attr($discord_client_secret); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="discord_bot_token"><?php esc_html_e('Discord Bot Token', 'admin-coalition'); ?></label></th>
            <td><input type="password" id="discord_bot_token" name="discord_bot_token" value="<?php echo esc_attr($discord_bot_token); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="discord_purchase_channel"><?php esc_html_e('Discord Purchase Channel', 'admin-coalition'); ?></label></th>
            <td>
                <select id="discord_purchase_channel" name="discord_purchase_channel" class="regular-text">
                    <?php foreach ($discord_channels as $channel_id => $channel_name): ?>
                        <option value="<?php echo esc_attr($channel_id); ?>" <?php selected($discord_purchase_channel, $channel_id); ?>><?php echo esc_html($channel_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <!-- Display Bot Status for First Server -->
    <p><?php echo Woo_Discord_Steam_Integration_Utils::get_single_server_status($discord_server_id, $discord_bot_token, $discord_client_id, 'First Server'); ?></p>

    <!-- "Add More" Button to Add Second Server -->
    <?php if ($first_server_status && strpos($first_server_status, 'Bot connected') !== false): ?>
        <p>
            <button type="button" id="add-second-server" class="button-primary"><?php esc_html_e('Add Second Discord Server', 'admin-coalition'); ?></button>
        </p>
    <?php endif; ?>

    <!-- Second Server Settings (Initially Hidden) -->
    <div id="second-server-settings" style="display:none;">
        <h2><?php esc_html_e('Discord Details (Second Server)', 'admin-coalition'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="discord_server_id_2"><?php esc_html_e('Second Discord Server ID', 'admin-coalition'); ?></label></th>
                <td><input type="text" id="discord_server_id_2" name="discord_server_id_2" value="<?php echo esc_attr($discord_server_id_2); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="discord_client_id_2"><?php esc_html_e('Second Discord Client ID', 'admin-coalition'); ?></label></th>
                <td><input type="text" id="discord_client_id_2" name="discord_client_id_2" value="<?php echo esc_attr($discord_client_id_2); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="discord_client_secret_2"><?php esc_html_e('Second Discord Client Secret', 'admin-coalition'); ?></label></th>
                <td><input type="text" id="discord_client_secret_2" name="discord_client_secret_2" value="<?php echo esc_attr($discord_client_secret_2); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th scope="row"><label for="discord_purchase_channel_2"><?php esc_html_e('Second Discord Purchase Channel', 'admin-coalition'); ?></label></th>
                <td>
                    <select id="discord_purchase_channel_2" name="discord_purchase_channel_2" class="regular-text">
                        <?php foreach ($discord_purchase_channel_2 as $channel_id => $channel_name): ?>
                            <option value="<?php echo esc_attr($channel_id); ?>" <?php selected($discord_purchase_channel_2, $channel_id); ?>><?php echo esc_html($channel_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </div>


    <!-- Steam Settings -->
    <h2><?php esc_html_e('Steam Details', 'admin-coalition'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="steam_web_api_key"><?php esc_html_e('Steam Web API Key', 'admin-coalition'); ?></label></th>
            <td><input type="password" id="steam_web_api_key" name="steam_web_api_key" value="<?php echo esc_attr($steam_web_api_key); ?>" class="regular-text"></td>
        </tr>            
    </table>

    <p class="submit">
        <button type="submit" name="submit" value="ets_discord_submit" class="ets-submit button-primary woocommerce-save-button"><?php esc_html_e('Save Settings', 'admin-coalition'); ?></button>
        <button type="submit" name="reset" value="ets_discord_reset" class="ets-reset button-secondary"><?php esc_html_e('Reset Settings', 'admin-coalition'); ?></button>
    </p>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#add-second-server').on('click', function() {
        $('#second-server-settings').show();
        $(this).hide();
    });
});
</script>
