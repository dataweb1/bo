(function ($, Drupal, drupalSettings) {

  Drupal.bo_fields_popup = Drupal.bo_fields_popup || {};

  Drupal.behaviors.bo_fields_popup = {
    attach: function (context) {
      $('.bo-fields-popup-modal').once().each(function () {
        let popup = $(this).clone(true, true);
        popup.appendTo('body');
        $(this).remove();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
