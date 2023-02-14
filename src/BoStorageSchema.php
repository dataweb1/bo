<?php

namespace Drupal\bo;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class BoStorageSchema extends SqlContentEntityStorageSchema {

  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    //then target your annoying field and set the 'not null' key to FALSE!
    if (!empty($schema['nid']['fields']['operator_id']))
      $schema['nid']['fields']['operator_id']['not null'] = FALSE;

    return $schema;
  }
}
