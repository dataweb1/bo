<?php

namespace Drupal\bo\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class BoEntityController extends ControllerBase {

  /**
   * Get title.
   */
  public function getInsertTitle($bundle) {

    $title = $this->t(
      "Insert @bundle",
      [
        '@bundle' => $bundle->get("label"),
      ]
    );

    return $title;
  }

}
