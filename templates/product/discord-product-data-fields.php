<?php

$discord_action_rules = get_post_meta( $product_id, '_discord_action_rules', true );
$discord_action_rules = ! empty( $discord_action_rules ) ? unserialize( $discord_action_rules ) : array();

// error_log( print_r( $discord_action_rules, true));

if ( empty( $discord_action_rules ) ) {
	$discord_action_rules[] = array(
		'trigger' => '',
		'action'  => '',
		'server'  => '',
		'role'    => '',
	);
}
$server_1 = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
$server_2 = sanitize_text_field( trim( get_option( 'discord_server_id_2' ) ) );

$server_1_roles = maybe_unserialize( get_option( 'discord_all_roles_' . $server_1 ) );
$server_2_roles = maybe_unserialize( get_option( 'discord_all_roles_' . $server_2 ) );

// error_log( print_r( $server_2_roles, true ) );

?>
<div id="discord_product_data" class="panel woocommerce_options_panel hidden">

	<div class="woo-discord-steam-actions-container">
		<div class="woo-discord-steam-header-container">
			<label>Discord Action</label>
			<div class="woo-discord-steam-header-buttons">
				<button type="button" id="add-discord-action">Add Discord Action</button>
			</div>
		</div>
		<div class="woo-discord-steam-action">
		<?php
		foreach ( $discord_action_rules as $index => $rule ) {
			?>
				<div class="woo-discord-steam-action-row-wrap">
					<div class="woo-discord-steam-action-row">
						<div class="dropdown-section">
							<select class="trigger-dropdown" name="woo-discord-trigger[]">
								<option value="purchased" <?php selected( $rule['trigger'], 'purchased' ); ?>>When the package is purchased</option>
								<option value="subscription_purchased" <?php selected( $rule['trigger'], 'subscription_purchased' ); ?>>When the subscription is purchased</option>
								<option value="refund" <?php selected( $rule['trigger'], 'refund' ); ?>>When the package is refunded</option>
								<option value="subscription_refund" <?php selected( $rule['trigger'], 'subscription_refund' ); ?>>When the subscription is refunded</option>
								<option value="chargebacked" <?php selected( $rule['trigger'], 'chargebacked' ); ?>>When the package is chargebacked</option>
								<option value="renew" <?php selected( $rule['trigger'], 'renew' ); ?>>When the subscription is renewed</option>
							</select>
						</div>

						<div class="dropdown-section">
							<select class="action-dropdown" name="woo-discord-action[]">
								<option value="assign_role" <?php selected( $rule['action'], 'assign_role' ); ?>>Assign role to customer on server</option>
								<option value="remove_role" <?php selected( $rule['action'], 'remove_role' ); ?>>Remove role from customer on server</option>
								<option value="send_message" <?php selected( $rule['action'], 'send_message' ); ?>>Send message on server</option>
							</select>
						</div>

						<div class="dropdown-section">
							<select class="server-dropdown" name="woo-discord-server[]">
								<option value="<?php echo esc_attr( $server_1 ); ?>" <?php selected( $rule['server'], $server_1 ); ?>>Server 1</option>
								<option value="<?php echo esc_attr( $server_2 ); ?>" <?php selected( $rule['server'], $server_2 ); ?>>Server 2</option>
							</select>
						</div>
					</div>

					<div class="role-section">

						<select class="role-dropdown" name="woo-discord-role[]">
						<?php foreach ( $server_1_roles as $role_id => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_id ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
						</select>
						<select class="role-dropdown" name="woo-discord-role[]">
						<?php foreach ( $server_2_roles as $role_id => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_id ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
						</select>                        

					</div>
					<div class="woo-discord-steaam-remove-wrap">
						<span class="woo-discord-steam-remove-action-row">X</span>

					</div>
				</div><!-- .woo-discord-steam-action-row-wrap -->
			
		<?php } ?>
		</div> 
	</div><!-- .woo-discord-steam-actions-container -->
</div><!-- .woocommerce_options_panel -->
