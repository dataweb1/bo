(function ($, Drupal, drupalSettings) {
  // Initialize.
  Drupal.bo_help = Drupal.bo_help || {};

  Drupal.behaviors.bo_help = {
    attach: function (context, settings) {
      let id = $(context).attr('id');
      if (id === 'help-content') {
        let entityId = $(context).attr('data-entity-id');
        $('#help-content').html(drupalSettings.bo[entityId]['#markup']);
      }
    }
  };

  $(document).ready(function () {
    // Show/hide Help button.
    $(".bo-content-operations").each(function () {

      let parent_wrapper = $(this).closest(".bo-entity");

      $(parent_wrapper).on("mouseenter", function (e) {
        $(this).find(".bo-trigger-help").first().css("display", "block");
      });

      $(parent_wrapper).on("mouseleave", function (e) {
        $(this).find(".bo-trigger-help").first().css("display", "none");
      });
    });
  });

})(jQuery, Drupal, drupalSettings);
