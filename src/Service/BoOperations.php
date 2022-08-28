<?php

namespace Drupal\bo\Service;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 *
 */
class BoOperations {

  /**
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   *
   */
  public function __construct(BoSettings $boSettings) {
    $this->boSettings = $boSettings;
  }


  /**
   * @param $view_result_count
   * @param $overview_name
   * @return bool
   *
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   * @See \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function showAddInsertLink($view_result_count, $overview_name) {
    $max_element_count = $this->boSettings->getCollectionOptions($overview_name, "max_element_count");
    if ($max_element_count > 0) {
      if ($view_result_count >= $max_element_count) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param $parameters
   * @param $enabled_bundles
   * @return array|mixed[]
   *
   * @see \Drupal\bo\Plugin\views\area\BoHeader
   * @see \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function getAddInsertEnabledBundlesLinks($parameters, $enabled_bundles) {
    /* default display_id */
    $display_id = $parameters["display_id"];

    /* change display_id if specific view */
    $specific_view = $this->boSettings->getCollectionOptions($parameters["display_id"], "specific_view");
    if ($specific_view == "") {
      $collection_machine_name = $this->boSettings->getCollectionBundleMachineNameViaId($parameters["collection_id"]);
      $specific_view = $this->boSettings->getCollectionOptions($collection_machine_name, "specific_view");
    }
    if ($specific_view != "") {
      $a_specific_view = explode("__", $specific_view);
      $display_id = $a_specific_view[0] . "__" . $a_specific_view[1];
    }
    $parameters["display_id"] = $display_id;

    if (count($enabled_bundles) == 1) {
      $first_and_only_bundle = reset($enabled_bundles);

      $title = $first_and_only_bundle["label"];
      $bundle_name = $first_and_only_bundle["bundle"];

      $parameters["title"] = $title;
      $parameters["bundle_name"] = $bundle_name;

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
  private function getAddInsertLink($parameters, $type) {
    $attributes = [];

    switch ($type) {
      case 'multi':
        $attributes["class"] = [
          'bo-trigger',
          'bo-trigger-multi',
          'bo-trigger-' . $parameters['action'],
        ];

        $url = Url::fromRoute('bo.multi', [
          'action' => $parameters['action'],
          'display_id' => $parameters['display_id'],
          'collection_id' => $parameters['collection_id'],
          'entity_id' => $parameters['entity_id'],
          'entity_weight' => $parameters['entity_weight'],
          'view_dom_id' => $parameters['view_dom_id'],
          'to_path' => $parameters["to_path"],
          'destination' => \Drupal::request()->getRequestUri(),
        ]);

        break;

      case 'single':
        $attributes['class'] = [
          'bo-trigger',
          'bo-trigger-' . $parameters['action'],
        ];

        if ($parameters['action'] == 'add') {
          $url = Url::fromRoute('entity.bo.add_form', [
            'bundle' => $parameters['bundle_name'],
            'to_path' => $parameters["to_path"],
            'collection_id' => $parameters["collection_id"],
            'display_id' => $parameters["display_id"],
            'view_dom_id' => $parameters["view_dom_id"],
            'destination' => \Drupal::request()->getRequestUri(),
          ]);
        }

        if ($parameters['action'] == 'insert') {
          $url = Url::fromRoute('entity.bo.insert_form', [
            'bundle' => $parameters['bundle_name'],
            'to_path' => $parameters["to_path"],
            'collection_id' => $parameters["collection_id"],
            'display_id' => $parameters["display_id"],
            'view_dom_id' => $parameters["view_dom_id"],
            'insert_under_entity_id' => $parameters["entity_id"],
            'insert_under_entity_weight' => $parameters["entity_weight"],
            'destination' => \Drupal::request()->getRequestUri(),
          ]);
        }

        break;

    }

    if ($url) {
      $link = Link::fromTextAndUrl(' ' . $parameters['title'], $url)->toRenderable();
      $link['#attributes'] = $attributes;

      return $link;
    }

    return [];
  }

}
