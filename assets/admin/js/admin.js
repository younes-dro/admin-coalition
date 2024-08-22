( function($) {

    // console.log (etsWooDiscordSteamParams );

    jQuery(document).ready(function($) {
        $('.copy-btn').on('click', function() {
            var targetInput = $('#' + $(this).data('target'));
            targetInput.select();
            targetInput[0].setSelectionRange(0, 99999); // For mobile devices
    
            try {
                document.execCommand('copy');
                // alert('Copied to clipboard: ' + targetInput.val());
            } catch (err) {
                alert('Failed to copy text.');
            }
        });
    });
    

} )(jQuery);