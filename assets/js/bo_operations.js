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
        let bo_view_dom_id = $(context).attr('data-view-dom-id');
        let collection_id = $(context).attr('data-collection-id');
        $(window).trigger('bo:refreshView', {'bo_view_dom_id': bo_view_dom_id, 'collection_id': collection_id});
      }

      // Close the operations pane if a BO entity form or a BO entity delete form is opened.
      if ($('.bo-form', context).length > 0 || $(context).hasClass('bo-confirm-form') > 0) {
        Drupal.behaviors.bo_operations.closeOperationsPane();
      }


      let elements = [];
      elements =  $(once('bo_operations', '.bo-operations a', context));
      elements.each(function() {
        $(this).on("mouseenter", function (e) {
          $(this).find('.show-hide').show();
        });

        $(this).on("mouseleave", function (e) {
          $(this).find('.show-hide').hide();
        });
      });

      // Show/hide Edit / Delete / Help button.
      elements = $(once('bo_operations', '.node-entity-operations', context));
      elements.each(function() {
        let parent_wrapper = $(this).closest(".node-entity");

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".node-entity-operations").first().addClass("visible");
          $(this).addClass("bo-wrapper-selected");
        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".node-entity-operations").first().removeClass("visible");
          $(this).removeClass("bo-wrapper-selected");
        });
      });

      // Show/hide Edit / Delete / Help button.
      elements = $(once('bo_operations', '.bo-entity-operations', context));
      elements.each(function() {
        let parent_wrapper = $(this).closest(".bo-entity");

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-entity-operations").first().addClass("visible");
          $(this).addClass("bo-wrapper-selected");
        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-entity-operations").first().removeClass("visible");
          $(this).removeClass("bo-wrapper-selected");
        });

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-operation-help").first().addClass("visible");
        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-operation-help").first().removeClass("visible");
        });
      });

      // Show/hide Insert button.
      elements = $(once('bo_operations', '.bo-insert-operations', context));
      elements.each(function() {
        let parent_wrapper = $(this).closest(".bo-entity");

        $(parent_wrapper).on("mouseenter", function (e) {
          $(this).find(".bo-insert-operations").first().addClass("visible");
          $(this).addClass("bo-wrapper-selected");

        });

        $(parent_wrapper).on("mouseleave", function (e) {
          $(this).find(".bo-insert-operations").first().removeClass("visible");
          $(this).removeClass("bo-wrapper-selected");
          Drupal.behaviors.bo_operations.closeOperationsPane(true);
        });
      });


      elements = $(once('bo_operations', '.bo-entity', context));
      elements.each(function() {
        $(this).children().each(function() {
          let id = $(this).attr('id');
          if (typeof id !== 'undefined' && id !== false) {
            if (id.search('bo_operations_pane') === -1) {
              $(this).parent().find('.bo-entity-operations .id-tag').html('#' + id);
              $(this).parent().find('.bo-entity-operations .id-tag').css('display', 'block');
              return false;
            }
          }
          else {
            $(this).parent().find('.bo-entity-operations .id-tag').css('display', 'none');
          }
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
    refreshView: function (bo_view_dom_id) {
      if (bo_view_dom_id !== "") {
        if (Drupal.views) {
          if (Drupal.views.instances["views_dom_id:" + bo_view_dom_id]) {
            var current_path = '/' + drupalSettings.path.currentPath;
            var collection_id = $(".js-view-dom-id-" + bo_view_dom_id).attr("data-collection-id");
            var reload = $(".js-view-dom-id-" + bo_view_dom_id).attr("data-reload");
            var nid = $(".js-view-dom-id-" + bo_view_dom_id).attr("data-nid");
            if (reload == '0') {
              Drupal.views.instances["views_dom_id:" + bo_view_dom_id].refreshViewAjax.options.data.bo_view_dom_id = bo_view_dom_id;
              Drupal.views.instances["views_dom_id:" + bo_view_dom_id].refreshViewAjax.options.data.current_path = current_path;
              Drupal.views.instances["views_dom_id:" + bo_view_dom_id].refreshViewAjax.options.data.collection_id = collection_id;
              Drupal.views.instances["views_dom_id:" + bo_view_dom_id].refreshViewAjax.options.data.nid = nid;

              $(".js-view-dom-id-" + bo_view_dom_id).triggerHandler("RefreshView");
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
