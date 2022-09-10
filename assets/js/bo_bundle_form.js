(function ($) {

  Drupal.bo_bundle_form = Drupal.bo_bundle_form || {};

  Drupal.behaviors.bo_bundle_form = {
    attach: function (context) {
      $('div[class*="js-form-item-related-bundles-group__"]', context).each(function() {
        $(this).css('margin-left', 0);
        $(this).find('input').remove();
        $(this).find('label').css('font-weight', 'bold');
      });
    }
  }
})(jQuery);
