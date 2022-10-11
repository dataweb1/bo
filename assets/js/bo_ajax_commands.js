(function ($, Drupal, drupalSettings, window) {
  'use strict';
  Drupal.AjaxCommands.prototype.refreshPageCommand = function (ajax, response) {
    window.location.reload();
  };

  Drupal.AjaxCommands.prototype.refreshViewCommand = function (ajax, response) {
    let view_dom_id = response.view_dom_id;
    Drupal.behaviors.bo_operations.refreshView(view_dom_id);
  };

  Drupal.AjaxCommands.prototype.closeOparationsPaneCommand = function (ajax, response) {
    Drupal.behaviors.bo_operations.closeOperationsPane();
  }

  Drupal.AjaxCommands.prototype.slideCommand = function (ajax, response) {

    let view_dom_id = response.view_dom_id;
    let entity_id = response.entity_id;
    let action = response.action;

    let selector;
    if (entity_id > 0) {
      selector = "#bo_operations_pane_" + view_dom_id + "_" + entity_id;
    }
    else {
      selector = "#bo_operations_pane_" + view_dom_id;
    }

    window.keep_closed = false;
    let open_id =  '#' + $('.bo-operations-pane.open').attr('id');

    if (open_id === selector && action === open_action) {
      Drupal.behaviors.bo_operations.closeOperationsPane();
    }

    if (action !== window.open_action && window.keep_closed === false) {
      $(selector).slideDown(200, function() {
        $(".bo-operations a").removeClass("active");
        $(selector).addClass("open");
        window.open_action = action;
      });
    }
  };

}(jQuery, Drupal, drupalSettings, window));
