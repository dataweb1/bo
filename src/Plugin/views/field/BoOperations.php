<?php

namespace Drupal\bo\Plugin\views\field;

use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoOperations as BoOperationsService;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\EntityOperations;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show block fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bo_operations")
 */
class BoOperations extends EntityOperations {

  /**
   * @var \Drupal\bo\Service\BoOperations
   */
  private BoOperationsService $boOperations;

  /**
   * @var \Drupal\bo\Service\BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @var \Drupal\bo\Service\BoBundle
   */
  private BoBundle $boBundle;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityRepositoryInterface $entity_repository, BoOperationsService $boOperations, BoCollection $boCollection, BoBundle $boBundle) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $entity_repository);
    $this->boOperations = $boOperations;
    $this->boCollection = $boCollection;
    $this->boBundle = $boBundle;

    // If for whatever reason the renderer is not loaded.
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      $container->get('bo.operations'),
      $container->get('bo.collection'),
      $container->get('bo.bundle'),
    );
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {

    $markup = '';
    $administer_entities = \Drupal::currentUser()->hasPermission("administer bo entities");
    if ($administer_entities) {
      // Get view filter/argument parameters for link rendering.
      $collection_id = $this->getViewCollectionIdFilter();
      $to_path = $this->getViewToPathArgument();

      // Get current entity for insert/edit/delete link.
      /** @var \Drupal\bo\Entity\BoEntity $entity */
      $current_entity = $values->_entity;

      $operations_size = 'big';
      if ($this->boCollection->getSmallOperations($current_entity->getCollectionId())) {
        $operations_size = 'small';
      }

      /* Insert link. */
      $links = [];
      if ($this->boOperations->showInsertLink(count($this->view->result), $collection_id)) {

        // Get nid for insert link.
        if ($node = \Drupal::routeMatch()->getParameter('node')) {
          $nid = $node->id();
        }
        else {
          $nid = \Drupal::request()->query->get('nid');
        }

        $link_parameters = [
          'collection_id' => $collection_id,
          'view_dom_id' => $this->view->dom_id,
          'nid' => $nid,
          'to_path' => $to_path,
          'entity_id' => $current_entity->id(),
          'entity_weight' => $current_entity->getWeight(),
          'action' => 'insert',
        ];

        $links[] = $this->boOperations->getSingleOrMultiAddInsertLink($link_parameters);

        $attributes = [
          'class' => [
            'bo-operations',
            'bo-insert-operations',
            'operations-size-' . $operations_size,
            'insert-position-' . $this->boCollection->getInsertPosition($current_entity->getCollectionId()),
          ],
        ];

        $bo_insert_operations = [
          '#theme' => 'bo_insert_operations_item_list',
          '#items' => $links,
          '#label' => '',
          '#attributes' => $attributes,
          '#attached' => [
            'library' => [
              'bo/bo_operations',
              'bo/bo_ajax_commands',
            ],
          ],
          '#cache' => [
            "tags" => [
              "bo:collection:" . $collection_id,
            ],
          ],
        ];

        $markup .= $this->renderer->render($bo_insert_operations);

        if (count($this->boCollection->getEnabledBundles($collection_id)) > 1) {
          $markup .= '<div id="bo_operations_pane_' . $this->view->dom_id . '_' . $current_entity->id() . '" class="insert-pane bo-operations-pane"></div>';
        }
      }

      /* Edit / Delete links */
      $links = [];
      $bo_content = parent::render($values);
      if (isset($bo_content["#links"]["edit"])) {
        $links[] = $this->getEditLink($bo_content["#links"]["edit"]["url"], $current_entity, $this->view->dom_id, $collection_id);
      }
      if (isset($bo_content["#links"]["delete"])) {
        $links[] = $this->getDeleteLink($bo_content["#links"]["delete"]["url"], $current_entity, $this->view->dom_id);
      }

      $bundle_label = '';
      /** @var \Drupal\bo\Entity\BoBundle $bundle */
      if ($bundle = $this->boBundle->getBundle($current_entity->bundle())) {
        // If current entity is not a collection.
        if (!$bundle->getCollectionEnabled()) {
          // If the collection of the current entity has
          // bundle label not disabled.
          if (!$this->boCollection->getDisableBundleLabel($collection_id)) {
            $label = $bundle->label();
            $bundle_label = $this->t($label);
          }
        }
        else {
          if (!$this->boCollection->getDisableBundleLabel($current_entity->getCollectionId())) {
            $label = $bundle->label();
            $bundle_label = $this->t($label);
          }
        }
      }

      $attributes = [
        'class' => [
          'bo-operations',
          'bo-entity-operations',
          'operations-size-' . $operations_size,
          'header-operations-position-' . $this->boCollection->getOperationsPosition($current_entity->getCollectionId()),
        ],
      ];

      $bo_entity_operations = [
        '#theme' => 'bo_entity_operations_item_list',
        '#items' => $links,
        '#attributes' => $attributes,
        '#label' => $bundle_label,
        '#attached' => [
          'library' => [
            'bo/bo_operations',
          ],
        ],
        '#cache' => [
          "tags" => [
            "bo:collection:" . $collection_id,
          ],
        ],
      ];

      $markup .= $this->renderer->render($bo_entity_operations);
    }

    return [
      '#markup' => $markup,
    ];
  }

  /**
   * @param $url
   * @param $entity
   * @param $view_dom_id
   * @return array
   */
  private function getDeleteLink($url, $entity, $view_dom_id) {

    $options = [
      'query' => [
        'view_dom_id' => $view_dom_id,
      ],
      'attributes' => [
        'class' => [
          'bo-operation-delete',
        ],
      ],
    ];

    $url->setOptions($options);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#cache' => $entity->getCacheTags(),
    ];
  }

  /**
   * @param $url
   * @param $entity
   * @param $view_dom_id
   * @param $collection_id
   * @return array
   */
  private function getEditLink($url, $entity, $view_dom_id, $collection_id) {
    $attributes = [
      'class' => [
        'bo-operation-edit',
      ],
    ];

    $options = [
      "query" => [
        "view_dom_id" => $view_dom_id,
        'collection_id' => $collection_id,
        "destination" => \Drupal::request()->getRequestUri(),
      ],
    ];

    $url->setOptions($options);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => $attributes,
      '#cache' => $entity->getCacheTags(),
    ];
  }

  /**
   * @return string
   */
  private function getViewCollectionIdFilter() {
    return $this->view->filter["bo_current_collection_id_filter"]->value;
  }

  /**
   * @return string
   */
  private function getViewToPathArgument() {
    return $this->view->argument["bo_current_path_argument"]->argument ?? '';
  }

}
