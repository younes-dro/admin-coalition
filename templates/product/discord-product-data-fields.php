<div id="discord_product_data" class="panel woocommerce_options_panel">
    <?php 
    $saved_rules = get_post_meta($product_id, '_discord_action_rules', true); 
    $saved_rules = maybe_unserialize($saved_rules);
    if (!$saved_rules) {
        $saved_rules = [];
    }
    ?>

    <input type="hidden" id="woo-discord-steam-product-id" name="woo-discord-steam-product-id" value="<?php echo intval($product_id); ?>" />

    <div class="woo-discord-steam-actions-container">
        <div class="woo-discord-steam-header-container">
            <h2 class="woo-discord-steam-header-title">Discord Action</h2>
            <div class="woo-discord-steam-header-buttons">
                <button id="help-discord-action" class="add-action-btn-help">Help</button>
                <button id="add-discord-action" class="add-action-btn">Add Discord Action</button>
            </div>
        </div>

        <?php 
        $rule_id = 1;
        foreach ($saved_rules as $rule_id => $rule_data) { ?>
            <div class="woo-discord-steam-action" data-rule-id="<?php echo esc_attr($rule_id); ?>">
                <div class="action-row">
                    <div class="dropdown-section">
                        <select class="trigger-dropdown" name="trigger[<?php echo esc_attr($rule_id); ?>]">
                            <option value="purchased" <?php selected($rule_data['trigger'], 'purchased'); ?>>When the package is purchased</option>
                            <option value="subscription_purchased" <?php selected($rule_data['trigger'], 'subscription_purchased'); ?>>When the subscription is purchased</option>
                            <option value="refund" <?php selected($rule_data['trigger'], 'refund'); ?>>When the package is refunded</option>
                            <option value="subscription_refund" <?php selected($rule_data['trigger'], 'subscription_refund'); ?>>When the subscription is refunded</option>
                            <option value="chargebacked" <?php selected($rule_data['trigger'], 'chargebacked'); ?>>When the package is chargebacked</option>
                            <option value="renew" <?php selected($rule_data['trigger'], 'renew'); ?>>When the subscription is renewed</option>
                        </select>
                    </div>

                    <div class="then-section">
                        <span>Then</span>
                    </div>

                    <div class="dropdown-section">
                        <select class="action-dropdown" name="action[<?php echo esc_attr($rule_id); ?>]">
                            <option value="assign_role" <?php selected($rule_data['action'], 'assign_role'); ?>>Assign role to customer on server</option>
                            <option value="remove_role" <?php selected($rule_data['action'], 'remove_role'); ?>>Remove role from customer on server</option>
                            <option value="send_message" <?php selected($rule_data['action'], 'send_message'); ?>>Send message on server</option>
                        </select>
                    </div>

                    <div class="then-section">
                        <span>On</span>
                    </div>

                    <div class="dropdown-section">
                        <select class="server-dropdown" name="server[<?php echo esc_attr($rule_id); ?>]">
                            <option value="server_1" <?php selected($rule_data['server'], 'server_1'); ?>>Server 1</option>
                            <option value="server_2" <?php selected($rule_data['server'], 'server_2'); ?>>Server 2</option>
                        </select>
                    </div>

                    <button class="remove-action-btn">X</button>
                </div>

                <div class="role-section">
                    <label class="role-section-title">Role:</label>
                    <select class="role-dropdown" name="role[<?php echo esc_attr($rule_id); ?>]">
                        <option value="rust_ensign" <?php selected($rule_data['role'], 'rust_ensign'); ?>>RUST Ensign</option>
                        <option value="rust_commander" <?php selected($rule_data['role'], 'rust_commander'); ?>>RUST Commander</option>
                    </select>
                </div>
            </div>
        <?php } ?>

        <div class="submit woo-steam-submit">
            <button type="button" id="submit-discord-actions" class="submit-action-btn">Save Actions</button>
        </div>
    </div><!-- .woo-discord-steam-actions-container -->
</div><!-- .woocommerce_options_panel -->
