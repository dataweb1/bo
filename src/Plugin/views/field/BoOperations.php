<?php

namespace Drupal\bo\Plugin\views\field;

use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoSettings;
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
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityRepositoryInterface $entity_repository, BoOperationsService $boOperations, BoCollection $boCollection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $entity_repository);
    $this->boOperations = $boOperations;
    $this->boCollection = $boCollection;

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

    $view_dom_id = $this->view->dom_id;
    $view_result_count = count($this->view->result);
    $collection_id = $this->view->filter["bo_current_collection_id_filter"]->value;
    $to_path = $this->view->argument["bo_current_path_argument"]->argument ?? '';

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
    if ($this->boOperations->showAddInsertLink($view_result_count, $collection_id)) {
      $links[] = $this->boOperations->getSingleOrMultiAddInsertLink($parameters);
      $bo_entity_operations = [
        '#theme' => 'bo_entity_operations_item_list',
        '#items' => $links,
        '#attached' => [
          'library' => [
            'bo/bo_operations',
            'bo/bo_ajax_commands',
          ],
        ],
        '#cache' => [
          "tags" => [
            "bo:settings",
            "bo:order",
          ],
        ],
      ];

      $markup .= $this->renderer->render($bo_entity_operations);

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

    $bo_content_operations = [
      '#theme' => 'bo_content_operations_item_list',
      '#items' => $links,
      '#attached' => [
        'library' => [
          'bo/bo_operations',
        ],
      ],
    ];

    $markup .= $this->renderer->render($bo_content_operations);

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
