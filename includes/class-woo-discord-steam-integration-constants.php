<?php
/**
 * Woo_Discord_Steam_Integration_Constants class.
 *
 * Contains constant values for the WooCommerce Discord and Steam Integration plugin.
 *
 * @package Woo_Discord_Steam_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo_Discord_Steam_Integration_Constants class.
 *
 * Provides various constant values for the plugin.
 *
 * @since 1.0.0
 */
class Woo_Discord_Steam_Integration_Constants {

	/**
	 * Constructor.
	 *
	 * Since this class only contains constants, the constructor is private to prevent instantiation.
	 */
	private function __construct() {
		// Prevent instantiation.
	}

	/**
	 * Discord API call scopes.
	 */
	const DISCORD_OAUTH_SCOPES = 'identify email guilds guilds.join';

	/**
	 * Discord API url.
	 */
	const DISCORD_API_URL = 'https://discord.com/api/v10/';

	/**
	 * Discord BOT Permissions.
	 */
	const DISCORD_BOT_PERMISSIONS = 8;

	/**
	 * Define group name for action scheduler actions.
	 */
	const ACTION_SCHEDULER_GROUP_NAME = 'admin-coalition';

	/**
	 * Following response codes not considered for re-try API calls.
	 */
	const DONOT_RETRY_API_CODES = array( 0, 10003, 50033, 10004, 50025, 10013, 10011 );

	/**
	 * Retry HTTP codes.
	 */
	const DONOT_RETRY_HTTP_CODES = array( 400, 401, 403, 404, 405, 502 );

	/**
	 * Add more constants as needed.
	 */
}
