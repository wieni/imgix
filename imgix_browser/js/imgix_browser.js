(function($) {
    $(document).ready(function() {
        $('.imgix-browser-item input:checkbox').hide();
        $('.imgix-browser-item').click(function() {
            var thisCheck = $(this).find('input:checkbox').first();
            thisCheck.prop("checked", !thisCheck.prop("checked"));
            $(this).toggleClass('active');
        });
    });
})(jQuery);
