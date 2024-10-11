( function($) {

    jQuery(document).ready(function($) {
        $('.copy-btn').on('click', function() {
            var targetInput = $('#' + $(this).data('target'));
            targetInput.select();
            targetInput[0].setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
            } catch (err) {
                alert('Failed to copy text.');
            }
        });

        $('#add-second-server').on('click', function() {
            $('#second-server-settings').slideDown();
            $(this).hide();
        });

    });

} )(jQuery);
