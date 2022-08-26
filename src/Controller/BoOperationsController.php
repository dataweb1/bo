<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Service\BoSettings;
use Drupal\bo\Ajax\SlideCommand;
use Drupal\bo\Service\BoOperations;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class BoOperationsController extends ControllerBase {

  private BoSettings $boSettings;

  /**
   *
   */
  public function __construct(BoSettings $boSettings) {
    $this->boSettings = $boSettings;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.settings'),
    );
  }

  /**
   *
   */
  public function reorder($display_id, $collection_id, $view_dom_id) {

    $args = [
      'display_id' => $display_id,
      'collection_id' => $collection_id,
      'view_dom_id' => $view_dom_id,
      'to_path' => \Drupal::request()->query->get('to_path'),
    ];

    $form = \Drupal::formBuilder()->getForm('\Drupal\bo\Form\BoReorderForm', $args);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(".bo-operations-pane", ""));
    $response->addCommand(new HtmlCommand('#bo_operations_pane_' . $view_dom_id, $form));
    $response->addCommand(new SlideCommand('reorder', $view_dom_id, 0));

    return $response;
  }

  /**
   *
   */
  public function multi($action, $display_id, $collection_id, $entity_id, $entity_weight, $view_dom_id) {

    $args = [
      'action' => $action,
      'display_id' => $display_id,
      'collection_id' => $collection_id,
      'entity_id' => $entity_id,
      'entity_weight' => $entity_weight,
      'view_dom_id' => $view_dom_id,
      'to_path' => \Drupal::request()->query->get('to_path'),
    ];

    $enabled_bundles = $this->boSettings->getEnabledBundles($args);
    $add_multi = $this->getMultiLinksList($args, $enabled_bundles);

    if (intval($entity_id) == 0) {
      $selector = "#bo_operations_pane_" . $view_dom_id;
    }
    else {
      $selector = "#bo_operations_pane_" . $view_dom_id . "_" . $entity_id;
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(".bo-operations-pane", ""));
    $response->addCommand(new HtmlCommand($selector, $add_multi));
    $response->addCommand(new SlideCommand('add', $view_dom_id, (int) $entity_id));

    return $response;
  }

  /**
   * Get Add/insert Buttons.
   */
  public function getMultiLinksList($parameters, $enabled_bundles): array {
    $buttons = [];

    foreach ($enabled_bundles as $bundle_name => $bundle) {

      $parameters['title'] = $this->t($bundle["label"]);
      $parameters['bundle_name'] = $bundle_name;

      switch ($parameters['action']) {
        case 'add':
          $url = Url::fromRoute('entity.bo.add_form', [
            'bo_bundle' => $parameters['bundle_name'],
            'to_path' => $parameters['to_path'],
            'collection_id' => $parameters['collection_id'],
            'display_id' => $parameters['display_id'],
            'view_dom_id' => $parameters['view_dom_id'],
          ]);
          break;

        case 'insert';
          $url = Url::fromRoute('entity.bo.insert_form', [
            'bo_bundle' => $parameters['bundle_name'],
            'to_path' => $parameters['to_path'],
            'collection_id' => $parameters['collection_id'],
            'display_id' => $parameters['display_id'],
            'view_dom_id' => $parameters['view_dom_id'],
            'insert_under_entity_id' => $parameters["entity_id"],
            'insert_under_entity_weight' => $parameters["entity_weight"],
          ]);
          break;
      }

      $buttons[] = [
        '#title' => [
          "#markup" => '<span class="' . $bundle["icon"] . '"></span>' . $parameters["title"],
        ],
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'bo-add-multi-item-link',
          ],
          'bundle-name' => $bundle_name,
        ],
        '#cache' => [
          "tags" => [
            "bo:settings",
          ],
        ],
      ];
    }

    return [
      '#theme' => 'bo_add_multi_item_list',
      '#title' => [
        "#markup" => '<span class="title-add"><i class="fas fa-plus"></i> ' . $this->t('Add') . '</span>' .
        '<span class="title-insert"><i class="fas fa-plus"></i> ' . $this->t('Insert') . '</span>',
      ],
      '#items' => $buttons,
    ];
  }

}
