<?php
/**
 * Woo_Discord_Steam_Integration_Admin class.
 *
 * Adds a sub-menu under WooCommerce.
 *
 * @package Woo_Discord_Steam_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo_Discord_Steam_Integration_Admin class.
 */
class Woo_Discord_Steam_Integration_Admin {

	/**
	 * Initialize the class and add hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_post_save_discord_steam_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_init', array( $this, 'discord_connect_bot' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_discord_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_discord_product_data_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_discord_product_data' ), 10, 2 );
		add_filter( 'manage_users_columns', array( $this, 'add_custom_user_columns' ) );
		add_action( 'manage_users_custom_column', array( $this, 'populate_custom_user_columns' ), 10, 3 );
		add_action( 'pre_user_query', array( $this, 'sort_custom_columns' ) );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * Registers the admin CSS styles for the plugin.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook ) {

		wp_register_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-admin', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/css/admin.css', array(), time() );
		wp_register_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-edit-product', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/css/edit-product.css', array(), time() );
	}


	/**
	 * Enqueue admin scripts.
	 *
	 * Registers the admin JavaScript files for the plugin and localizes script parameters.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		$min     = '.min';
		$version = Woo_Discord_Steam_Integration()->get_plugin_version();
		if ( WP_DEBUG == true ) {
			$version = time();
			$min     = '';
		}
		wp_register_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-admin', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/js/admin.js', array( 'jquery' ), $version );
		wp_register_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-discord-action', Woo_Discord_Steam_Integration()->plugin_url() . '/assets/admin/js/discord-action.js', array( 'jquery' ), $version );
		$script_params = array(
			'admin_ajax'                  => admin_url( 'admin-ajax.php' ),
			'permissions_const'           => Woo_Discord_Steam_Integration_Constants::DISCORD_BOT_PERMISSIONS,
			'is_admin'                    => is_admin(),
			'ets_woo_discord_steam_nonce' => wp_create_nonce( 'ets-woo-discord--steam-ajax-nonce' ),
		);
		wp_localize_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-admin', 'etsWooDiscordSteamParams', $script_params );
		wp_localize_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-discord-action', 'etsWooDiscordSteamParams', $script_params );
	}


	/**
	 * Add a Top level menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Admin Coalition',
			__( 'Admin Coalition', 'admin-coalition' ),
			'manage_options',
			'admin-coalition',
			array( $this, 'create_admin_page' ),
			Woo_Discord_Steam_Integration_Utils::get_top_level_menu_icon()
		);
	}

	/**
	 * Display the admin page.
	 */
	public function create_admin_page() {
		wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-admin' );
		wp_enqueue_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-admin' );
		wc_get_template( 'admin/application-settings.php', array(), '', Woo_Discord_Steam_Integration()->plugin_path() . '/templates/' );
	}

	/**
	 * Save the settings.
	 */
	public function save_settings() {

		// error_log( print_r( $_POST, true ) );

		if ( ! isset( $_POST['discord_steam_settings_nonce'] ) || ! wp_verify_nonce( $_POST['discord_steam_settings_nonce'], 'save_discord_steam_settings' ) ) {
			wp_die( __( 'Nonce verification failed', 'admin-coalition' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You are not allowed to perform this action', 'admin-coalition' ) );
		}

		if ( isset( $_POST['reset'] ) && $_POST['reset'] === 'ets_discord_reset' ) {
			delete_option( 'discord_server_id' );
			delete_option( 'discord_client_id' );
			delete_option( 'discord_client_secret' );
			// delete_option( 'discord_server_id_2' );
			// delete_option( 'discord_client_id_2' );
			// delete_option( 'discord_client_secret_2' );
			delete_option( 'discord_bot_token' );
			delete_option( 'discord_bot_redirect_url' );
			delete_option( 'discord_auth_redirect_url' );
			// delete_option( 'discord_purchase_channel' );
			delete_option( 'steam_web_api_key' );
			delete_option( 'discord_all_roles' );
			delete_option( 'discord_roles_color' );

			wp_redirect( admin_url( 'admin.php?page=admin-coalition&settings-reset=true' ) );
			exit;
		}

		if ( isset( $_POST['discord_server_id'] ) ) {
			update_option( 'discord_server_id', sanitize_text_field( $_POST['discord_server_id'] ) );
		}
		if ( isset( $_POST['discord_client_id'] ) ) {
			update_option( 'discord_client_id', sanitize_text_field( $_POST['discord_client_id'] ) );
		}
		if ( isset( $_POST['discord_client_secret'] ) ) {
			update_option( 'discord_client_secret', sanitize_text_field( $_POST['discord_client_secret'] ) );
		}
		if ( isset( $_POST['discord_server_id_2'] ) ) {
			update_option( 'discord_server_id_2', sanitize_text_field( $_POST['discord_server_id_2'] ) );
		}
		// if ( isset( $_POST['discord_client_id_2'] ) ) {
		// update_option( 'discord_client_id_2', sanitize_text_field( $_POST['discord_client_id_2'] ) );
		// }
		// if ( isset( $_POST['discord_client_secret_2'] ) ) {
		// update_option( 'discord_client_secret_2', sanitize_text_field( $_POST['discord_client_secret_2'] ) );
		// }
		if ( isset( $_POST['discord_bot_token'] ) ) {
			update_option( 'discord_bot_token', sanitize_text_field( $_POST['discord_bot_token'] ) );
		}
		if ( isset( $_POST['discord_bot_redirect_url'] ) ) {
			update_option( 'discord_bot_redirect_url', sanitize_text_field( $_POST['discord_bot_redirect_url'] ) );
		}
		if ( isset( $_POST['discord_auth_redirect_url'] ) ) {
			update_option( 'discord_auth_redirect_url', sanitize_text_field( $_POST['discord_auth_redirect_url'] ) );
		}
		// if ( isset( $_POST['discord_purchase_channel'] ) ) {
		// update_option( 'discord_purchase_channel', sanitize_text_field( $_POST['discord_purchase_channel'] ) );
		// }
		if ( isset( $_POST['discord_saved_server'] ) ) {
			update_option( 'discord_saved_server', sanitize_text_field( $_POST['discord_saved_server'] ) );
		}
		if ( isset( $_POST['steam_web_api_key'] ) ) {
			update_option( 'steam_web_api_key', sanitize_text_field( $_POST['steam_web_api_key'] ) );
		}

		wp_redirect( admin_url( 'admin.php?page=admin-coalition&settings-updated=true' ) );
		exit;
	}

	/**
	 * Method to catch the admin BOT connect action.
	 *
	 * @param NONE
	 * @return NONE
	 */
	public function discord_connect_bot() {

		if ( isset( $_GET['action'] ) && 'woo-discord-steam-connect-to-bot' === $_GET['action'] ) {
			if ( ! current_user_can( 'administrator' ) ) {
				wp_send_json_error( 'You do not have sufficient rights', 403 );
				exit();
			}
			$server_number     = intval( $_GET['server_number'] );
			$server_suffix     = ( isset( $server_number ) && $server_number == 2 ) ? '_2' : '';
			$discord_server_id = sanitize_text_field( trim( get_option( 'discord_server_id' . $server_suffix ) ) );

			$params                    = array(
				'client_id'            => sanitize_text_field( trim( get_option( 'discord_client_id' ) ) ),
				'permissions'          => Woo_Discord_Steam_Integration_Constants::DISCORD_BOT_PERMISSIONS,
				'response_type'        => 'code',
				'scope'                => 'bot',
				'guild_id'             => $discord_server_id,
				'disable_guild_select' => 'true',
				'redirect_uri'         => sanitize_text_field( trim( get_option( 'discord_bot_redirect_url' ) ) ),
			);
			$discord_authorise_api_url = Woo_Discord_Steam_Integration_Constants::DISCORD_API_URL . 'oauth2/authorize?' . http_build_query( $params );

			wp_redirect( $discord_authorise_api_url, 302, get_site_url() );
			exit;
		}
	}

	/**
	 * Add a new "Discord" tab in the product data metabox.
	 *
	 * @return array
	 */
	public function add_discord_product_data_tab( $tabs ) {
		$tabs['discord'] = array(
			'label'    => __( 'Discord', 'admin-coalition' ),
			'target'   => 'discord_product_data',
			'class'    => array(),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Add fields to the new "Discord" tab.
	 */
	public function add_discord_product_data_fields() {
		global $post;
		wp_enqueue_style( Woo_Discord_Steam_Integration()->get_plugin_name() . '-edit-product' );
		wp_enqueue_script( Woo_Discord_Steam_Integration()->get_plugin_name() . '-discord-action' );
		wc_get_template( 'product/discord-product-data-fields.php', array( 'product_id' => $post->ID ), '', Woo_Discord_Steam_Integration()->plugin_path() . '/templates/' );
	}

	/**
	 * Save the custom fields data when the product is saved.
	 *
	 * @param int $post_id The ID of the post (product) being saved.
	 *
	 * @since 1.0.0
	 */
	public function save_discord_product_data( $post_id, $post ) {

		// error_log('Trigger data: ' . print_r($_POST['woo-discord-trigger'], true));
		// error_log( 'Action data' . print_r( $_POST['woo-discord-action'], true ) );
		// error_log( 'Server data' . print_r( $_POST['woo-discord-server'], true ) );
		// error_log( 'Role data' . print_r( $_POST['woo-discord-role'], true ) );

		if ( isset( $_POST['woo-discord-trigger'] ) && is_array( $_POST['woo-discord-trigger'] ) ) {

			$discord_rules = array();

			// error_log( print_r( $_POST['woo-discord-message'], true ) );

			foreach ( $_POST['woo-discord-trigger'] as $index => $trigger ) {

				$action           = isset( $_POST['woo-discord-action'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-action'][ $index ] ) : '';
				$server           = isset( $_POST['woo-discord-server'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-server'][ $index ] ) : '';
				$role_server_1    = isset( $_POST['woo-discord-server-1-role'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-server-1-role'][ $index ] ) : '';
				$role_server_2    = isset( $_POST['woo-discord-server-2-role'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-server-2-role'][ $index ] ) : '';
				$channel_server_1 = isset( $_POST['woo-discord-server-1-channel'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-server-1-channel'][ $index ] ) : '';
				$channel_server_2 = isset( $_POST['woo-discord-server-2-channel'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-server-2-channel'][ $index ] ) : '';
				$message          = isset( $_POST['woo-discord-message'][ $index ] ) ? sanitize_text_field( $_POST['woo-discord-message'][ $index ] ) : '';

				$discord_rules[] = array(
					'trigger'   => sanitize_text_field( $trigger ),
					'action'    => $action,
					'server'    => $server,
					'role_1'    => $role_server_1,
					'role_2'    => $role_server_2,
					'channel_1' => $channel_server_1,
					'channel_2' => $channel_server_2,
					'message'   => $message,
				);

				error_log( print_r( $discord_rules, true ) );
			}

			update_post_meta( $post_id, '_discord_action_rules', serialize( $discord_rules ) );
		} else {

			delete_post_meta( $post_id, '_discord_action_rules' );
		}
	}



	/**
	 * Adds custom columns to the user list table.
	 *
	 * @param array $columns The existing columns in the user list table.
	 * @return array The modified columns with the new custom columns added.
	 */
	public function add_custom_user_columns( $columns ) {
		$columns['discord_id'] = __( 'Discord ID', 'admin-coalition' );
		$columns['steam_id']   = __( 'Steam ID', 'admin-coalition' );
		return $columns;
	}

	/**
	 * Populates the custom columns in the user list table.
	 *
	 * @param mixed  $value       The value of the custom column.
	 * @param string $column_name The name of the custom column.
	 * @param int    $user_id     The ID of the user.
	 * @return string The value to be displayed in the custom column.
	 */
	public function populate_custom_user_columns( $value, $column_name, $user_id ) {
		if ( 'discord_id' == $column_name ) {
			$discord_id       = Woo_Discord_Steam_Integration_Utils::get_discord_user_id( $user_id );
			$discord_username = Woo_Discord_Steam_Integration_Utils::get_discord_user_name( $user_id );
			$output           = $discord_id ? esc_html( $discord_id ) : __( 'Not Connected', 'admin-coalition' );
			$output          .= $discord_username ? '<br>' . esc_html( $discord_username ) : '';
			return $output;
		}

		if ( 'steam_id' == $column_name ) {
			$steam_id       = Woo_Discord_Steam_Integration_Utils::get_steam_user_id( $user_id );
			$steam_username = Woo_Discord_Steam_Integration_Utils::get_steam_username( $user_id );
			$steam_avatar   = Woo_Discord_Steam_Integration_Utils::get_steam_avatar( $user_id );

			if ( $steam_id ) {
				$output  = esc_html( $steam_id );
				$output .= $steam_username ? '<br>' . esc_html( $steam_username ) : '';
				if ( $steam_avatar ) {
					$output .= '<br><img src="' . esc_url( $steam_avatar ) . '" alt="' . esc_attr( $steam_username ) . '" style="width:50px;height:50px;border-radius:50%;">';
				}
				return $output;
			} else {
				return __( 'Not Connected', 'admin-coalition' );
			}
		}

		return $value;
	}



	/**
	 * Modifies the query to allow sorting by custom columns.
	 *
	 * @param WP_User_Query $query The WP_User_Query instance (passed by reference).
	 */
	public function sort_custom_columns( $query ) {
		global $wpdb;
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->query_vars['orderby'];
		if ( 'discord_id' == $orderby ) {
			$query->query_orderby = 'ORDER BY ' . $wpdb->prefix . 'usermeta.meta_value ' . ( $query->query_vars['order'] == 'ASC' ? 'asc' : 'desc' );
		}
		if ( 'steam_id' == $orderby ) {
			$query->query_orderby = 'ORDER BY ' . $wpdb->prefix . 'usermeta.meta_value ' . ( $query->query_vars['order'] == 'ASC' ? 'asc' : 'desc' );
		}
	}
}
