(function ($) {

  var gen_slug = function (str) {
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();

    // remove accents, swap ñ for n, etc
    var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
    var to   = "aaaaaeeeeeiiiiooooouuuunc______";
    for (var i = 0, l = from.length; i < l; i++) {
      str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
      .replace(/\s+/g, '_') // collapse whitespace and replace by _
      .replace(/-+/g, '_'); // collapse dashes

    return str;
  };

  Drupal.bo_settings = Drupal.bo_settings || {};

  Drupal.behaviors.bo_settings = {
    attach: function (context) {

      $(".bo-settings-field-types-element").each(function () {
        let checkbox = $(this).closest("tr").find(".bo-settings-field-types-type-checkbox");
        if (!checkbox.is(':checked')) {
            $(this).css("display", "none");
        }
      });

      $(".fieldset-bo-settings-field-types table input.bo-settings-field-types-type-checkbox").on('change', function (event) {
        let checked = $(this).is(':checked');
        $(this).closest("tr").find(".bo-settings-field-types-element").each(function () {
          if (checked === true) {
            $(this).css('display', "block");
          } else {
            $(this).css('display', "none");
          }
        });

        event.stopImmediatePropagation();
      });

    }
  };

})(jQuery);
