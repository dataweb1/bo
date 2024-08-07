<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Ajax\SlideCommand;
use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class BoOperationsController extends ControllerBase {

  /**
   * @var \Drupal\bo\Service\BoBundle
   */
  private BoBundle $boBundle;

  /**
   * @var \Drupal\bo\Service\BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @param \Drupal\bo\Service\BoBundle $boBundle
   * @param \Drupal\bo\Service\BoCollection $boCollection
   */
  public function __construct(BoBundle $boBundle, BoCollection $boCollection) {
    $this->boBundle = $boBundle;
    $this->boCollection = $boCollection;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \Drupal\bo\Service\BoOperationsController|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.bundle'),
      $container->get('bo.collection')
    );
  }

  /**
   * @param $collection_id
   * @param $bo_view_dom_id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function reorder($collection_id, $bo_view_dom_id) {

    $args = [
      'collection_id' => $collection_id,
      'bo_view_dom_id' => $bo_view_dom_id,
      'to_path' => \Drupal::request()->query->get('to_path'),
    ];

    $form = \Drupal::formBuilder()->getForm('\Drupal\bo\Form\BoReorderForm', $args);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(".bo-operations-pane", ""));
    $response->addCommand(new HtmlCommand('#bo_operations_pane_' . $bo_view_dom_id, $form));
    $response->addCommand(new SlideCommand('reorder', $bo_view_dom_id, 0));

    return $response;
  }

  /**
   *
   */
  public function multi($action, $collection_id, $entity_id, $entity_weight, $bo_view_dom_id) {

    $args = [
      'action' => $action,
      'collection_id' => $collection_id,
      'entity_id' => $entity_id,
      'entity_weight' => $entity_weight,
      'bo_view_dom_id' => $bo_view_dom_id,
      'to_path' => \Drupal::request()->query->get('to_path'),
      'nid' => \Drupal::request()->query->get('nid'),
    ];

    $add_multi = $this->getMultiLinksList($args);

    if (intval($entity_id) == 0) {
      $selector = "#bo_operations_pane_" . $bo_view_dom_id;
    }
    else {
      $selector = "#bo_operations_pane_" . $bo_view_dom_id . "_" . $entity_id;
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(".bo-operations-pane", ""));
    $response->addCommand(new HtmlCommand($selector, $add_multi));
    $response->addCommand(new SlideCommand('add', $bo_view_dom_id, (int) $entity_id));

    return $response;
  }

  /**
   * Get Add/insert Buttons.
   */
  public function getMultiLinksList($args): array {
    $buttons = [];
    $enabled_bundles = $this->boCollection->getEnabledBundles($args['collection_id']);
    /** @var \Drupal\bo\Entity\BoBundle $bundle  */
    foreach ($enabled_bundles as $bundle) {
      switch ($args['action']) {
        case 'add':
          $url = Url::fromRoute('entity.bo.add_form', [
            'bundle' => $bundle->id(),
            'nid' => $args['nid'],
            'to_path' => $args['to_path'],
            'collection_id' => $args['collection_id'],
            'bo_view_dom_id' => $args['bo_view_dom_id'],
          ]);
          break;

        case 'insert';
          $url = Url::fromRoute('entity.bo.insert_form', [
            'bundle' => $bundle->id(),
            'nid' => $args['nid'],
            'to_path' => $args['to_path'],
            'collection_id' => $args['collection_id'],
            'bo_view_dom_id' => $args['bo_view_dom_id'],
            'insert_under_entity_id' => $args["entity_id"],
            'insert_under_entity_weight' => $args["entity_weight"],
          ]);
          break;
      }

      $buttons[] = [
        '#title' => [
          "#markup" => '<span class="' . $bundle->getIcon() . '"></span>' . $this->t($bundle->label()),
        ],
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => [
          'class' => [
            'bo-add-multi-item-link',
          ],
        ],
        '#cache' => [
          "tags" => [
            "bo:collection:" . $args['collection_id'],
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
