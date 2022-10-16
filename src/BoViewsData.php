<?php

namespace Drupal\bo;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the BO entity type.
 */
class BoViewsData extends EntityViewsData
{

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['node_field_data']['nid']['relationship'] = [
      'title' => $this->t('BO Entities'),
      'help' => $this->t('Relate content to the BO entities that where added to the node. This relationship will create one record for each BO entity that was added to the node.'),
      'id' => 'standard',
      'base' => 'bo',
      'base field' => 'nid',
      'field' => 'nid',
      'label' => $this->t('BO Entities'),
    ];

    return $data;
  }

}
