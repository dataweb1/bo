<?php

namespace Drupal\bo\Service;

use Drupal\Core\Entity\EntityTypeManager;

/**
 *
 */
class BoEntity {

  /**
   * @var EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * @param EntityTypeManager $entityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param $langcode
   * @param $to_path
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteAllEntitiesWithPath($langcode, $to_path) {
    $query = \Drupal::entityQuery('bo')
      ->accessCheck(TRUE)
      ->condition('to_path', $to_path)
      ->condition('langcode', $langcode);

    $bo_ids = $query->execute();

    $bo_entities = $this->entityTypeManager->getStorage('bo')->loadMultiple($bo_ids);
    foreach ($bo_entities as $bo_entity) {
      $bo_entity->delete();
    }
  }

}
