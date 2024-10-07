(function($) {
    console.log(etsWooDiscordSteamParams);
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
        cloned.insertBefore($('.woo-steam-submit'));
    });

    
    $(document).on('click', '.remove-action-btn', function(e) {
        e.preventDefault();
        $(this).closest('div.woo-discord-steam-action').remove();
    });

    $('#submit-discord-actions').on('click', function(e) {
        e.preventDefault(); 
        var discordDivs = $('.woo-discord-steam-action'); 
        const dataRules = {}; 
    
        discordDivs.each(function(index) {
            var ruleId = $(this).data('rule-id'); 
            dataRules[ruleId] = {}; 
            
            
            $(this).find('select').each(function() {
                var fieldName = $(this).attr('name').replace(/\[\d+\]/, ''); 
                dataRules[ruleId][fieldName] = $(this).val(); 
            });
        });
    
        var product_id = $('#woo-discord-steam-product-id').val();
    
        
        $.ajax({
            url: etsWooDiscordSteamParams.admin_ajax,
            method: 'POST',
            data: {
                action: 'save_discord_actions',
                ets_woo_discord_steam_nonce: etsWooDiscordSteamParams.ets_woo_discord_steam_nonce,
                product_id: product_id,
                rules: dataRules 
            },
            success: function(response) {
                console.log('Actions saved successfully:', response);
            },
            error: function() {
                alert('There was an error saving the actions.');
            }
        });
    });
    
    
})(jQuery);

