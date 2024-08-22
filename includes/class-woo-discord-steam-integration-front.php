<?php
/**
 * Woo_Discord_Steam_Integration_Front class.
 *
 * Handles the front-end integration for Discord and Steam authentication.
 *
 * @package Woo_Discord_Steam_Integration
 */



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo_Discord_Steam_Integration_Front class.
 */
class Woo_Discord_Steam_Integration_Front {


	/**
	 * Instance of the Discord handler.
	 *
	 * @var Woo_Discord_Steam_Integration_Discord_Handler
	 */
	private $discord_handler;

	/**
	 * Initialize the class and add hooks.
	 */
	public function __construct( Woo_Discord_Steam_Integration_Discord_Handler $discord_handler ) {
		$this->discord_handler = $discord_handler;
		add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		// add_filter( 'woocommerce_order_button_html', array( $this, 'custom_place_order_button_html' ) );
		// add_filter( 'woocommerce_before_checkout_form', array( $this, 'add_connect_discord_button_billing_form' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_successful_purchase' ) );
		add_action( 'init', array( $this, 'init_shortcodes') ) ;
		add_action( 'init', array ($this, 'handle_steam_openid_callback' ) );
		// add_action( 'woocommerce_payment_complete', array( $this, 'handle_successful_purchase' ) );
		// add_action( 'woocommerce_payment_complete_order_status_completed', array( $this, 'handle_successful_purchase' ) );
		add_action('woocommerce_checkout_order_processed', array( $this, 'log_purchase_message'), 10, 3);
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_connect_buttons' ) );
	}

	public function register_public_assets(){
		// //error_log( 'Register Front style');
		wp_register_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/public/css/public.css', array(), Woo_Discord_Steam_Integration()->get_plugin_version() );
		wp_register_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/public/js/public.js', array( ), Woo_Discord_Steam_Integration()->get_plugin_version(), true );
	}

	/**
	 * Initialize the shortcodes.
	 */
	public function init_shortcodes() {
		add_shortcode( 'login_with_discord', array( $this, 'shortcode_connect_discord' ) );
		add_shortcode( 'login_with_steam', array( $this, 'shortcode_connect_steam' ) );
		add_shortcode( 'ets_discord', array( $this, 'shortcode_ets_discord' ) );
		add_shortcode( 'ets_steam', array( $this, 'shortcode_ets_steam' ) );
	}

	/**
	 * Register the [login_with_discord] shortcode to display the Login with Discord button or the connected Discord username.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The HTML for the Connect Discord button or a disconnect button with the Discord username if already connected.
	 */
	public function shortcode_connect_discord( $atts ) {
		wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public');

		$atts = shortcode_atts( array(
			'redirect_url' => wc_get_checkout_url(), 
		), $atts );

		$redirect_url = esc_url( $atts['redirect_url'] );

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$discord_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_discord( $user_id );
			$discord_username = get_user_meta( $user_id, '_ets_discord_username', true );

			if ( ! $discord_connected ) {
				$auth_url = add_query_arg( 'redirect_url', urlencode( $redirect_url ), '?action=discord-auth' );
				return '<div class="ets_shortcode_wrapper"><a href="' . esc_url( $auth_url ) . '" class="button alt connect-button"><span class="logo discord-logo"></span>' . __( 'Login with Discord', 'admin-coalition' ) . '</a></div>';
			} else {
				$username_display = $discord_username ? '<div class="ets_shortcode_wrapper connected"><span class="logo discord-logo"></span>' . __( 'Connected to Discord as: ', 'admin-coalition' ) . esc_html( $discord_username ) . '</div>' : '';
				return $username_display;
			}
		} else {
			$auth_url = add_query_arg( 'redirect_url', urlencode( $redirect_url ), '?action=discord-auth' );
			return '<div class="ets_shortcode_wrapper"><a href="' . esc_url( $auth_url ) . '" class="button alt connect-button"><span class="logo discord-logo"></span>' . __( 'Login with Discord', 'admin-coalition' ) . '</a></div>';
		}
	}

	/**
	 * Register the [login_with_steam] shortcode to display the Login with Steam button or the connected Steam ID.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The HTML for the Connect Steam button or a disconnect button with the Steam ID if already connected.
	 */
	public function shortcode_connect_steam($atts) {
		wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public' );

		$atts = shortcode_atts(array(
			'redirect_url' => wc_get_checkout_url(), 
		), $atts);

		$redirect_url = esc_url( $atts['redirect_url'] );

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$steam_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_steam( $user_id );
			$steam_personaname = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );
			$steam_avatar = Woo_Discord_Steam_Integration_Utils::get_steam_avatar( $user_id );

			if (!$steam_connected) {
				$steam_login_url = Woo_Discord_Steam_Integration_Utils::get_steam_login_url($redirect_url);
				return '<div class="ets_shortcode_wrapper"><a href="' . esc_url( $steam_login_url ) . '" class="button alt connect-button"><span class="logo steam-logo"></span>' . __( 'Login with Steam', 'admin-coalition' ) . '</a></div>';
			} else {
				$steam_avatar_img = '<img src="' . esc_url( $steam_avatar ) . '" alt="' . esc_attr( $steam_personaname ) . '" style="width:50px;height:50px;border-radius:50%;">';
				$steam_id_display = $steam_personaname ? '<div class="ets_shortcode_wrapper connected"><span class="logo steam-logo"></span>' . __('Connected to Steam as: ', 'admin-coalition') . esc_html( $steam_personaname ) . $steam_avatar_img . '</div>' : '';
				return $steam_id_display;
			}
		} else {
			$steam_login_url = Woo_Discord_Steam_Integration_Utils::get_steam_login_url($redirect_url);
			return '<div class="ets_shortcode_wrapper"><a href="' . esc_url( $steam_login_url ) . '" class="button alt connect-button"><span class="logo steam-logo"></span>' . __( 'Login with Steam', 'admin-coalition' ) . '</a></div>';
		}
	}


/**
 * Register the [ets_discord] shortcode to display the Connect Discord button or the connected Discord username.
 *
 * @return string The HTML for the Connect Discord button or a disconnect button with the Discord username if already connected.
 */
public function shortcode_ets_discord() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $discord_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_discord( $user_id );
        $discord_username = Woo_Discord_Steam_Integration_Utils::get_discord_user_name( $user_id );

        if ( ! $discord_connected ) {
            return $this->get_connect_discord_button( false );
        } else {
            return $this->get_connect_discord_button(true, $discord_username);
        }
    } else {
        return $this->get_connect_discord_button(false);
    }
}

/**
 * Register the [ets_steam] shortcode to display the Connect Steam button or the connected Steam username.
 *
 * @return string The HTML for the Connect Steam button or a disconnect button with the Steam username if already connected.
 */
public function shortcode_ets_steam() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $steam_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_steam( $user_id );
        $steam_personaname = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );

        if ( ! $steam_connected ) {
            return $this->get_connect_steam_button( false );
        } else {
            return $this->get_connect_steam_button(true, $steam_personaname);
        }
    } else {
        return $this->get_connect_steam_button(false);
    }
}


	/**
	 * Enqueue public styles and scripts on the checkout page.
	 */
	public function enqueue_public_assets() {
		wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name());
		if ( is_checkout() ) {
			wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/public/css/public.css', array(), Woo_Discord_Steam_Integration()->get_plugin_version() );
			wp_enqueue_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-public', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/public/js/public.js', array( 'jquery' ), Woo_Discord_Steam_Integration()->get_plugin_version(), true );

			// Determine if the user is connected to Discord
			$is_user_connected_to_discord = is_user_logged_in() && ! empty( get_user_meta( get_current_user_id(), 'discord_id', true ) ) ? 1 : 0;

			// Pass data to JavaScript
			wp_localize_script(
				Woo_Discord_Steam_Integration()->get_plugin_name() . '-public',
				'discordData',
				array(
					'discordLoginUrl'     => esc_js( Woo_Discord_Steam_Integration_Utils::get_discord_login_url() ),
					'connectDiscordText'  => esc_js( __( 'Connect Discord', 'admin-coalition' ) ),
					'isUserConnected'     => $is_user_connected_to_discord,
					
				)
			);
		}
	}

	/**
	 * Handles the callback from Steam OpenID authentication.
	 *
	 * This method is triggered when Steam redirects the user back to your site after they have logged in via Steam.
	 * It validates the OpenID response, retrieves the Steam ID, and then processes the user data accordingly.
	 * If the Steam ID is successfully retrieved, it is saved to the user's meta data.
	 * The user is then redirected to the provided redirect URL or the WooCommerce checkout page.
	 *
	 * @return void
	 */
	function handle_steam_openid_callback() {
		//error_log( 'All GET param : ' . print_r( $_GET, true ) );
		
		if (isset($_GET['openid_mode']) && $_GET['openid_mode'] == 'id_res') {
			$steam_id = $this->validate_openid_response($_GET);
			// //error_log('Response Steam User data after Auth: ' . print_r($_GET, true));

			if ($steam_id) {
				$steam_user_data = Woo_Discord_Steam_Integration_Utils::get_steam_user_info($steam_id);
				
				if (!is_user_logged_in()) {
					// Check if a user with this Steam ID already exists
					$existing_user = get_users(array(
						'meta_key' => '_ets_steam_id',
						'meta_value' => $steam_id,
						'number' => 1,
						'fields' => 'ID'
					));
					
					if (!empty($existing_user)) {
						// Log in the existing user
						$user_id = $existing_user[0];
						wp_set_auth_cookie($user_id, false, '', '');
					} else {
						// No user found, create a new one
						$personaname = $steam_user_data['personaname'] ?? 'SteamUser';
						$username = sanitize_user($personaname, true);
						$password = wp_generate_password(12, true, true);
						$email = $steam_id . '@placeholder.email';
						$user_id = wp_create_user($username, $password, $email);

						if (is_wp_error($user_id)) {
							wp_die('Steam login failed: ' . $user_id->get_error_message());
						}
						$new_user = new WP_User($user_id);
						$new_user->set_role( 'customer');
						// Optionally, mark the email as needing an update
						add_user_meta($user_id, 'email_needs_update', true);

						wp_set_auth_cookie($user_id, false, '', '');
					}
				} else {
					$user_id = get_current_user_id();
				}

				update_user_meta($user_id, '_ets_steam_id', $steam_id);

				if (!empty($steam_user_data)) {
					update_user_meta($user_id, '_ets_steam_user_data', $steam_user_data);
					update_user_meta($user_id, '_ets_steam_personaname', $steam_user_data['personaname'] ?? '');
					update_user_meta($user_id, '_ets_steam_profileurl', $steam_user_data['profileurl'] ?? '');
					update_user_meta($user_id, '_ets_steam_avatar', $steam_user_data['avatar'] ?? '');
					update_user_meta($user_id, '_ets_steam_avatarmedium', $steam_user_data['avatarmedium'] ?? '');
					update_user_meta($user_id, '_ets_steam_avatarfull', $steam_user_data['avatarfull'] ?? '');
					update_user_meta($user_id, '_ets_steam_communityvisibilitystate', $steam_user_data['communityvisibilitystate'] ?? '');
					update_user_meta($user_id, '_ets_steam_primaryclanid', $steam_user_data['primaryclanid'] ?? '');
					update_user_meta($user_id, '_ets_steam_timecreated', $steam_user_data['timecreated'] ?? '');
					update_user_meta($user_id, '_ets_steam_personastate', $steam_user_data['personastate'] ?? '');
				}

				$redirect_url = isset($_GET['openid_return_to']) ? esc_url_raw($_GET['openid_return_to']) : wc_get_checkout_url();
				//error_log('Redirect url after login steam: ' . $redirect_url);

				wp_redirect($redirect_url);
				exit;
			} else {
				wp_die('Steam login failed. Please try again.');
			}
		}
	}



	/**
	 * Validates the OpenID response from Steam and extracts the Steam ID.
	 *
	 * This method processes the response returned by Steam during OpenID authentication.
	 * It extracts and returns the Steam ID if the response is valid.
	 *
	 * @param array $response The OpenID response array returned by Steam.
	 * @return string|false The extracted Steam ID if valid, or false if validation fails.
	 */
	private function validate_openid_response( $response ) {
		if ( isset( $response['openid_claimed_id'] ) ) {
			$steam_id = str_replace( 'https://steamcommunity.com/openid/id/', '', $response['openid_claimed_id'] );
			return $steam_id;
		}
		return false;
	}



	/**
	 * TO BE REMOVED 
	 * 
	 * 
	 * 
	 * Modify the HTML of the "Place Order" button on the checkout page.
	 *
	 * This method checks if the current user is logged in and connected to both Discord and Steam.
	 * If the user is logged in and connected to both, it returns the original "Place Order" button.
	 * If the user is logged in but not connected to both Discord and Steam, it replaces the "Place Order" button
	 * with appropriate messages and buttons to connect to Discord and Steam.
	 *
	 * @param string $button The original HTML of the "Place Order" button.
	 * @return string Modified HTML of the "Place Order" button or replacement content.
	 */
	public function custom_place_order_button_html( $button ) {
		if ( is_user_logged_in() ) {
			$user_id           = get_current_user_id();
			$discord_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_discord( $user_id );
			$steam_connected   = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_steam( $user_id );

			// If both accounts are connected, return the original button
			if ( $discord_connected && $steam_connected ) {
				return $button;
			}

			// If not both accounts are connected, show respective connect buttons and messages
			$messages = array();
			if ( ! $discord_connected ) {
				// $messages[] = '<p>' . __( 'Please connect your Discord account.', 'admin-coalition' ) . '</p>';
				$messages[] = $this->get_connect_discord_button();
			} else{
				$discord_username = Woo_Discord_Steam_Integration_Utils::get_discord_user_name( $user_id);
				// $messages[] = '<p>' . __( 'Discord: ', 'admin-coalition' ) . $discord_username .  '</p>';
			}

			if ( ! $steam_connected ) {
				// $messages[] = '<p>' . __( 'Please connect your Steam account.', 'admin-coalition' ) . '</p>';
				$messages[] = $this->get_connect_steam_button();
			} else{
				$steam_personaname = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );
				// $messages[] = '<p>' . __( 'Steam: ', 'admin-coalition' ) . $steam_personaname .  '</p>';
			}

			return implode( '', $messages );
		} else {
			// If user is not logged in, show a general message requiring both connections
			$messages   = array();
			// $messages[] = '<p>' . __( 'Please connect your Discord and Steam accounts.', 'admin-coalition' ) . '</p>';
			$messages[] = $this->get_connect_discord_button();
			$messages[] = $this->get_connect_steam_button();

			return implode( '', $messages );
		}
	}


	/**
	 * TO REMOVE
	 * _________________________________________________
	 * 
	 * 
	 * Add a "Connect Discord" button above the billing details on the checkout page.
	 *
	 * This method checks if the user is connected to Discord. If the user is not connected, 
	 * it displays a message and a button to connect to Discord.
	 *
	 * @param mixed $checkout 
	 * @return void Outputs the HTML content.
	 */
	public function add_connect_discord_button_billing_form( $checkout ) {
		if ( is_user_logged_in() ) {
			$user_id           = get_current_user_id();
			$discord_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_discord( $user_id );
			$steam_connected   = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_steam( $user_id );
	
			$messages = array();
	
			if ( $discord_connected && $steam_connected ) {
				$discord_username = Woo_Discord_Steam_Integration_Utils::get_discord_user_name( $user_id);
				$steam_personaname = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );
				// $messages[] = '<p>' . __( 'Discord: ', 'admin-coalition' ) . esc_html( $discord_username ) .  '</p>';
				// $messages[] = '<p>' . __( 'Steam: ', 'admin-coalition' ) . esc_html( $steam_personaname ) .  '</p>';
			}
	
			if ( ! $discord_connected ) {
				// $messages[] = '<p>' . __( 'Please connect your Discord account.', 'admin-coalition' ) . '</p>';
				$messages[] = $this->get_connect_discord_button( false );
			} else {
				$discord_username = Woo_Discord_Steam_Integration_Utils::get_discord_user_name( $user_id);
				$messages[] = $this->get_connect_discord_button(true, $discord_username);
			}
	
			if ( ! $steam_connected ) {
				// $messages[] = '<p>' . __( 'Please connect your Steam account.', 'admin-coalition' ) . '</p>';
				$messages[] = $this->get_connect_steam_button( false );
			} else {
				$steam_personaname = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );
				$messages[] = $this->get_connect_steam_button( true, $steam_personaname );
			}
	
			echo '<div class="connect-buttons-container">' . implode( '', $messages ) . '</div>';
		} else {
			$messages   = array();
			// $messages[] = '<p>' . __( 'Please connect your Discord and Steam accounts.', 'admin-coalition' ) . '</p>';
			$messages[] = $this->get_connect_discord_button(false);
			$messages[] = $this->get_connect_steam_button(false);
	
			echo '<div class="connect-buttons-container">' . implode( '', $messages ) . '</div>';
		}
	}

	public function get_connect_discord_button($connected = false, $username = '') {
		if ($connected) {
			return '<a href="#" class="connect-button connected"><span class="logo discord-logo"></span>Connected: ' . esc_html($username) . '</a>';
		} else {
			$discord_login_url = Woo_Discord_Steam_Integration_Utils::get_discord_login_url();
			return '<a href="' . esc_url( $discord_login_url ) . '" class="connect-button"><span class="logo discord-logo"></span>Login with Discord</a>';
		}
	}
	
	public function get_connect_steam_button($connected = false, $username = '') {
		if ($connected) {
			return '<a href="#" class="connect-button connected"><span class="logo steam-logo"></span>Connected: ' . esc_html($username) . '</a>';
		} else {
			$steam_login_url = Woo_Discord_Steam_Integration_Utils::get_steam_login_url();
			return '<a href="' . esc_url( $steam_login_url ) . '" class="connect-button"><span class="logo steam-logo"></span>Login with Steam</a>';
		}
	}
	
	
	



	/**
	 * Handle actions after a successful WooCommerce order completion.
	 *
	 * @param int $order_id The ID of the completed order.
	 */
	public function handle_successful_purchase( $order_id ) {
		// //error_log( print_r( get_class_methods( $this->discord_handler ), true ) );
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_customer_id();

		//error_log( "Order Completed ID: $order_id - User ID: $user_id" );

		if ( ! $user_id ) {
			//error_log( "User ID is missing for order ID: $order_id" );
			return;
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id      = $item->get_product_id();
			$discord_role_id = Woo_Discord_Steam_Integration_Utils::get_discord_role_id_by_product( $product_id );

			//error_log( "Product ID: $product_id - Discord Role ID: $discord_role_id" );

			if ( ! empty( $discord_role_id ) ) {
				$discord_user_id = Woo_Discord_Steam_Integration_Utils::get_discord_user_id( $user_id );

				if ( $discord_user_id ) {
					$this->discord_handler->add_role_to_user( $user_id, $discord_role_id, $product_id );
				} else {
					//error_log( "Discord user ID is missing for user ID: $user_id" );
				}
			}
		}
	}



	/**
	 * Log purchase message after order is placed.
	 *
	 * @param int $order_id The order ID.
	 * @param array $posted_data The array of posted data.
	 * @param WC_Order $order The order object.
	 */
	public function log_purchase_message($order_id, $posted_data, $order) {
		$user_id = $order->get_user_id();
		$product_ids = $order->get_items();

		foreach ($product_ids as $item_id => $item ) {
			$product_id = $item->get_product_id();
			do_action( 'ets_discord_send_dm_after_payment_complete', $user_id, $product_id );
		}
	}


	public function validate_connect_buttons() {
		$user_id = get_current_user_id();
		$discord_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_discord( $user_id );
		$steam_connected = Woo_Discord_Steam_Integration_Utils::is_user_connected_to_steam( $user_id );
	
		if ( ! $discord_connected ) {
			wc_add_notice( __( '<strong>Connecting Discord</strong> is required.', 'admin-coalition' ), 'error' );
		}
	
		if ( ! $steam_connected ) {
			wc_add_notice( __( '<strong>Connecting Steam</strong> is required.', 'admin-coalition' ), 'error' );
		}
	}
	



}
