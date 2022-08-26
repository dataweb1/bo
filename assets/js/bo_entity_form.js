(function ($) {

  Drupal.bo_entity_form = Drupal.bo_entity_form || {};

  Drupal.behaviors.bo_entity_form = {
    attach: function (context) {

      $(".bo-form .field--name-type select").on("change", function () {
        changeType();
      });

      let option_count = 0;
      let select = $(".bo-form .field--name-type select");
      $(select).find('option').each(function (index,element) {
        if (element["value"] !== "_none") {
          option_count++;
        }
      });

      if (option_count === 1) {
        $(".bo-form .field--name-type select").val($(".bo-form .field--name-type select option:last-child").attr("value"));
        $(".bo-form .field--name-type").css("display", "none");
      }
      changeType();
    }
  };

  function changeType() {
    let selected_type = $(".bo-form .field--name-type select").val();
    if (selected_type !== "_none" && selected_type !== "") {
      $(".field--name-size").css("display", "block");
    }
    else {
      $(".field--name-size").css("display", "none");
    }

    $( ".bo-field" ).each( function () {
      if($(this).attr('bo-field-properties')) {
        let properties = $.parseJSON($(this).attr("bo-field-properties"));

        if (properties.hasOwnProperty(selected_type)) {
          $(this).css("display", "block");

          if (selected_type !== '_none') {

            $(this).find("label,.label,.fieldset-legend").first().html($(this).attr("bo-original-label"));
            if (typeof properties[selected_type]["internal_title"] !== "undefined" && properties[selected_type]["internal_title"] === true) {
              $(this).find("label,.label").first().html(Drupal.t("Internal title"));
            }
            else {
              if (typeof properties[selected_type]["override_title_label"] !== "undefined" && properties[selected_type]["override_title_label"] !== "") {
                $(this).find("label,.label,.fieldset-legend").first().html(properties[selected_type]["override_title_label"]);
              }
            }
          }
        }
        else {
          $(this).css("display", "none");
        }
      }
      else {
        $(this).css("display", "none");
      }
    });
  }
})(jQuery);
