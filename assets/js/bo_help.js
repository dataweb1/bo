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

})(jQuery, Drupal, drupalSettings);
