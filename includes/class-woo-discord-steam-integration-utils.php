<?php

/**
 * Woo_Discord_Steam_Integration_Utils class.
 *
 * Contains utility functions for the WooCommerce Discord and Steam Integration plugin.
 *
 * @package Woo_Discord_Steam_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo_Discord_Steam_Integration_Utils class.
 *
 * Provides various utility functions for the plugin.
 *
 * @since 1.0.0
 */
class Woo_Discord_Steam_Integration_Utils {

	/**
	 * Constructor.
	 *
	 * Since this class only contains static methods, the constructor is private to prevent instantiation.
	 */
	private function __construct() {
		// Prevent instantiation.
	}

	/**
	 * Get the current screen URL.
	 *
	 * @return string The current screen URL.
	 */
	public static function get_current_screen_url() {
		$parts       = wp_parse_url( home_url() );
		$current_uri = "{$parts['scheme']}://{$parts['host']}" . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . add_query_arg( null, null );

		return $current_uri;
	}

	/**
	 * Get the bot redirect URL without additional parameters.
	 *
	 * @return string The bot redirect URL without additional parameters.
	 */
	public static function get_bot_redirect_url() {
		$current_screen_url = self::get_current_screen_url();
		$url_parts          = wp_parse_url( $current_screen_url );

		$bot_redirect_url = "{$url_parts['scheme']}://{$url_parts['host']}" .
						( isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '' ) .
						"{$url_parts['path']}?page=admin-coalition";

		return $bot_redirect_url;
	}

	/**
	 * Public method to check the status of a single server
	 * This method is used to access the private check_single_server_status.
	 *
	 * @param string $guild_id
	 * @param string $discord_bot_token
	 * @param string $client_id
	 * @param string $server_label
	 * @return string
	 */
	public static function get_single_server_status( $guild_id, $discord_bot_token, $client_id, $server_label ) {
		// error_log("Checking server status with params: guild_id={$guild_id}, bot_token={$discord_bot_token}, client_id={$client_id}, server_label={$server_label}");
		return self::check_single_server_status( $guild_id, $discord_bot_token, $client_id, $server_label );
	}



	/**
	 * Check the bot status connection.
	 *
	 * @since 1.0.0
	 * @deprecated 1.1.0 This method is deprecated and replaced by a new approach to handle multiple servers.
	 * The current system handles both the first and second servers via separate options.
	 * Please use the server-specific connection checks to handle individual server connections.
	 *
	 * @return string HTML output indicating the status of the bot connection.
	 */
	public static function check_bot_status_connection() {
		$guild_id          = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
		$second_guild_id   = sanitize_text_field( trim( get_option( 'second_discord_server_id' ) ) );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$client_id         = sanitize_text_field( trim( get_option( 'discord_client_id' ) ) );
		$user_id           = get_current_user_id();

		$bot_status_output = '';

		// Check the first server status
		$bot_status_output .= self::check_single_server_status( $guild_id, $discord_bot_token, $client_id, 'First Server' );

		// Check the second server status if it exists
		if ( $second_guild_id ) {
			$bot_status_output .= self::check_single_server_status( $second_guild_id, $discord_bot_token, $client_id, 'Second Server' );
		}

		return $bot_status_output;
	}

	/**
	 * Checks the status of a bot connection to a specific Discord server and retrieves its roles.
	 *
	 * This function performs an API call to Discord to check whether the bot is connected to a specific server (guild)
	 * and retrieves the list of roles for that server. It also handles various error conditions, such as incorrect
	 * server ID or bot token, and provides feedback to the admin via a button to reconnect the bot if necessary.
	 *
	 * @param string $guild_id The Discord server ID (guild ID) to check the connection for.
	 * @param string $discord_bot_token The Discord bot token used for authorization.
	 * @param string $client_id The Discord client ID (application ID) of the bot.
	 * @param string $server_label The label to identify the server (e.g., "First Server" or "Second Server").
	 *
	 * @return string The HTML output for the connection status, including buttons for reconnecting the bot if needed.
	 */
	private static function check_single_server_status( $guild_id, $discord_bot_token, $client_id, $server_label ) {
		if ( $guild_id && $discord_bot_token ) {
			$discord_server_roles_api = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/roles';
			$guild_args               = array(
				'method'  => 'GET',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bot ' . $discord_bot_token,
				),
			);
			$guild_response           = wp_remote_post( $discord_server_roles_api, $guild_args );
			$response_arr             = json_decode( wp_remote_retrieve_body( $guild_response ), true );

			$bot_button = '';

			// Check for API errors and handle them accordingly
			if ( array_key_exists( 'code', $response_arr ) || array_key_exists( 'error', $response_arr ) ) {
				// Handle specific error codes
				if ( $response_arr['code'] === 10004 ) {
					$server_number = ($server_label === 'First Server') ? 1 : 2;
					// Server ID is wrong or the bot is not connected to the server
					$bot_button .= '<a href="?action=woo-discord-steam-connect-to-bot&server_number=' . $server_number . '" class="button-primary woo-discord-steam-error woo-discord-steam-connect-to-bot" id="woo-discord-steam-connect-discord-bot">' . esc_html__( 'Connect your Bot to ' . $server_label, 'admin-coalition' ) . self::get_discord_logo_white() . '</a>';
					$bot_button .= '<b>The server ID is wrong or you did not connect the Bot to ' . $server_label . '.</b>';
					return $bot_button;
				} elseif ( $response_arr['code'] === 0 && $response_arr['message'] == '401: Unauthorized' ) {
					// Bot token is unauthorized or incorrect
					$bot_button .= '<a href="#" class="button-primary woo-discord-steam-error">' . esc_html__( 'Error: Unauthorized - The Bot Token is wrong for ' . $server_label, 'admin-coalition' ) . '</a>';
					return $bot_button;
				} else {
					return 'Unknown Error!';
				}
			} else {
				// Bot is connected successfully, show a success message
				$bot_button .= '<div class="woo-discord-steam-bot-connected"><a href="#" class="button-primary woo-discord-steam-valid"><span>' . esc_html__( 'Bot connected to ' . $server_label, 'admin-coalition' ) . '</span>' . self::get_discord_logo_white() . '</a></div>';

				// Save the roles for this server
				$discord_roles = array();
				foreach ( $response_arr as $key => $value ) {
					$isbot = false;
					if ( is_array( $value ) ) {
						if ( array_key_exists( 'tags', $value ) ) {
							if ( array_key_exists( 'bot_id', $value['tags'] ) ) {
								$isbot = true;
								if ( $value['tags']['bot_id'] === $client_id ) {
									$response_arr['bot_connected'] = 'yes';
								}
							}
						}
					}
					if ( $key != 'previous_mapping' && $isbot == false && isset( $value['name'] ) && $value['name'] != '@everyone' ) {
						$discord_roles[ $value['id'] ] = $value['name'];
					}
				}

				// Save roles for each server separately
				update_option( 'discord_all_roles_' . $guild_id, serialize( $discord_roles ) );

				// error_log( print_r( $bot_button, true ) );
				return $bot_button;
			}
		}
	}



	/**
	 * Get the Discord logo in white.
	 *
	 * @since 1.0.0
	 *
	 * @return string SVG HTML for the Discord logo in white.
	 */
	private static function get_discord_logo_white() {
		return '<svg width="24" height="24" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M20.317 4.3696C18.7685 3.75291 17.11 3.2769 15.387 2.96495C15.0523 3.50487 14.6577 4.04992 14.2905 4.6296C12.4082 4.3307 10.5183 4.3307 8.64951 4.6296C8.2823 4.04992 7.8877 3.50487 7.55295 2.96495C5.82005 3.2769 4.16155 3.76221 2.61305 4.3696C0.2289 8.08939 -0.39495 11.7242 0.10605 15.3066C2.23655 16.7485 4.2768 17.8194 6.3138 18.4824C6.9183 17.6159 7.45845 16.6945 7.92255 15.7291C8.6553 15.9319 9.40545 16.0791 10.164 16.1696C10.3271 15.9051 10.4616 15.624 10.561 15.3291C8.802 15.0361 7.0965 14.5583 5.46855 13.9061C5.7975 13.6343 6.1158 13.348 6.41355 13.0481C9.39455 14.086 12.5745 14.086 15.5555 13.0481C15.8533 13.348 16.1716 13.6343 16.5005 13.9061C14.8726 14.5583 13.1671 15.0361 11.408 15.3291C11.5074 15.624 11.6419 15.9051 11.805 16.1696C12.5636 16.0791 13.3137 15.9319 14.0465 15.7291C14.5106 16.6945 15.0508 17.6159 15.6553 18.4824C17.6923 17.8194 19.7326 16.7485 21.8631 15.3066C22.3932 11.7194 21.7482 8.0748 20.317 4.3696ZM8.5518 13.1306C7.4988 13.1306 6.6153 12.1271 6.6153 10.8418C6.6153 9.55645 7.47675 8.5449 8.5518 8.5449C9.62685 8.5449 10.5143 9.55645 10.488 10.8418C10.488 12.1271 9.62685 13.1306 8.5518 13.1306ZM15.4482 13.1306C14.3952 13.1306 13.5117 12.1271 13.5117 10.8418C13.5117 9.55645 14.3731 8.5449 15.4482 8.5449C16.5232 8.5449 17.4107 9.55645 17.3844 10.8418C17.3844 12.1271 16.5232 13.1306 15.4482 13.1306Z"/></svg>';
	}

	/**
	 * Fetch Discord channels based on a dynamic server ID.
	 *
	 * This function retrieves the list of text channels from a specified Discord server (guild)
	 * using the provided server ID. The server must be authenticated via the bot token, and the
	 * function returns an associative array of channel IDs and names, sorted alphabetically.
	 *
	 * @param string $discord_server_id The Discord server (guild) ID from which to fetch channels.
	 *
	 * @return array An associative array of channels where the key is the channel ID and the value
	 *               is the channel name. Returns an empty array if the bot token or server ID
	 *               is invalid, or if an error occurs during the API request.
	 */
	public static function fetch_discord_channels( $discord_server_id ) {
		if ( empty( $discord_server_id ) ) {
			return array();
		}

		$discord_bot_token = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$discord_api_url   = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL;

		if ( empty( $discord_bot_token ) || empty( $discord_server_id ) ) {
			return array();
		}

		$response = wp_remote_get(
			$discord_api_url . "guilds/{$discord_server_id}/channels",
			array(
				'headers' => array(
					'Authorization' => 'Bot ' . $discord_bot_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$channels = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $channels ) ) {
			return array();
		}

		if ( key_exists( 'code', $channels ) ) {
			// error_log( print_r( $channels, true ) );
			return array();
		}

		$channel_options = array();
		foreach ( $channels as $channel ) {
			// Only return text channels (type 0)
			if ( $channel['type'] == 0 ) {
				$channel_options[ $channel['id'] ] = $channel['name'];
			}
		}

		asort( $channel_options );
		$channel_options = array( 0 => '---' ) + $channel_options;

		return $channel_options;
	}


	/**
	 * Generate Discord login URL.
	 *
	 * @return string
	 */
	public static function get_discord_login_url() {
		// $redirect_uri = wc_get_checkout_url();
		$redirect_uri = get_option( 'discord_auth_redirect_url' );
		$params       = array(
			'client_id'     => sanitize_text_field( get_option( 'discord_client_id' ) ),
			'redirect_uri'  => $redirect_uri,
			'response_type' => 'code',
			'scope'         => Woo_Discord_Steam_Integration_Constants::DISCORD_OAUTH_SCOPES,
			'state'         => 'discord_auth',
		);

		return Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'oauth2/authorize?' . http_build_query( $params );
	}

	/**
	 * Get the Steam OpenID login URL.
	 *
	 * @return string The URL to redirect to Steam's login page.
	 */
	public static function get_steam_login_url( $redirect_url = '' ) {
		$return_url = ! empty( $redirect_url ) ? $redirect_url : wc_get_checkout_url(); // Redirect URI after authentication
		// error_log( 'Redirect URL in Get Steam login url :' . $return_url );
		$steam_login_url = 'https://steamcommunity.com/openid/login?' . http_build_query(
			array(
				'openid.mode'       => 'checkid_setup',
				'openid.ns'         => 'http://specs.openid.net/auth/2.0',
				'openid.return_to'  => $return_url,
				'openid.realm'      => home_url(),
				'openid.ns.sreg'    => 'http://openid.net/extensions/sreg/1.1',
				'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
				'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
			)
		);

		return $steam_login_url;
	}




	/**
	 * Retrieve the Discord username associated with a user.
	 *
	 * This method fetches the Discord username stored in the user meta for a given WordPress user ID.
	 *
	 * @param int $user_id The ID of the WordPress user.
	 * @return string|null The Discord username if available, otherwise null.
	 */
	public static function get_discord_user_name( $user_id ) {
		$discord_username = get_user_meta( $user_id, '_ets_discord_username', true );
		return $discord_username ? $discord_username : null;
	}


	/**
	 * Retrieve the Discord ID associated with a user.
	 *
	 * This method fetches the Discord user ID stored in the user meta for a given WordPress user ID.
	 *
	 * @param int $user_id The ID of the WordPress user.
	 * @return string|null The Discord user ID if available, otherwise null.
	 */
	public static function get_discord_user_id( $user_id ) {
		$discord_id = get_user_meta( $user_id, '_ets_discord_user_id', true );
		return $discord_id ? $discord_id : null;
	}

	/**
	 * Retrieve the Steam ID associated with a user.
	 *
	 * This method fetches the Steam user ID stored in the user meta for a given WordPress user ID.
	 *
	 * @param int $user_id The ID of the WordPress user.
	 * @return string|null The Steam user ID if available, otherwise null.
	 */
	public static function get_steam_user_id( $user_id ) {
		$steam_id = get_user_meta( $user_id, '_ets_steam_id', true );
		return $steam_id ? $steam_id : null;
	}

	/**
	 * Retrieve the Steam username associated with a user.
	 *
	 * This method fetches the Steam username stored in the user meta for a given WordPress user ID.
	 *
	 * @param int $user_id The ID of the WordPress user.
	 * @return string|null The Steam username if available, otherwise null.
	 */
	public static function get_steam_username( $user_id ) {
		$steam_username = get_user_meta( $user_id, '_ets_steam_personaname', true );
		return $steam_username ? $steam_username : null;
	}

	/**
	 * Retrieve the Steam avatar associated with a user.
	 *
	 * This method fetches the Steam avatar URL stored in the user meta for a given WordPress user ID.
	 *
	 * @param int $user_id The ID of the WordPress user.
	 * @return string|null The Steam avatar URL if available, otherwise null.
	 */
	public static function get_steam_avatar( $user_id ) {
		$steam_avatar = get_user_meta( $user_id, '_ets_steam_avatar', true );
		return $steam_avatar ? $steam_avatar : null;
	}

	/**
	 * Fetch Steam user information using the Steam Web API.
	 *
	 * This method retrieves user information from Steam using the user's Steam ID
	 * and API key. The data is fetched via the Steam Web API and returned as an array.
	 * If the request fails, an error is logged and the method returns null.
	 *
	 * @param string $steam_id The Steam ID of the user.
	 * @return array|null The Steam user information if available, or null if the request fails.
	 */
	public static function get_steam_user_info( $steam_id ) {
		$api_key = sanitize_text_field( trim( get_option( 'steam_web_api_key' ) ) );
		$api_url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=' . $api_key . '&steamids=' . $steam_id;

		$response = wp_remote_get( $api_url );

		if ( is_wp_error( $response ) ) {
			// error_log( 'Error fetching Steam user info: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// //error_log( 'Response data Steam User: ' . print_r( $data, true ) );

		if ( isset( $data['response']['players'][0] ) ) {
			return $data['response']['players'][0];
		}

		// //error_log( 'No Steam user info found for Steam ID: ' . $steam_id );
		return null;
	}


	/**
	 * Check if the user is connected to Discord.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if the user is connected to Discord, false otherwise.
	 */
	public static function is_user_connected_to_discord( $user_id ) {
		$discord_id = get_user_meta( $user_id, '_ets_discord_user_id', true );
		return ! empty( $discord_id );
	}

	/**
	 * Check if the user is connected to Steam.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if the user is connected to Steam, false otherwise.
	 */
	public static function is_user_connected_to_steam( $user_id ) {
		$steam_id = get_user_meta( $user_id, '_ets_steam_id', true );
		return ! empty( $steam_id );
	}

	/**
	 * Retrieve the Discord role ID associated with a product.
	 *
	 * This method fetches the Discord role ID stored in the post meta for a given product ID.
	 *
	 * @param int $product_id The ID of the product.
	 * @return string|null The Discord role ID if available, otherwise null.
	 */
	public static function get_discord_role_id_by_product( $product_id ) {
		$discord_role_id = get_post_meta( $product_id, '_ets_discord_role_id', true );
		return $discord_role_id ? $discord_role_id : null;
	}

	/**
	 * Get the URL for the top-level menu icon.
	 *
	 * This function returns the URL of the icon used for the top-level menu
	 * in the WordPress admin dashboard.
	 *
	 * @return string The URL of the top-level menu icon.
	 */
	public static function get_top_level_menu_icon() {
		return Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/images/image
		.png';
	}

	/**
	 * Get the URL for the settings page icon.
	 *
	 * This function returns the URL of the icon used on the settings page
	 * of the Woo_Discord_Steam_Integration plugin.
	 *
	 * @return string The URL of the settings page icon.
	 */
	public static function get_settings_page_icon() {
		return Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/images/settings.png';
	}

	public static function get_saved_servers_menu(){
		$discord_server_id         = sanitize_text_field(trim(get_option('discord_server_id')));
		$discord_server_id_2         = sanitize_text_field(trim(get_option('discord_server_id_2')));

		$servers = array();
		$servers[$discord_server_id] = 'Server 1';
		if( !empty($discord_server_id_2)){
			$servers[$discord_server_id_2] = 'Server 2';
		}

		return $servers;

	}
}
