 (function($) {
     $(window).load(function() {
         "use strict";

         var stars = $('.rating__choose');

         stars.unbind('click').click(function() {
             stars.each(function() {
                 if ($(this).is(':checked')) {
                     $(this).prop('checked', false);
                 }
             })

             $('#stars-rating-comment').val($(this).attr('value'));

             $(this).prop('checked', true);
         });

     });
 })(jQuery);