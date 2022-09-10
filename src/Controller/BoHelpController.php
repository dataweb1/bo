<?php

namespace Drupal\bo\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class BoHelpController extends ControllerBase {

  /**
   * Render "help-content" div that then will be filled via "bo" variable in drupalSettings.
   *
   * @see "assets/js/bo_help.js"
   *
   * @param $collection_id
   * @param $entity_id
   *
   * @return array
   */
  public function help($collection_id, $entity_id) {
    return ['#markup' => '<div id="help-content" data-collection-id="' . $collection_id . '" data-entity-id="' . $entity_id . '"></div>'];
  }

}
