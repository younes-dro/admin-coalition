<?php

$discord_action_rules = get_post_meta( $product_id, '_discord_action_rules', true );
$discord_action_rules = ! empty( $discord_action_rules ) ? unserialize( $discord_action_rules ) : array();

if ( empty( $discord_action_rules ) ) {
    $discord_action_rules[] = array(
        'trigger' => '',
        'action'  => '',
        'server'  => '',
        'role'    => '',
        'channel' => '',
        'message' => '',
    );
}

$server_1 = sanitize_text_field( trim( get_option( 'discord_server_id' ) ) );
$server_2 = sanitize_text_field( trim( get_option( 'discord_server_id_2' ) ) );

$server_1_roles = maybe_unserialize( get_option( 'discord_all_roles_' . $server_1 ) );
$server_2_roles = maybe_unserialize( get_option( 'discord_all_roles_' . $server_2 ) );

$server_1_channels = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $server_1 );
$server_2_channels = Woo_Discord_Steam_Integration_Utils::fetch_discord_channels( $server_2 );

error_log( print_r( $discord_action_rules, true ) );

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
                                <option value="<?php echo esc_attr( $server_1 ); ?>" <?php selected( $rule['server'], $server_1 ); ?>>Server 1</option>
                                <option value="<?php echo esc_attr( $server_2 ); ?>" <?php selected( $rule['server'], $server_2 ); ?>>Server 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="role-section">
                        <select class="role-dropdown server-1-roles" name="woo-discord-role[]" <?php echo $rule['server'] == $server_1 ? '' : 'disabled style="display:none;"'; ?>>
                            <?php foreach ( $server_1_roles as $role_id => $role_name ) : ?>
                                <option value="<?php echo esc_attr( $role_id ); ?>" <?php selected( $rule['role'], $role_id ); ?>>
                                    <?php echo esc_html( $role_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="role-dropdown server-2-roles" name="woo-discord-role[]" <?php echo $rule['server'] == $server_2 ? '' : 'disabled style="display:none;"'; ?>>
                            <?php foreach ( $server_2_roles as $role_id => $role_name ) : ?>
                                <option value="<?php echo esc_attr( $role_id ); ?>" <?php selected( $rule['role'], $role_id ); ?>>
                                    <?php echo esc_html( $role_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="channel-section">
                        <select class="channel-dropdown server-1-channels" name="woo-discord-channel[]" <?php echo ( $rule['server'] == $server_1 && $rule['action'] == 'send_message') ? '' : ' style="display:none;"'; ?> >
                            <?php foreach( $server_1_channels as $channel_id => $channel_name) :?>
                                <option value="<?php echo esc_attr( $channel_id);?>" <?php echo  isset ($rule['channel'])  ? selected( $rule['channel'], $channel_id, false) : '' ?> >
                                    <?php echo esc_html( $channel_name)?>
                                </option>
                            <?php endforeach ?>
                        </select>  
                        <select class="channel-dropdown server-2-channels" name="woo-discord-channel[]" <?php echo ( $rule['server'] == $server_2 && $rule['action'] == 'send_message') ? '' : ' style="display:none;"'; ?> >
                            <?php foreach( $server_2_channels as $channel_id => $channel_name) :?>
                                <option value="<?php echo esc_attr( $channel_id);?>" <?php echo  isset ($rule['channel'])  ? selected( $rule['channel'], $channel_id, false) : '' ?>>
                                    <?php echo esc_html( $channel_name)?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="message-section">
                      <?php $rule_message= trim( $rule['message']) ?>  
                        <textarea class="message-textarea" name="woo-discord-message[]" <?php echo ($rule['action'] == 'send_message') ? '' : 'disabled style="display:none;"'; ?>><?php echo esc_textarea( $rule_message)?></textarea>
                    </div>

                    <div class="woo-discord-steaam-remove-wrap">
                        <span class="woo-discord-steam-remove-action-row">X</span>
                    </div>
                </div><!-- .woo-discord-steam-action-row-wrap -->
            <?php
        }
        ?>
        </div> 
    </div><!-- .woo-discord-steam-actions-container -->
</div><!-- .woocommerce_options_panel -->

<script>
    jQuery(document).ready(function($) {
        function toggleRoleAndChannelSections(actionDropdown, serverDropdown) {
            var selectedAction = $(actionDropdown).val();
            var selectedServer = $(serverDropdown).val();
            var rowWrap = $(actionDropdown).closest('.woo-discord-steam-action-row-wrap');

            var roleSection = rowWrap.find('.role-section');
            var channelSection = rowWrap.find('.channel-section');
            var messageSection = rowWrap.find('.message-section');

            
            roleSection.find('.role-dropdown').prop('disabled', true).hide();
            channelSection.find('.channel-dropdown').prop('disabled', true).hide();
            messageSection.find('.message-textarea').prop('disabled', false).hide();

            if (selectedAction === 'send_message') {
                
                if (selectedServer === '<?php echo esc_js($server_1); ?>') {
                    channelSection.find('.server-1-channels').prop('disabled', false).show();
                } else if (selectedServer === '<?php echo esc_js($server_2); ?>') {
                    channelSection.find('.server-2-channels').prop('disabled', false).show();
                }
                messageSection.find('.message-textarea').prop('disabled', false).show();
            } else {
                
                if (selectedServer === '<?php echo esc_js($server_1); ?>') {
                    roleSection.find('.server-1-roles').prop('disabled', false).show();
                } else if (selectedServer === '<?php echo esc_js($server_2); ?>') {
                    roleSection.find('.server-2-roles').prop('disabled', false).show();
                }
            }
        }

        
        $('.action-dropdown, .server-dropdown').each(function() {
            toggleRoleAndChannelSections($(this).closest('.woo-discord-steam-action-row-wrap').find('.action-dropdown'), $(this).closest('.woo-discord-steam-action-row-wrap').find('.server-dropdown'));
        });

        
        $(document).on('change', '.action-dropdown', function() {
            toggleRoleAndChannelSections(this, $(this).closest('.woo-discord-steam-action-row-wrap').find('.server-dropdown'));
        });

        $(document).on('change', '.server-dropdown', function() {
            toggleRoleAndChannelSections($(this).closest('.woo-discord-steam-action-row-wrap').find('.action-dropdown'), this);
        });
    });
</script>
