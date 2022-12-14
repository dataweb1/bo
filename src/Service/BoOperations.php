<?php

namespace Drupal\bo\Service;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 *
 */
class BoOperations {

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @param BoSettings $boSettings
   */
  public function __construct(BoCollection $boCollection) {
    $this->boCollection = $boCollection;
  }

  /**
   * @param $view_result_count
   * @param $overview_name
   * @return bool
   *
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   * @See \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function showAddLink($view_result_count, $collection_id) {
    if ($collection_id == '') {
      return FALSE;
    }

    $create_permissions = $this->boCollection->hasCreateBundlePermissionsForCollection($collection_id);
    if (!$create_permissions) {
      return FALSE;
    }

    $max_element_count = $this->boCollection->getCollectionMaxElementCount($collection_id);

    if ($max_element_count > 0) {
      if ($view_result_count >= $max_element_count) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param $view_result_count
   * @param $overview_name
   * @return bool
   *
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   * @See \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function showInsertLink($view_result_count, $collection_id) {
    if ($collection_id == '') {
      return FALSE;
    }

    if ($this->boCollection->getDisableInsert($collection_id)) {
      return FALSE;
    }

    $create_permissions = $this->boCollection->hasCreateBundlePermissionsForCollection($collection_id);
    if (!$create_permissions) {
      return FALSE;
    }

    $max_element_count = $this->boCollection->getCollectionMaxElementCount($collection_id);

    if ($max_element_count > 0) {
      if ($view_result_count >= $max_element_count) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param $parameters
   * @return array|mixed[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSingleOrMultiAddInsertLink($parameters) {
    /** @var \Drupal\bo\Entity\BoBundle[] $enabled_bundles */
    $enabled_bundles = $this->boCollection->getEnabledBundles($parameters['collection_id']);
    if (count($enabled_bundles) == 1) {
      $first_and_only_bundle = reset($enabled_bundles);
      $parameters["title"] = $first_and_only_bundle->label();
      $parameters["bundle"] = $first_and_only_bundle->id();

      $button = $this->getAddInsertLink($parameters, "single");
    }
    else {
      $parameters["title"] = "";
      $button = $this->getAddInsertLink($parameters, "multi");
    }

    return $button;
  }

  /**
   * @param $parameters
   * @param $type
   * @return array|mixed[]
   */
  public function getAddInsertLink($parameters, $single_or_multi) {
    $attributes = [];

    $url = NULL;

    switch ($single_or_multi) {
      case 'multi':
        $attributes["class"] = [
          'bo-operation-multi',
          'bo-operation-' . $parameters['action'],
          'use-ajax',
        ];

        $url = Url::fromRoute('bo.multi', [
          'action' => $parameters['action'],
          'collection_id' => $parameters['collection_id'],
          'entity_id' => $parameters['entity_id'],
          'entity_weight' => $parameters['entity_weight'],
          'bo_view_dom_id' => $parameters['bo_view_dom_id'],
          'nid' => $parameters['nid'],
          'to_path' => $parameters["to_path"],
          'destination' => \Drupal::request()->getRequestUri(),
        ]);

        break;

      case 'single':
        $attributes['class'] = [
          'bo-operation-single',
          'bo-operation-' . $parameters['action'],
        ];

        if ($parameters['action'] == 'add') {
          $url = Url::fromRoute('entity.bo.add_form', [
            'bundle' => $parameters['bundle'],
            'nid' => $parameters['nid'],
            'to_path' => $parameters["to_path"],
            'collection_id' => $parameters["collection_id"],
            'bo_view_dom_id' => $parameters["bo_view_dom_id"],
            'destination' => \Drupal::request()->getRequestUri(),
          ]);
        }

        if ($parameters['action'] == 'insert') {
          $url = Url::fromRoute('entity.bo.insert_form', [
            'bundle' => $parameters['bundle'],
            'nid' => $parameters['nid'],
            'to_path' => $parameters["to_path"],
            'collection_id' => $parameters["collection_id"],
            'bo_view_dom_id' => $parameters["bo_view_dom_id"],
            'insert_under_entity_id' => $parameters["entity_id"],
            'insert_under_entity_weight' => $parameters["entity_weight"],
            'destination' => \Drupal::request()->getRequestUri(),
          ]);
        }

        break;
    }

    if ($url !== NULL) {
      $link = [
        '#type' => 'link',
        '#title' => [
          '#markup' => '<span>' . $parameters['title'] . '</span>',
        ],
        '#url' => $url,
      ];

      if ($parameters['title'] == '') {
        $link['#title']['#markup'] = '';
      }

      $link['#attributes'] = $attributes;

      return $link;
    }

    return [];
  }

}
