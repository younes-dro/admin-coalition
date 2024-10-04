<form id="discord_actions_form">
            
    <input type="text" name="woo-discord-steam-product-id" value=" <?php echo intval($product_id) ?>" />
    <div id="discord_product_data" class="panel woocommerce_options_panel">

        <div class="woo-discord-steam-actions-container">
            <div class="woo-discord-steam-header-container">
                <h2 class="woo-discord-steam-header-title">Discord Action</h2>
                <div class="woo-discord-steam-header-buttons">
                    <button id="help-discord-action" class="add-action-btn-help">Help</button>
                    <button id="add-discord-action" class="add-action-btn">Add Discord Action</button>
                </div>
            </div>

            
            <div class="woo-discord-steam-action" data-rule-id="1">
                <div class="action-row">
                    <div class="dropdown-section">
                        <select class="trigger-dropdown" name="trigger[1]">
                            <option value="purchased">When the package is purchased</option>
                            <option value="subscription_purchased">When the subscription is purchased</option>
                            <option value="refund">When the package is refunded</option>
                            <option value="subscription_refund">When the subscription is refunded</option>
                            <option value="chargebacked">When the package is chargebacked</option>
                            <option value="renew">When the subscription is renewed</option>
                        </select>
                    </div>
                    <div class="then-section">
                        <span>Then</span>
                    </div>
                    <div class="dropdown-section">
                        <select class="action-dropdown" name="action[1]">
                            <option value="assign_role">Assign role to customer on server</option>
                            <option value="remove_role">Remove role from customer on server</option>
                            <option value="send_message">Send message on server</option>
                        </select>
                    </div>
                    <div class="then-section">
                        <span>On</span>
                    </div>
                    <div class="dropdown-section">
                        <select class="server-dropdown" name="server[1]">
                            <option value="server_1">Server 1</option>
                            <option value="server_2">Server 2</option>
                        </select>
                    </div>
                    <button class="remove-action-btn">X</button>
                </div>

                <div class="role-section">
                    <label class="role-section-title">Role:</label>
                    <select class="role-dropdown" name="role[1]">
                        <option value="rust_ensign">RUST Ensign</option>
                        <option value="rust_commander">RUST Commander</option>
                    </select>
                </div>
            </div>

            <div class="submit">
                <button type="submit" id="submit-discord-actions" class="submit-action-btn">Save Actions</button>
            </div>
        </div><!-- .woo-discord-steam-actions-container -->
    </div><!-- .woocommerce_options_panel -->
</form>
