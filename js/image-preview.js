(function ($) {
    'use strict';

    Drupal.behaviors.imgix = {
        attach: function(context, settings) {
            $('.imgix-widget', context).once('imgix').each(function() {
                if (!$(this).find('.imgix-image').length) {
                    $(this).prepend('<div class="imgix-image"></div>');
                }

                var url = $(this).find('.file--image a').attr('href');
                if (url) {
                    $(this).find('.imgix-image').prepend('<img src="'+url+'">');
                }
            });
        }
    };

}(jQuery));
