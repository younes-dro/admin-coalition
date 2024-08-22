<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Retrieve options
$discord_server_id         = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
$discord_client_id         = sanitize_text_field( trim( get_option( 'discord_client_id' ) ) );
$discord_client_secret     = sanitize_text_field( trim( get_option( 'discord_client_secret' ) ) );
$discord_bot_token         = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
$discord_bot_redirect_url  = Woo_Discord_Steam_Integration_Utils::get_bot_redirect_url();
$discord_auth_redirect_url = wc_get_checkout_url() . '?via=discord';
// $steam_client_id           = sanitize_text_field( trim( get_option( 'steam_client_id' ) ) );
// $steam_client_secret       = sanitize_text_field( trim( get_option( 'steam_client_secret' ) ) );
// $steam_redirect_uri        = sanitize_text_field( trim( get_option( 'steam_redirect_uri' ) ) );
$steam_web_api_key        = sanitize_text_field( trim( get_option( 'steam_web_api_key' ) ) );

$discord_purchase_channel  = sanitize_text_field( trim( get_option( 'discord_purchase_channel' ) ) );

$discord_channels = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels();
?>

<div class="wrap">
	<img class="ets-discord-steam-integration-image" src="<?php echo Woo_Discord_Steam_Integration_Utils::get_settings_page_icon(); ?>" />
	<h1 class="ets-discord-steam-integration-title"><?php esc_html_e( 'Admin Coalition Login', 'admin-coalition' ); ?></h1>

	<div class="woo-discord-steam-info">
		<p class="description">
			<?php esc_html_e( 'This plugin provides the following custom shortcodes that you can use:', 'admin-coalition' ); ?>
			<br>
			<strong>[login_with_discord]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Login with Discord" button. This button allows users to log in to WordPress using their Discord credentials if their Discord account is connected to their WordPress account.', 'admin-coalition' ); ?>
			<br>
			<strong>[login_with_steam]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Login with Steam" button. This button allows users to log in to WordPress using their Steam credentials if their Steam account is connected to their WordPress account.', 'admin-coalition' ); ?>
			<br>
			
		</p>
		<hr>
		<p class="description">
			<?php esc_html_e( 'Additionally, the plugin provides the following shortcodes for use on the checkout page:', 'admin-coalition' ); ?>
			<br>
			<strong>[ets_discord]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Connect Discord" button or show the connected Discord username if already connected.', 'admin-coalition' ); ?>
			<br>
			<strong>[ets_steam]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Connect Steam" button or show the connected Steam ID if already connected.', 'admin-coalition' ); ?>
			<br>
			
		</p>
		</div>

	<?php if ( isset( $_GET['settings-reset'] ) && $_GET['settings-reset'] === 'true' ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Settings have been reset.', 'admin-coalition' ); ?></p>
		</div>
	<?php elseif ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings have been saved.', 'admin-coalition' ); ?></p>
		</div>
	<?php endif; ?>
	
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="save_discord_steam_settings">
		<?php wp_nonce_field( 'save_discord_steam_settings', 'discord_steam_settings_nonce' ); ?>
		
		<h2><?php esc_html_e( 'Discord Details', 'admin-coalition' ); ?></h2>
		<table class="form-table">
			<tr></tr>
			<tr>
				<th scope="row">
					<label for="discord_server_id"><?php esc_html_e( 'Discord Server ID', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="text" id="discord_server_id" name="discord_server_id" value="<?php echo esc_attr( $discord_server_id ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="discord_client_id"><?php esc_html_e( 'Discord Client ID', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="text" id="discord_client_id" name="discord_client_id" value="<?php echo esc_attr( $discord_client_id ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="discord_client_secret"><?php esc_html_e( 'Discord Client Secret', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="text" id="discord_client_secret" name="discord_client_secret" value="<?php echo esc_attr( $discord_client_secret ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="discord_bot_token"><?php esc_html_e( 'Discord Bot Token', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="password" id="discord_bot_token" name="discord_bot_token" value="<?php echo esc_attr( $discord_bot_token ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="discord_bot_redirect_url"><?php esc_html_e( 'Discord Bot URL', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="text" id="discord_bot_redirect_url" name="discord_bot_redirect_url" value="<?php echo esc_attr( $discord_bot_redirect_url ); ?>" class="regular-text ets-disabled-input">
					<button type="button" class="copy-btn" data-target="discord_bot_redirect_url" title="<?php esc_attr_e('Copy to clipboard', 'admin-coalition'); ?>">
						📋
					</button>
				</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="discord_auth_redirect_url"><?php esc_html_e( 'Discord Auth URL', 'admin-coalition' ); ?></label>
			</th>
			<td>
				<input type="text" id="discord_auth_redirect_url" name="discord_auth_redirect_url" value="<?php echo esc_attr( $discord_auth_redirect_url ); ?>" class="regular-text ets-disabled-input">
				<button type="button" class="copy-btn" data-target="discord_auth_redirect_url" title="<?php esc_attr_e('Copy to clipboard', 'admin-coalition'); ?>">
					📋
				</button>
			</td>
		</tr>
			<tr>
				<th scope="row">
					<label for="discord_purchase_channel"><?php esc_html_e( 'Discord Purchase Channel', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<select id="discord_purchase_channel" name="discord_purchase_channel" class="regular-text">
						<?php foreach ( $discord_channels as $channel_id => $channel_name ) : ?>
							<option value="<?php echo esc_attr( $channel_id ); ?>" <?php selected( $discord_purchase_channel, $channel_id ); ?>>
								<?php echo esc_html( $channel_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit"><?php echo Woo_Discord_Steam_Integration_Utils::check_bot_status_connection(); ?></p>
		<hr>
		<h2><?php esc_html_e( 'Steam Details', 'admin-coalition' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="steam_web_api_key"><?php esc_html_e( 'Steam Web API Key', 'admin-coalition' ); ?></label>
				</th>
				<td>
					<input type="password" id="steam_web_api_key" name="steam_web_api_key" value="<?php echo esc_attr( $steam_web_api_key ); ?>" class="regular-text">
				</td>
			</tr>			
		</table>

		<div class="bot_status_connection">
	<p class="submit">
		<button type="submit" name="submit" value="ets_discord_submit" class="ets-submit button-primary woocommerce-save-button">
			<?php esc_html_e( 'Save Settings', 'admin-coalition' ); ?>
		</button>
		<button type="submit" name="reset" value="ets_discord_reset" class="ets-reset button-secondary">
			<?php esc_html_e( 'Reset Settings', 'admin-coalition' ); ?>
		</button>
	</p>
</div>

	</form>
</div>
