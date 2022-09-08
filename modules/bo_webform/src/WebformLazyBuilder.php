<?php

namespace Drupal\bo_webform;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 *
 */
class WebformLazyBuilder implements RenderCallbackInterface {

  /**
   *
   */
  public static function buildWebform($webform) {
    return [
      'webform' => [
        '#type' => 'webform',
        '#webform' => $webform,
      ],
    ];
  }

}
