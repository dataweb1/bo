<?php

namespace Drupal\bo\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local action plugin with a dynamic title.
 */
class BoBundleLocalAction extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {

    $uri = $request->getUri();

    if (strpos($uri, "/element/list") !== FALSE) {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("element"),
      ]);
    }

    if (strpos($uri, "/content/list") !== FALSE) {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("content"),
      ]);
    }

    return $title;
  }

}
