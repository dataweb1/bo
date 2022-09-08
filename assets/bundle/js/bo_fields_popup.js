(function ($, Drupal, drupalSettings) {

  Drupal.bo_bundle_popup = Drupal.bo_bundle_popup || {};

  Drupal.behaviors.bo_bundle_popup = {
    attach: function (context) {
      $('.bo-fields-popup-modal').once().each(function () {
        let popup = $(this).clone(true, true);
        popup.appendTo('body');
        $(this).remove();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
