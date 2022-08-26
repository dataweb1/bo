(function ($) {

  Drupal.bo_bundle = Drupal.bo_bundle || {};

  Drupal.behaviors.bo_bundle = {
    attach: function (context) {

      $(".bo-bundle-text-override-title-label").each(function () {
        let checkbox = $(this).closest("tr").find(".bo-bundle-checkbox-internal-title");
        if (checkbox.is(':checked')) {
          $(this).css("display", "none");
        }
        else {
          $(this).css("display", "block");
        }
      });

      $(".bo-bundle-collection").each(function () {
        let checkbox = $(this).closest("tr").find(".bo-bundle-checkbox-collection");
        if (checkbox.is(':checked')) {
          $(this).css("display", "inline-block");
        }
        else {
          $(this).css("display", "none");
        }
      });

      attachBoEntityClickEvents();

      function attachBoEntityClickEvents() {
        $(".bo-bundle-checkbox-internal-title").on('change', function (event) {
          let checked = $(this).is(':checked');

          $(this).closest("tr").find(".bo-bundle-text-override-title-label").each(function () {
            if (checked === true) {
              $(this).css('display', "none");
            } else {
              $(this).css('display', "block");
            }
          });

          event.stopImmediatePropagation();
        });

        $(".bo-bundle-checkbox-collection").on('change', function (event) {
          let checked = $(this).is(':checked');

          $(this).closest("tr").find(".bo-bundle-collection").each(function () {
            if (checked === true) {
              $(this).css('display', "inline-block");
            } else {
              $(this).css('display', "none");
            }
          });
          event.stopImmediatePropagation();
        });
      }

    }
  }
})(jQuery);
