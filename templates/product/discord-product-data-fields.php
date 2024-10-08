<div id="discord_product_data" class="panel woocommerce_options_panel hidden">

    <div class="woo-discord-steam-actions-container">
        <div class="woo-discord-steam-header-container">
            <label>Discord Action</label>
            <div class="woo-discord-steam-header-buttons">
                <button type="button" id="add-discord-action">Add Discord Action</button>
            </div>
        </div>

        <?php
    
        $discord_action_rules = get_post_meta( $product_id, '_discord_action_rules', true );
        $discord_action_rules = ! empty( $discord_action_rules ) ? unserialize( $discord_action_rules ) : [];

        
        if ( empty( $discord_action_rules ) ) {
            $discord_action_rules[] = [
                'trigger' => '',
                'action'  => '',
                'server'  => '',
                'role'    => '',
            ];
        }

        
        foreach ( $discord_action_rules as $index => $rule ) {
        ?>
            <div class="woo-discord-steam-action" data-rule-id="<?php echo esc_attr( $index + 1 ); ?>">
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

                        <div class="then-section">
                            <span>Then</span>
                        </div>

                        <div class="dropdown-section">
                            <select class="action-dropdown" name="woo-discord-action[]">
                                <option value="assign_role" <?php selected( $rule['action'], 'assign_role' ); ?>>Assign role to customer on server</option>
                                <option value="remove_role" <?php selected( $rule['action'], 'remove_role' ); ?>>Remove role from customer on server</option>
                                <option value="send_message" <?php selected( $rule['action'], 'send_message' ); ?>>Send message on server</option>
                            </select>
                        </div>

                        <div class="then-section">
                            <span>On</span>
                        </div>

                        <div class="dropdown-section">
                            <select class="server-dropdown" name="woo-discord-server[]">
                                <option value="server_1" <?php selected( $rule['server'], 'server_1' ); ?>>Server 1</option>
                                <option value="server_2" <?php selected( $rule['server'], 'server_2' ); ?>>Server 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="role-section">
                        <label class="role-section-title">Role:</label>
                        <select class="role-dropdown" name="woo-discord-role[]">
                            <option value="rust_ensign" <?php selected( $rule['role'], 'rust_ensign' ); ?>>RUST Ensign</option>
                            <option value="rust_commander" <?php selected( $rule['role'], 'rust_commander' ); ?>>RUST Commander</option>
                        </select>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div><!-- .woo-discord-steam-actions-container -->
</div><!-- .woocommerce_options_panel -->
