(function($) {

    // Click event to add a new Discord action
    $('#add-discord-action').on('click', function(e) {
        e.preventDefault();
        
        
        var lastRule = $('div.woo-discord-steam-action').last();
        var newRuleId = parseInt(lastRule.data('rule-id')) + 1;

        var cloned = lastRule.clone();
        cloned.attr('data-rule-id', newRuleId);
        

        cloned.find('select').each(function() {
            var name = $(this).attr('name');
            var newName = name.replace(/\[\d+\]/, '[' + newRuleId + ']');
            $(this).attr('name', newName);
            $(this).val(''); 
        });

        
        $('div.woo-discord-steam-actions-container').append(cloned);
    });

    
    $(document).on('click', '.remove-action-btn', function(e) {
        e.preventDefault();
        $(this).closest('div.woo-discord-steam-action').remove();
    });

    $('#discord_actions_form').on('submit', function(e) {
        e.preventDefault(); 
        console.log(etsWooDiscordSteamParams);
        var formData = $(this).serialize();
        var product_id = $('#woo-discord-steam-product-id').val();
        $.ajax({
            url: etsWooDiscordSteamParams.admin_ajax, 
            method: 'POST',
            data: {
                action: 'save_discord_actions', 
                product_id: product_id, 
                form_data: formData
            },
            success: function(response) {
                console.log(response);
                alert('Actions saved successfully!');
            },
            error: function() {
                alert('There was an error saving the actions.');
            }
        });
    });
})(jQuery);

