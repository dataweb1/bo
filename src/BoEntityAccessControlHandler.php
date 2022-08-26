<?php

namespace Drupal\bo;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class BoEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    /** @var \Drupal\bo\Entity\BoEntityInterface $entity */
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, "view any $entity_type_id $bundle");

      case 'update':
        return AccessResult::allowedIfHasPermission($account, "edit any $entity_type_id $bundle");

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, "delete any $entity_type_id $bundle");
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, "create {$context['entity_type_id']} $entity_bundle");
  }

}
