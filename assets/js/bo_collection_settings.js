(function ($) {
  Drupal.bo_collection_settings = Drupal.bo_collection_settings || {};

  Drupal.behaviors.bo_collection_settings = {
    attach: function (context) {

      $("input[checkbox-group='bo-settings-bundle']").click(function () {
        let toggle_fieldset = $(this).attr("toggle-fieldset");
        if ($(this).is(':checked')) {
          $("." + toggle_fieldset + " input").each(function () {
            $(this).prop('checked', TRUE);
          });
        } else {
          $("." + toggle_fieldset + " input").each(function () {
            $(this).prop('checked', FALSE);
          });
      }
      });

      $(".fieldset-bo-settings-block-types input").click(function () {
        if ($(this).is(':checked')) {
          let bo_settings_bundle_checkbox_name = $(this).attr("bo-settings-bundle-checkbox-name");
          $("input[name='" + bo_settings_bundle_checkbox_name + "']").prop('checked', TRUE);
        }
      });

      $('.check-uncheck-all').click(function () {

        let check_uncheck_type = $(this).attr('check-uncheck-type');
        //if ($(this).prop('checked')) {
          $('.' + check_uncheck_type + '-bundles input[type="checkbox"]').prop('checked', $(this).prop('checked'));
        //}
      });
    }
  }
})(jQuery);
