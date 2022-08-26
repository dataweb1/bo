<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Entity\BoBundleEntity;
use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class BoBundleController extends ControllerBase {

  /**
   * Get title.
   */
  public function getBoBundleAddFormTitle($type) {

    if ($type == "element") {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("element"),
      ]);
    }

    if ($type == "content") {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("content"),
      ]);
    }

    return $title;
  }

  /**
   * Get title.
   */
  public function getBoBundleContentTypeListTitle() {
    $title = "BO " . $this->t("content");
    return $title;
  }

  /**
   * Get title.
   */
  public function getBoBundleElementListTitle() {
    $title = "BO " . $this->t("elements");
    return $title;
  }

  /**
   * Add form.
   */
  public function renderBoBundleAddForm($type) {

    $entity = BoBundleEntity::create();
    $entity->setType($type);
    $form = \Drupal::service('entity.form_builder')->getForm($entity, 'default');

    return [
      'form' => $form,
    ];
  }

}
