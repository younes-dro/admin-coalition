(function($) {
    // console.log(etsWooDiscordSteamParams);
    $('#add-discord-action').on('click', function(e) {
        e.preventDefault();
        var newRow = $('.woo-discord-steam-action-row-wrap:first').clone();
        $(newRow).find('select').val('');   
        $('.woo-discord-steam-action-row-wrap:last').after(newRow);

    });

    $(document).on('click', '.woo-discord-steaam-remove-wrap', function(e) {
        e.preventDefault();
        $(this).closest('div.woo-discord-steam-action-row-wrap').remove();
    });

    
})(jQuery);

