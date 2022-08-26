<?php

namespace Drupal\bo\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show block fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bo_fields")
 */
class BoFields extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {

  }

}
