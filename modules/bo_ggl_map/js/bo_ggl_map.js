/**
 * @file
 * Handle the bo_ggl_map specific JS.
 */

(function ($, Drupal, drupalSettings) {
  //'use strict';

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.bo_ggl_map = {
    attach: function (context, drupalSettings) {
    }
  };

  $(window).on({
    'bo:refreshView': function boGglMapRefreshView(event, result) {
      if ($(".js-view-dom-id-" + result.view_dom_id).find(' #ggl_map').length > 0) {
          Drupal.gglMap.attached = false;
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
