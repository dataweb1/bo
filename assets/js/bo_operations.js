(function ($, Drupal, drupalSettings) {
  // Initialize.
  window.open_action = "";
  window.keep_closed = false;
  window.mouseenter = false;

  Drupal.bo_operations = Drupal.bo_operations || {};

  Drupal.behaviors.bo_operations = {
    attach: function (context, settings) {

      // Execute a bo:refreshView trigger after refreshing a BO view.
      if ($(context).hasClass('bo-overview')) {
        let view_dom_id = $(context).attr('data-view-dom-id');
        let collection_id = $(context).attr('data-collection-id');
        $(window).trigger('bo:refreshView', {'view_dom_id': view_dom_id, 'collection_id': collection_id});
      }

      // Close the operations pane if a BO entity form or a BO entity delete form is opened.
      if ($('.bo-form', context).length > 0 || $(context).hasClass('bo-confirm-form') > 0) {
        Drupal.behaviors.bo_operations.closeOperationsPane();
      }

      // Show/hide Edit / Delete / Help button.
      $(".bo-content-operations").once().each(function () {

        let parent_wrapper = $(this).closest(".bo-entity");

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-content-operations").first().css("display", "block");
          $(this).addClass("bo-wrapper-selected");
        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-content-operations").first().css("display", "none");
          $(this).removeClass("bo-wrapper-selected");
        });

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-trigger-help").first().css("display", "block");
        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-trigger-help").first().css("display", "none");
        });
      });

      // Show/hide Insert button.
      $(".bo-entity-operations").once().each(function () {

        let parent_wrapper = $(this).closest(".bo-entity");

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-entity-operations").first().css("display", "block");
          //$(this).find(".bo-trigger-reorder").css("display", "block");
          $(this).addClass("bo-wrapper-selected");

        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-entity-operations").first().css("display", "none");
          $(this).removeClass("bo-wrapper-selected");
          Drupal.behaviors.bo_operations.closeOperationsPane(true);
        });
      });

    },
    closeOperationsPane: function (insert_pane = false) {
      let selector = '.bo-operations-pane';
      if (insert_pane === true) {
        selector += '.insert-pane';
      }
      $(selector).slideUp(200, function () {
        $(selector).removeClass("open");
        window.open_action = "";
        window.keep_closed = true;
      });
    },
    refreshView: function (view_dom_id) {
      if (view_dom_id !== "") {
        if (Drupal.views) {
          if (Drupal.views.instances["views_dom_id:" + view_dom_id]) {
            var current_path = '/' + drupalSettings.path.currentPath;
            var collection_id = $(".js-view-dom-id-" + view_dom_id).attr("data-collection-id");
            var reload = $(".js-view-dom-id-" + view_dom_id).attr("data-reload");
            if (reload == '0') {
              Drupal.views.instances["views_dom_id:" + view_dom_id].refreshViewAjax.options.data.view_dom_id = view_dom_id;
              Drupal.views.instances["views_dom_id:" + view_dom_id].refreshViewAjax.options.data.current_path = current_path;
              Drupal.views.instances["views_dom_id:" + view_dom_id].refreshViewAjax.options.data.collection_id = collection_id;

              $(".js-view-dom-id-" + view_dom_id).triggerHandler("RefreshView");
            }
            else {
              Drupal.AjaxCommands.prototype.refreshPageCommand();
            }
          } else {
            Drupal.AjaxCommands.prototype.refreshPageCommand();
          }
        } else {
          Drupal.AjaxCommands.prototype.refreshPageCommand();
        }
      }
    },
  };

})(jQuery, Drupal, drupalSettings);
