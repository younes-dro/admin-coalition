<div id="discord_product_data" class="panel woocommerce_options_panel">
<?php
if ( isset( $product_id ) && ! empty( $product_id ) ) {
    $product_id = intval( $product_id );



} else {
    echo $product_id = 0; 
}

?>


<div class="woo-discord-steam-actions-container">
    <div class="woo-discord-steam-header-container">
        <h2 class="woo-discord-steam-header-title">Discord Action</h2>
        <button id="add-discord-action" class="add-action-btn">Help</button>
        <button id="add-discord-action" class="add-action-btn">Add Discord Action</button>
    </div>


    <div class="woo-discord-steam-action">
        <div class="action-row">
            <div class="dropdown-section">
                <label>When:</label>
                <select class="trigger-dropdown">
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
                <label>Action:</label>
                <select class="action-dropdown">
                    <option value="assign_role">Assign role to customer on server</option>
                    <option value="remove_role">Remove role from customer on server</option>
                    <option value="send_message">Send message on server</option>
                </select>
            </div>

            <div class="dropdown-section">
                <label>On:</label>
                <select class="server-dropdown">
                    <option value="server_1">Server 1</option>
                    <option value="server_2">Server 2</option>
                </select>
            </div>

            <button class="remove-action-btn">X</button>
        </div>

        <div class="role-section">
            <label>Role:</label>
            <select class="role-dropdown">
                <option value="rust_ensign">RUST Ensign</option>
                <option value="rust_commander">RUST Commander</option>
                
            </select>
        </div>
    </div>
</div>


</div>
