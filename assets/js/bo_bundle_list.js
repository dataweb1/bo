(function ($, window, Drupal) {

  Drupal.behaviors.bundleDrag = {
    attach: function attach(context, settings) {
      if (typeof Drupal.tableDrag === 'undefined' || typeof Drupal.tableDrag.bundles === 'undefined') {
        return;
      }

      function checkEmptyGroups(table, rowObject) {
        table.find('tr.group-message').each(function () {
          let $this = $(this);

          if ($this.prev('tr').get(0) === rowObject.element) {
              if (rowObject.method !== 'keyboard' || rowObject.direction === 'down') {
                  rowObject.swap('after', this);
              }
          }

          if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
              $this.removeClass('group-populated').addClass('group-empty');
          } else if ($this.is('.group-empty')) {
              $this.removeClass('group-empty').addClass('group-populated');
          }
        });
      }

      function updateLastPlaced(table, rowObject) {
        table.find('.color-success').removeClass('color-success');

        let $rowObject = $(rowObject);
        if (!$rowObject.is('.drag-previous')) {
          table.find('.drag-previous').removeClass('drag-previous');
          $rowObject.addClass('drag-previous');
        }
      }

      function updateBundleWeights(table, group) {
        let weight = -Math.round(table.find('.draggable').length / 2);

        table.find('.group-' + group + '-message').nextUntil('.group-title').find('select.bundle-weight').val(function () {
          return ++weight;
        });
      }

      let table = $('#bundles');

      let tableDrag = Drupal.tableDrag.bundles;

      tableDrag.row.prototype.onSwap = function (swappedRow) {
        checkEmptyGroups(table, this);
        updateLastPlaced(table, this);
      };

      tableDrag.onDrop = function () {
        let dragObject = this;
        let $rowElement = $(dragObject.rowObject.element);

        let groupRow = $rowElement.prevAll('tr.group-message').get(0);
        let groupName = groupRow.className.replace(/([^ ]+[ ]+)*group-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
        let groupField = $rowElement.find('select.bundle-group-select');

        if (groupField.find('option[value=' + groupName + ']').length === 0) {
          window.alert(Drupal.t('The bundle cannot be placed in this group.'));

          groupField.trigger('change');
        }

        if (!groupField.is('.bundle-group-' + groupName)) {
          let weightField = $rowElement.find('select.bundle-weight');
          let oldGroupName = weightField[0].className.replace(/([^ ]+[ ]+)*bundle-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');
          groupField.removeClass('bundle-group-' + oldGroupName).addClass('bundle-group-' + groupName);
          weightField.removeClass('bundle-weight-' + oldGroupName).addClass('bundle-weight-' + groupName);
          groupField.val(groupName);
        }

        updateBundleWeights(table, groupName);
      };

      $(context).find('select.bundle-group-select').once('bundle-group-select').on('change', function (event) {
        let row = $(this).closest('tr');
        let select = $(this);

        tableDrag.rowObject = new tableDrag.row(row[0]);

        let groupMessage = table.find('.group-' + select[0].value + '-message');
        let groupItems = groupMessage.nextUntil('.group-message, .group-title');
        if (groupItems.length) {
          groupItems.last().after(row);
        } else {
          groupMessage.after(row);
        }
        updateBundleWeights(table, select[0].value);

        checkEmptyGroups(table, tableDrag.rowObject);

        updateLastPlaced(table, row);

        if (!tableDrag.changed) {
          $(Drupal.theme('tableDragChangedWarning')).insertBefore(tableDrag.table).hide().fadeIn('slow');
          tableDrag.changed = true;
        }

        select.trigger('blur');
      });
    }
  };
})(jQuery, window, Drupal);
