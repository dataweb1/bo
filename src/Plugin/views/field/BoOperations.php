<?php

namespace Drupal\bo\Plugin\views\field;

use Drupal\bo\Entity\BoEntity;
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
   * @var BoOperationsService
   */
  private BoOperationsService $boOperations;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @var BoBundle
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
      $view_dom_id = $this->view->dom_id;
      $view_result_count = count($this->view->result);
      $collection_id = $this->view->filter["bo_current_collection_id_filter"]->value;
      $to_path = $this->view->argument["bo_current_path_argument"]->argument ?? '';

      /** @var BoEntity $entity */
      $entity = $values->_entity;
      $entity_id = $entity->id();
      $entity_weight = $entity->getWeight();

      $parameters = [
        'collection_id' => $collection_id,
        'view_dom_id' => $view_dom_id,
        'to_path' => $to_path,
        'entity_id' => $entity_id,
        'entity_weight' => $entity_weight,
        'action' => 'insert',
      ];

      /* Insert link */
      $links = [];
      if ($this->boOperations->showInsertLink($view_result_count, $collection_id)) {
        $links[] = $this->boOperations->getSingleOrMultiAddInsertLink($parameters);
        $bo_insert_operations = [
          '#theme' => 'bo_insert_operations_item_list',
          '#items' => $links,
          '#label' => '',
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
          $markup .= '<div id="bo_operations_pane_' . $view_dom_id . '_' . $entity_id . '" class="insert-pane bo-operations-pane"></div>';
        }
      }

      /* Edit / Delete links */
      $links = [];
      $bo_content = parent::render($values);
      if (isset($bo_content["#links"]["edit"])) {
        $links[] = $this->getEditLink($bo_content["#links"]["edit"]["url"], $entity, $view_dom_id, $collection_id);
      }
      if (isset($bo_content["#links"]["delete"])) {
        $links[] = $this->getDeleteLink($bo_content["#links"]["delete"]["url"], $entity, $view_dom_id);
      }

      $bundle_label = '';
      /** @var \Drupal\bo\Entity\BoBundle $bundle */
      $bundle = $this->boBundle->getBundle($entity->bundle());
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
        if (!$this->boCollection->getDisableBundleLabel($entity->getCollectionId())) {
          $label = $bundle->label();
          $bundle_label = $this->t($label);
        }
      }
      $bo_entity_operations = [
        '#theme' => 'bo_entity_operations_item_list',
        '#items' => $links,
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
   * @param $parameters
   * @return array
   */
  public function getDeleteLink($url, $entity, $view_dom_id) {

    $options = [
      'query' => [
        'view_dom_id' => $view_dom_id,
      ],
      'attributes' => [
        'class' => [
          'bo-trigger',
          'bo-trigger-delete',
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
   * @param $parameters
   * @return array
   */
  public function getEditLink($url, $entity, $view_dom_id, $collection_id) {
    $attributes = [
      'class' => [
        'bo-trigger',
        'bo-trigger-edit',
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

}
