<?php
/**
 * Woo_Discord_Steam_Integration_Discord_Handler class.
 *
 * Handles various Discord functionalities like adding roles, sending direct messages, etc.
 *
 * @package Woo_Discord_Steam_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo_Discord_Steam_Integration_Discord_Handler class.
 */
class Woo_Discord_Steam_Integration_Discord_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init_discord_auth' ) );
		add_action( 'init', array( $this, 'handle_discord_auth_response' ) );
		// add_action( 'ets_discord_send_dm_after_payment_complete', array( $this, 'send_message_to_channel_after_payment_complete' ), 10, 2 );
	}

	/**
	 * Initializes the Discord authentication process.
	 *
	 * This method checks if the 'discord-auth' action is triggered and sets up the
	 * necessary parameters to initiate the Discord OAuth2 flow. If a 'redirect_url'
	 * is provided via the URL or shortcode, it uses that; otherwise, it defaults to
	 * a predefined redirect URL.
	 *
	 * If the 'via=discord' parameter is missing from the redirect URL, it appends it.
	 * Then, the method builds the required parameters (client ID, redirect URI, response
	 * type, and scope) for the Discord authorization URL and performs a 302 redirect
	 * to start the OAuth2 authentication flow.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_discord_auth() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'discord-auth' ) {
			$is_shortcode = isset( $_GET['redirect_url'] );
			$redirect_url = $is_shortcode ? esc_url_raw( $_GET['redirect_url'] ) : get_option( 'discord_auth_redirect_url' );
			// error_log( 'Init Discord Auth 1 :' . print_r( $redirect_url, true ) );

			if ( $is_shortcode || strpos( $redirect_url, 'via=discord' ) === false ) {
				$params       = array(
					'via'          => 'discord',
					'redirect_url' => $redirect_url,
				);
				$redirect_url = add_query_arg( $params, $redirect_url );
				// error_log( 'Init Discord Auth 2 :' . print_r( $redirect_url, true ) );
			}

			// error_log( 'Init Discord Auth 3 :' . print_r( $redirect_url, true ) );
			$params = array(
				'client_id'     => sanitize_text_field( trim( get_option( 'discord_client_id' ) ) ),
				'redirect_uri'  => $redirect_url,
				'response_type' => 'code',
				'scope'         => 'identify email connections guilds guilds.join',
			);

			$discord_authorise_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'oauth2/authorize?' . http_build_query( $params );

			wp_redirect( $discord_authorise_api_url, 302, get_site_url() );
			exit;
		}
	}


	public function handle_discord_auth_response() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			if ( isset( $_GET['code'] ) && isset( $_GET['via'] ) && $_GET['via'] == 'discord' ) {
				$code         = sanitize_text_field( trim( $_GET['code'] ) );
				$redirect_uri = isset( $_GET['redirect_url'] ) ? esc_url_raw( $_GET['redirect_url'] ) : null;

				$response = $this->create_discord_auth_token( $code, $user_id, $redirect_uri );
				// error_log( 'Create Discord auth token for logged in user : ' . print_r( $response, true ) );

				if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
					$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( is_array( $res_body ) && array_key_exists( 'access_token', $res_body ) ) {
						$access_token = sanitize_text_field( trim( $res_body['access_token'] ) );
						$user_body    = $this->get_discord_current_user( $access_token );

						$discord_user_email = ( ! empty( $user_body['email'] ) ) ? $user_body['email'] : $user_body['id'] . '@placeholder.email';

						if ( get_user_meta( $user_id, 'email_needs_update', true ) ) {
							$update_email = wp_update_user(
								array(
									'ID'         => $user_id,
									'user_email' => $discord_user_email,
								)
							);
							delete_user_meta( $user_id, 'email_needs_update' );
							if ( is_wp_error( $update_email ) ) {
								// error_log($update_email->get_error_message());
							} else {
								// error_log('Update Email successful');
							}
						}

						$this->catch_discord_auth_callback( $res_body, $user_id );
						$discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
						$this->add_discord_member_in_guild( $discord_user_id, $user_id, $access_token );
						wp_safe_redirect( urldecode_deep( $redirect_uri ? $redirect_uri : wc_get_checkout_url() ) );
						exit();
					}
				}
			}

			return;
		} else {
			// Guest user
			if ( isset( $_GET['code'] ) && isset( $_GET['via'] ) && $_GET['via'] == 'discord' ) {
				$user_id = ( is_user_logged_in() ) ? get_current_user_id() : 'guest';
				$code    = sanitize_text_field( trim( $_GET['code'] ) );

				$redirect_uri = isset( $_GET['redirect_url'] ) ? esc_url_raw( $_GET['redirect_url'] ) : null;

				// error_log( 'Auth Response :' . print_r( $redirect_uri, true ) );
				$response = $this->create_discord_auth_token( $code, $user_id, $redirect_uri );
				// //error_log( 'Response Auth : ' . print_r( $response, true ) );

				if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
					$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( is_array( $res_body ) && array_key_exists( 'access_token', $res_body ) ) {
						$access_token = sanitize_text_field( trim( $res_body['access_token'] ) );
						$user_body    = $this->get_discord_current_user( $access_token );
						// error_log( 'Guest Resposne : ' . print_r( $user_body, true ) );
						$discord_user_email = ( ! empty( $user_body['email'] ) ) ? $user_body['email'] : $user_body['id'] . '@placeholder.email';
						$password           = wp_generate_password( 12, true, false );

						$discord_exist_user_id = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
						$discord_user_id       = $user_body['id'];

						if ( email_exists( $discord_user_email ) || $discord_user_id == $discord_exist_user_id ) {
							$current_user = get_user_by( 'email', $discord_user_email );
							$user_id      = $current_user->ID;
						} else {
							$user_id  = wp_create_user( $discord_user_email, $password, $discord_user_email );
							$new_user = new WP_User( $user_id );
							$new_user->set_role( 'customer' );
							add_user_meta( $user_id, 'email_needs_update', true );
							wp_new_user_notification( $user_id, null, $password );
						}

						$this->catch_discord_auth_callback( $res_body, $user_id );
						// $credentials = array(
						// 'user_login'    => $discord_user_email,
						// 'user_password' => $password,
						// );
						wp_set_auth_cookie( $user_id, false, '', '' );

						// wp_signon($credentials, '');

						$discord_user_id = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
						$this->add_discord_member_in_guild( $discord_user_id, $user_id, $access_token );
						wp_safe_redirect( urldecode_deep( $redirect_uri ? $redirect_uri : wc_get_checkout_url() ) );
						exit();
					}
				}
			}

			return;
		}
	}


	/**
	 * Create authentication token for Discord API.
	 *
	 * @param string $code The authorization code returned by Discord.
	 * @param int    $user_id The WordPress user ID.
	 * @param string $redirect_uri The redirect URI to use after successful authentication.
	 * @return object|WP_Error The API response object or WP_Error on failure.
	 */
	public function create_discord_auth_token( $code, $user_id, $redirect_uri = null ) {
		$discord_token_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'oauth2/token';

		// Use the provided redirect URI or fall back to the default from the settings.
		if ( $redirect_uri ) {
			$params       = array(
				'via'          => 'discord',
				'redirect_url' => $redirect_uri,
			);
			$redirect_url = add_query_arg( $params, $redirect_uri );
		} else {
			$redirect_url = sanitize_text_field( trim( get_option( 'discord_auth_redirect_url' ) ) );
		}
		// $redirect_uri = $redirect_uri ? $redirect_uri : sanitize_text_field( trim( get_option( 'discord_auth_redirect_url' ) ) );
		if ( $redirect_uri ) {
			// error_log('Redirect url in Create auth token : ' . print_r( $redirect_url, true ) );
		}
		if ( ! is_user_logged_in() ) {
			if ( ! empty( $code ) && $user_id == 'guest' ) {
				$args     = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
					'body'    => array(
						'client_id'     => sanitize_text_field( trim( get_option( 'discord_client_id' ) ) ),
						'client_secret' => sanitize_text_field( trim( get_option( 'discord_client_secret' ) ) ),
						'grant_type'    => 'authorization_code',
						'code'          => $code,
						'redirect_uri'  => $redirect_url,
					),
				);
				$response = wp_remote_post( $discord_token_api_url, $args );

				return $response;
			} else {
				wp_send_json_error( 'Unauthorized user', 401 );
				exit();
			}
		}

		$response          = '';
		$refresh_token     = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_refresh_token', true ) ) );
		$pre_token         = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_access_token', true ) ) );
		$token_expiry_time = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_expires_in', true ) ) );

		if ( $refresh_token && $pre_token ) {
			$date              = new DateTime();
			$current_timestamp = $date->getTimestamp();
			if ( $current_timestamp > $token_expiry_time ) {
				$args     = array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
					'body'    => array(
						'client_id'     => sanitize_text_field( trim( get_option( 'discord_client_id' ) ) ),
						'client_secret' => sanitize_text_field( trim( get_option( 'discord_client_secret' ) ) ),
						'grant_type'    => 'refresh_token',
						'refresh_token' => $refresh_token,
						'redirect_uri'  => $redirect_url,
						'scope'         => Woo_Discord_Steam_Integration_Constants::DISCORD_BOT_PERMISSIONS,
					),
				);
				$response = wp_remote_post( $discord_token_api_url, $args );
				/**
				 * Error Logs
				 */
			}
		} else {
			$args     = array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'client_id'     => sanitize_text_field( trim( get_option( 'discord_client_id' ) ) ),
					'client_secret' => sanitize_text_field( trim( get_option( 'discord_client_secret' ) ) ),
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'redirect_uri'  => $redirect_url,
				),
			);
			$response = wp_remote_post( $discord_token_api_url, $args );
			/**
			 * Error logs
			 */
		}

		return $response;
	}


	/**
	 * Get Discord user details from API
	 *
	 * @param STRING $access_token
	 * @return OBJECT REST API response
	 */
	public function get_discord_current_user( $access_token ) {
		if ( $access_token ) {
			$discord_cuser_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'users/@me';
			$param                 = array(
				'headers' => array(
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Authorization' => 'Bearer ' . $access_token,
				),
			);
			$user_response         = wp_remote_get( $discord_cuser_api_url, $param );
			$response_arr          = json_decode( wp_remote_retrieve_body( $user_response ), true );
			$user_id               = get_current_user_id();
			if ( $user_id ) {

				// Handle eventual errors here
			}

			$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
			return $user_body;
		} else {
			return '';
		}
	}

	/*
	* Method to catch the discord auth response and process it.
	*
	* @param ARRAY $res_body
	*/
	private function catch_discord_auth_callback( $res_body, $user_id ) {
		$discord_exist_user_id = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
		$access_token          = sanitize_text_field( trim( $res_body['access_token'] ) );
		update_user_meta( $user_id, '_ets_discord_access_token', $access_token );
		if ( array_key_exists( 'refresh_token', $res_body ) ) {
			$refresh_token = sanitize_text_field( trim( $res_body['refresh_token'] ) );
			update_user_meta( $user_id, '_ets_discord_refresh_token', $refresh_token );
		}
		if ( array_key_exists( 'expires_in', $res_body ) ) {
			$expires_in = $res_body['expires_in'];
			$date       = new DateTime();
			$date->add( DateInterval::createFromDateString( $expires_in . ' seconds' ) );
			$token_expiry_time = $date->getTimestamp();
			update_user_meta( $user_id, '_ets_discord_expires_in', $token_expiry_time );
		}
		$user_body = $this->get_discord_current_user( $access_token );

		if ( is_array( $user_body ) && array_key_exists( 'discriminator', $user_body ) ) {
			$discord_user_number = $user_body['discriminator'];
			$discord_user_name   = $user_body['username'];
			// $discord_user_name_with_number = $discord_user_name . '#' . $discord_user_number;
			update_user_meta( $user_id, '_ets_discord_username', $discord_user_name );
		}
		if ( is_array( $user_body ) && array_key_exists( 'id', $user_body ) ) {
			$_ets_discord_user_id = sanitize_text_field( trim( $user_body['id'] ) );
			if ( $discord_exist_user_id == $_ets_discord_user_id ) {
				$_ets_discord_role_id = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_role_id', true ) ) );
				if ( ! empty( $_ets_discord_role_id ) && $_ets_discord_role_id != 'none' ) {
					/**
					 * Maybe Remove user role
					 */
				}
			}
			update_user_meta( $user_id, '_ets_discord_user_id', $_ets_discord_user_id );
			// Save the server
		}
	}

	/**
	 * Add new member into discord guild
	 *
	 * @param INT    $_ets_discord_user_id
	 * @param INT    $user_id
	 * @param STRING $access_token
	 * @return NONE
	 */
	private function add_discord_member_in_guild( $_ets_discord_user_id, $user_id, $access_token, $guild_id = '' ) {

		// maybe Action Scheduerlr will be used
		$this->ets_discord_as_handler_add_member_to_guild( $_ets_discord_user_id, $user_id, $access_token, $guild_id );
	}

	/**
	 * Method to add new members to Discord guild.
	 *
	 * @param INT    $_ets_discord_user_id
	 * @param INT    $user_id
	 * @param STRING $access_token
	 * @return NONE
	 */
	public function ets_discord_as_handler_add_member_to_guild( $_ets_discord_user_id, $user_id, $access_token, $guild_id ) {
		// Check to ensure the member still exists.
		if ( get_userdata( $user_id ) === false ) {
			error_log( __FUNCTION__ . ' User data does not exists  ' );
			return;
		}
		if ( ! $guild_id ) {
			$guild_id = sanitize_text_field( trim( get_option( 'discord_saved_server' ) ) );
		}

		$discord_bot_token = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );

		$guilds_memeber_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $_ets_discord_user_id;

		$guild_args     = array(
			'method'  => 'PUT',
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bot ' . $discord_bot_token,
			),
			'body'    => json_encode(
				array(
					'access_token' => $access_token,
					'roles'        => array(),
				)
			),
		);
		$guild_response = wp_remote_post( $guilds_memeber_api_url, $guild_args );

		if ( is_wp_error( $guild_response ) ) {
			error_log( 'Error adding member to server. Error Code: ' . $guild_response->get_error_code() . '. Error Message: ' . $guild_response->get_error_message() );
			return;
		}

		// Verify if the response is successful (2xx HTTP status)
		$response_code = wp_remote_retrieve_response_code( $guild_response );
		error_log( __FUNCTION__ . ' - HTTP response code: ' . $response_code );
		if ( $response_code >= 200 && $response_code < 300 ) {
			update_user_meta( $user_id, 'discord_server_id_added_' . $guild_id, $guild_id );
		} else {
			error_log( 'Failed to add user to server. HTTP Response Code: ' . $response_code );
		}
	}



	/**
	 * Send a message to the Discord channel after payment is complete and role is assigned.
	 *
	 * @deprecated since version 1.1.0, replaced by action rules.
	 * No longer needed as sending messages can now be handled through the new action rules,
	 * including the "send message" option. This eliminates the need to send messages via the
	 * completed payment action hook.
	 * @param int $user_id The user ID.
	 * @param int $product_id The product ID.
	 */
	public function send_message_to_channel_after_payment_complete( $user_id, $product_id ) {
		$user_info = get_userdata( $user_id );
		// $first_name    = $user_info->first_name;
		// $last_name     = $user_info->last_name;
		// $steam_id      = get_user_meta( $user_id, '_ets_steam_id', true );
		$steam_personaname = get_user_meta( $user_id, '_ets_steam_personaname', true );
		// $discord_id    = get_user_meta( $user_id, '_ets_discord_user_id', true );
		$discord_username = get_user_meta( $user_id, '_ets_discord_username', true );
		$product          = wc_get_product( $product_id );
		$product_title    = $product ? $product->get_name() : '';
		$message          = sprintf( 'A User with SteamID: %s and DiscordID: %s has just purchased %s', $steam_personaname, $discord_username, $product_title );
		$channel_id       = sanitize_text_field( trim( get_option( 'discord_purchase_channel' ) ) );

		if ( $channel_id ) {
			// //error_log( "Call Send mesage to channel ID : $channel_id " );
			$this->send_message_to_channel( $channel_id, $message );
		}
	}

	public function send_message_action( $user_id, $role_id, $server_id, $channel_id, $message, $order_id ) {

		$user_info    = get_userdata( $user_id );
		$steam_id     = get_user_meta( $user_id, '_ets_steam_id', true );
		$steam_name   = get_user_meta( $user_id, '_ets_steam_personaname', true );
		$discord_id   = get_user_meta( $user_id, '_ets_discord_user_id', true );
		$discord_name = get_user_meta( $user_id, '_ets_discord_username', true );
		$ip_address   = $_SERVER['REMOTE_ADDR'];
		$email        = $user_info->user_email;

		$order          = wc_get_order( $order_id );
		$product_name   = '';
		$product_expiry = '';
		$product_price  = '';

		// if ( $order ) {
		// foreach ( $order->get_items() as $item ) {
		// $product_name = $item->get_name();
		// $product_price = wc_price( $item->get_total() );
		// $subscription = wcs_get_subscriptions_for_order( $order_id );
		// if ( $subscription ) {
		// $product_expiry = date_i18n( get_option( 'date_format' ), $subscription->get_date( 'end' ) );
		// }
		// break;
		// }
		// }

		// Replace placeholders in the message body
		$message = str_replace(
			array(
				'{SteamID}',
				'{Game-Server}',
				'{SteamName}',
				'{Product}',
				'{Product Expiry}',
				'{Product Price}',
				'{IP}',
				'{email}',
				'{date}',
				'{DiscordID}',
				'{DiscordName}',
			),
			array(
				$steam_id,
				$this->get_selected_game_server(),
				$steam_name,
				$product_name,
				$product_expiry,
				$product_price,
				$ip_address,
				$email,
				date_i18n( get_option( 'date_format' ) ),
				$discord_id,
				$discord_name,
			),
			$message
		);

		if ( $channel_id ) {
			$this->send_message_to_channel( $channel_id, $message );
		}
	}

	/**
	 * Helper function to fetch the selected game server.
	 * stored in checkout data.
	 */
	private function get_selected_game_server() {
		// return WC()->session->get('wcsr_game_server_details') ? WC()->session->get('wcsr_game_server_details') : 'Unknown Server';
	}


	/**
	 * Send a message to a Discord channel.
	 *
	 * @param string $channel_id The channel ID.
	 * @param string $message The message to be sent.
	 * @return bool True if successful, false otherwise.
	 */
	public function send_message_to_channel( $channel_id, $message ) {
		error_log( "Trying ... Send mesage to channel ID : $channel_id " );
		$discord_bot_token = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );

		$discord_send_message_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'channels/' . $channel_id . '/messages';
		$message_args                 = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bot ' . $discord_bot_token,
			),
			'body'    => json_encode( array( 'content' => $message ) ),
		);

		$response = wp_remote_post( $discord_send_message_api_url, $message_args );

		if ( is_wp_error( $response ) ) {
			// error_log( 'Error sending message to channel: ' . $response->get_error_message() );
			return false;
		}

		return true;
	}




	/**
	 * Add role to a Discord user.
	 *
	 * @param string $user_id The user ID.
	 * @param string $role_id The role ID to be added.
	 * @param int    $server_id The guild ID.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function add_role_to_user( $user_id, $role_id, $server_id ) {
		if ( $server_id ) {
			$guild_id = $server_id;
		} else {
			$guild_id = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
		}

		$access_token                = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_access_token', true ) ) );
		$_ets_discord_user_id        = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
		$discord_bot_token           = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$discord_change_role_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $_ets_discord_user_id . '/roles/' . $role_id;

		error_log( "Call add role for user id : $user_id - disord role :  $role_id " );

		// check user if exist on server
		$server_id_added = get_user_meta( $user_id, 'discord_server_id_added_' . $guild_id, true );
		error_log( 'Server_id_added value : ' . $server_id_added );
		if ( ! $server_id_added ) {
			error_log( 'User not exist in server :' . $guild_id . ' Discord user id : ' . $_ets_discord_user_id . 'Access Token :' . $access_token );
			$this->add_discord_member_in_guild( $_ets_discord_user_id, $user_id, $access_token, $guild_id );

		}

		if ( $_ets_discord_user_id ) {
			// error_log( "Execute add role for user id : $user_id - disord role :  $role_id " );
			$param = array(
				'method'  => 'PUT',
				'headers' => array(
					'Content-Type'   => 'application/json',
					'Authorization'  => 'Bot ' . $discord_bot_token,
					'Content-Length' => 0,
				),
			);

			$response = wp_remote_get( $discord_change_role_api_url, $param );
			error_log( print_r( $response, true ) );
			if ( ! is_wp_error( $response ) ) {
				error_log( print_r( 'Role Added ! ' ) );
			} else {
				error_log( print_r( $response, true ) );
			}

			/**
			 * Error logs */
		} else {
			error_log( print_r( 'Non discord id for adding role ', true ) );
		}
	}

	/**
	 * Remove role from a Discord user.
	 *
	 * @param string $user_id The user ID.
	 * @param string $role_id The role ID to be removed.
	 * @param int    $server_id The Server ID.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function remove_role_from_user( $user_id, $role_id, $server_id ) {
		if ( $server_id ) {
			$guild_id = $server_id;
		} else {
			$guild_id = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
		}

		error_log( "Call remove role for user id : $user_id - disord role :  $role_id " );

		$access_token                = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_access_token', true ) ) );
		$_ets_discord_user_id        = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
		$discord_bot_token           = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$discord_delete_role_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/members/' . $_ets_discord_user_id . '/roles/' . $role_id;
		error_log( print_r( 'discord_delete_role_api_url : ' . $discord_delete_role_api_url, true ) );
		if ( $_ets_discord_user_id ) {
			$param = array(
				'method'  => 'DELETE',
				'headers' => array(
					'Content-Type'   => 'application/json',
					'Authorization'  => 'Bot ' . $discord_bot_token,
					'Content-Length' => 0,
				),
			);

			$response = wp_remote_request( $discord_delete_role_api_url, $param );
			error_log( print_r( $response, true ) );
			if ( ! is_wp_error( $response ) ) {
				error_log( print_r( 'Role Removed ! ', true ) );
			} else {
				error_log( print_r( $response, true ) );
			}
		} else {
			error_log( print_r( 'Missing discord_user_id Non discord id for removing role ', true ) );
		}
	}

	/**
	 * Bans a user from the specified Discord server (guild).
	 *
	 * @param int $user_id   The ID of the user in WordPress.
	 * @param int $server_id The Discord server (guild) ID to ban the user from.
	 * @return void
	 */
	public function ban_user_from_server( $user_id, $server_id ) {
		if ( ! $server_id ) {
			error_log( 'Missing server ID!' );
			return;
		}

		$guild_id                 = $server_id;
		$_ets_discord_user_id     = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
		$discord_bot_token        = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$discord_ban_user_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/bans/' . $_ets_discord_user_id;

		if ( $_ets_discord_user_id ) {
			$param = array(
				'method'  => 'PUT',
				'headers' => array(
					'Authorization' => 'Bearer ' . $discord_bot_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode(
					array(
						'delete_message_days' => 7, // Deletes last 7 days of messages (optional)
					)
				),
			);

			$response = wp_remote_request( $discord_ban_user_api_url, $param );

			if ( ! is_wp_error( $response ) ) {
				error_log( 'User banned successfully!' );
			} else {
				error_log( 'Error banning user: ' . $response->get_error_message() );
			}
		} else {
			error_log( 'Cannot ban the user: Missing Discord user ID' );
		}
	}

	/**
	 * Unbans a user from the specified Discord server (guild).
	 *
	 * @param int $user_id   The ID of the user in WordPress.
	 * @param int $server_id The Discord server (guild) ID to unban the user from.
	 * @return void
	 */
	public function unban_user_from_server( $user_id, $server_id ) {
		if ( ! $server_id ) {
			error_log( 'Missing server ID!' );
			return;
		}

		$guild_id                   = $server_id;
		$_ets_discord_user_id       = sanitize_text_field( trim( get_user_meta( $user_id, '_ets_discord_user_id', true ) ) );
		$discord_bot_token          = sanitize_text_field( trim( get_option( 'discord_bot_token' ) ) );
		$discord_unban_user_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'guilds/' . $guild_id . '/bans/' . $_ets_discord_user_id;

		// Check if the user has a Discord user ID
		if ( $_ets_discord_user_id ) {
			$param = array(
				'method'  => 'DELETE',
				'headers' => array(
					'Authorization' => 'Bearer ' . $discord_bot_token,
					'Content-Type'  => 'application/json',
				),
			);

			$response = wp_remote_request( $discord_unban_user_api_url, $param );

			if ( ! is_wp_error( $response ) ) {
				error_log( 'User unbanned successfully!' );
			} else {
				error_log( 'Error unbanning user: ' . $response->get_error_message() );
			}
		} else {
			error_log( 'Cannot unban the user: Missing Discord user ID' );
		}
	}


	/**
	 * Send a direct message to a Discord user.
	 *
	 * @param string $discord_user_id The Discord user ID.
	 * @param string $message The message to be sent.
	 * @return bool True if successful, false otherwise.
	 */
	public function send_direct_message( $discord_user_id, $message ) {
	}



	/**
	 * Add user to a Discord channel.
	 *
	 * @param string $discord_user_id The Discord user ID.
	 * @param string $channel_id The channel ID.
	 * @return bool True if successful, false otherwise.
	 */
	public function add_user_to_channel( $discord_user_id, $channel_id ) {
	}
}
