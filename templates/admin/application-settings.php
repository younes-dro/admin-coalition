<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Retrieve options for the first and second servers
$discord_server_id         = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
$discord_client_id         = sanitize_text_field( trim( get_option( 'discord_client_id' ) ) );
$discord_client_secret     = sanitize_text_field( trim( get_option( 'discord_client_secret' ) ) );
$discord_bot_token         = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
$discord_bot_redirect_url  = Woo_Discord_Steam_Integration_Utils::get_bot_redirect_url();
$discord_auth_redirect_url = wc_get_checkout_url() . '?via=discord';
$discord_purchase_channel  = sanitize_text_field( trim( get_option( 'discord_purchase_channel' ) ) );
// $discord_channels          = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $discord_server_id );

// Retrieve options for the second server
$discord_server_id_2 = sanitize_text_field( trim( get_option( 'discord_server_id_2' ) ) );
// $discord_client_id_2         = sanitize_text_field(trim(get_option('discord_client_id_2')));
// $discord_client_secret_2     = sanitize_text_field(trim(get_option('discord_client_secret_2')));
// $discord_purchase_channel_2  = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $discord_server_id_2);

$first_server_status = Woo_Discord_Steam_Integration_Utils::get_single_server_status( $discord_server_id, $discord_bot_token, $discord_client_id, 'First Server' );

$discord_saved_server = sanitize_text_field( trim( get_option( 'discord_saved_server' ) ) );
$discord_servers      = Woo_Discord_Steam_Integration_Utils::get_saved_servers_menu();

$steam_web_api_key = sanitize_text_field( trim( get_option( 'steam_web_api_key' ) ) );

?>

<div class="wrap">
	<img class="ets-discord-steam-integration-image" src="<?php echo Woo_Discord_Steam_Integration_Utils::get_settings_page_icon(); ?>" />
	<h1 class="ets-discord-steam-integration-title"><?php esc_html_e( 'Admin Coalition Login', 'admin-coalition' ); ?></h1>
	<div class="woo-discord-steam-info">
		<p class="description">
		<?php esc_html_e( 'This plugin provides the following custom shortcodes that you can use:', 'admin-coalition' ); ?>
		<br>
		<strong>[login_with_discord]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Login with Discord" button. This button allows users to log in to WordPress using their Discord credentials.', 'admin-coalition' ); ?>
		<?php esc_html_e( 'You can also specify a redirect URL using the "redirect_url" parameter to control where users are redirected after a successful login.', 'admin-coalition' ); ?>
		<br>
		<?php esc_html_e( 'Ensure that the OAuth2 redirect URL in your Discord Developer Portal matches the following format:', 'admin-coalition' ); ?>
		<br>
		<strong><?php esc_html_e( 'Example URL Format:', 'admin-coalition' ); ?></strong> 
		<code><?php echo esc_html( 'https://yourwebsite.com/your-page/?via=discord&redirect_url=https://yourwebsite.com/target-page' ); ?></code>        
		<br><br>
		<strong>[login_with_steam]</strong> - <?php esc_html_e( 'Use this shortcode to display a "Login with Steam" button. This button allows users to log in to WordPress using their Steam credentials.', 'admin-coalition' ); ?>
		<?php esc_html_e( 'Similar to Discord, you can specify a redirect URL using the "redirect_url" parameter.', 'admin-coalition' ); ?>

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

	<!-- First Server Settings -->
	<h2><?php esc_html_e( 'Discord Details (First Server)', 'admin-coalition' ); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="discord_server_id"><?php esc_html_e( 'Discord Server ID', 'admin-coalition' ); ?></label></th>
			<td><input type="text" id="discord_server_id" name="discord_server_id" value="<?php echo esc_attr( $discord_server_id ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th scope="row"><label for="discord_client_id"><?php esc_html_e( 'Discord Client ID', 'admin-coalition' ); ?></label></th>
			<td><input type="text" id="discord_client_id" name="discord_client_id" value="<?php echo esc_attr( $discord_client_id ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th scope="row"><label for="discord_client_secret"><?php esc_html_e( 'Discord Client Secret', 'admin-coalition' ); ?></label></th>
			<td><input type="text" id="discord_client_secret" name="discord_client_secret" value="<?php echo esc_attr( $discord_client_secret ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th scope="row"><label for="discord_bot_token"><?php esc_html_e( 'Discord Bot Token', 'admin-coalition' ); ?></label></th>
			<td><input type="password" id="discord_bot_token" name="discord_bot_token" value="<?php echo esc_attr( $discord_bot_token ); ?>" class="regular-text"></td>
		</tr>

	</table>

	<!-- Display Bot Status for First Server -->
	<p><?php echo Woo_Discord_Steam_Integration_Utils::get_single_server_status( $discord_server_id, $discord_bot_token, $discord_client_id, 'First Server' ); ?></p>

	<!-- "Add More" Button to Add Second Server -->
	<?php if ( ( $first_server_status && strpos( $first_server_status, 'Bot connected' ) !== false ) ) : ?>
		<p>
			<button type="button" id="add-second-server" class="button-primary"><?php esc_html_e( 'Add Second Discord Server', 'admin-coalition' ); ?></button>
		</p>
	<?php endif; ?>

	<!-- Second Server Settings (Initially Hidden) -->
	<div id="second-server-settings" style="display:none;">
		<h2><?php esc_html_e( 'Discord Details (Second Server)', 'admin-coalition' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="discord_server_id_2"><?php esc_html_e( 'Second Discord Server ID', 'admin-coalition' ); ?></label></th>
				<td><input type="text" id="discord_server_id_2" name="discord_server_id_2" value="<?php echo esc_attr( $discord_server_id_2 ); ?>" class="regular-text"></td>
			</tr>
		</table>
			<!-- Display Bot Status for second Server -->
	<p><?php echo Woo_Discord_Steam_Integration_Utils::get_single_server_status( $discord_server_id_2, $discord_bot_token, $discord_client_id, 'Second Server' ); ?></p>
	</div>

	<table class="form-table">
	<tr>
			<th scope="row"><label for="discord_saved_server"><?php esc_html_e( 'Select discord server', 'admin-coalition' ); ?></label></th>
			<td>
				<select id="discord_saved_server" name="discord_saved_server" class="regular-text">
					<?php foreach ( $discord_servers as $server_id => $server_name ) : ?>
						<option value="<?php echo esc_attr( $server_id ); ?>" <?php selected( $discord_saved_server, $server_id ); ?>><?php echo esc_html( $server_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>

	<!-- Steam Settings -->
	<h2><?php esc_html_e( 'Steam Details', 'admin-coalition' ); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="steam_web_api_key"><?php esc_html_e( 'Steam Web API Key', 'admin-coalition' ); ?></label></th>
			<td><input type="password" id="steam_web_api_key" name="steam_web_api_key" value="<?php echo esc_attr( $steam_web_api_key ); ?>" class="regular-text"></td>
		</tr>            
	</table>

	<p class="submit">
		<button type="submit" name="submit" value="ets_discord_submit" class="ets-submit button-primary woocommerce-save-button"><?php esc_html_e( 'Save Settings', 'admin-coalition' ); ?></button>
		<button type="submit" name="reset" value="ets_discord_reset" class="ets-reset button-secondary"><?php esc_html_e( 'Reset Settings', 'admin-coalition' ); ?></button>
	</p>
	</form> 
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#add-second-server').on('click', function() {
		$('#second-server-settings').show();
		$(this).hide();
	});
});
</script>
