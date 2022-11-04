<?php

namespace Drupal\bo\Plugin\views\area;

use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoOperations;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a views area plugin.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("bo_header")
 */
class BoHeader extends AreaPluginBase {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $access_manager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * @var BoOperations
   */
  private BoOperations $boOperations;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   * Constructs a new BoHeader.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, AccountInterface $current_user, BoOperations $boOperations, BoCollection  $boCollection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->access_manager = $access_manager;
    $this->current_user = $current_user;
    $this->boOperations = $boOperations;
    $this->boCollection = $boCollection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('current_user'),
      $container->get('bo.operations'),
      $container->get('bo.collection'),
    );
  }


  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    if (!$empty || !empty($this->options['empty'])) {

      // Get view filter/argument parameters for link rendering.
      $collection_id = $this->getViewCollectionIdFilter();
      $to_path = $this->getViewToPathArgument();

      // Start adding the links according to the permissions and the collection settings.
      $links = [];

      // Collection settings link.
      $administer_bundles = $this->current_user->hasPermission("administer bo bundles");
      if ($administer_bundles) {
        $links[] = $this->getSettingsLink($collection_id);
      }

      $administer_entities = $this->current_user->hasPermission("administer bo entities");
      if ($administer_entities) {
        // Get nid for add link.
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
          'entity_id' => 0,
          'entity_weight' => 0,
          'action' => 'add',
        ];

        // Add single or multi link.
        if ($this->boOperations->showAddLink(count($this->view->result), $collection_id)) {
          $links[] = $this->boOperations->getSingleOrMultiAddInsertLink($link_parameters);
        }

        // Reorder link.
        $show_reorder = FALSE;
        if ($this->boCollection->hasEditBundlePermissionsForCollection($collection_id)) {
          if (count($this->view->result) > 1) {
            foreach ($this->view->sort as $s) {
              if ($s->realField == "weight") {
                $show_reorder = TRUE;
                break;
              }
            }
            if ($show_reorder) {
              $links[] = $this->getReorderLink($link_parameters);
            }
          }
        }
      }

      // Render all.
      $html_header = '';
      if (count($links) > 0) {

        $operations_size = 'big';
        if ($this->boCollection->getSmallOperations($collection_id)) {
          $operations_size = 'small';
        }

        $attributes = [
          'class' => [
            'bo-header-operations',
            'bo-operations',
            'bo-' . $operations_size . '-operations',
          ],
        ];

        $bo_header_operations = [
          '#theme' => 'bo_header_operations_item_list',
          '#items' => $links,
          '#attributes' => $attributes,
          '#label' => $this->boCollection->getCollectionLabel($collection_id),
        ];

        $class = '';
        if ($this->boCollection->getHeaderOperationsOverlap($collection_id)) {
          $wrapper_class = 'with-overlap';
          $class = 'bo-operations-overlap bo-operations-position-' . $this->boCollection->getOperationsPosition($collection_id);
        }

        $html_header = '<div class="bo-header-wrapper ' . $wrapper_class . '">';
        $html_header .= '<div class="bo-header ' . $class . '">';
        $html_header .= \Drupal::service('renderer')->render($bo_header_operations);
        $html_header .= '</div>';

        $enabled_bundles = $this->boCollection->getEnabledBundles($collection_id);
        if ($show_reorder || count($enabled_bundles) > 1) {
          $html_header .= '<div id="bo_operations_pane_' . $this->view->dom_id . '" class="bo-operations-pane"></div>';
        }
        $html_header .= '</div>';

      }

      return [
        '#attached' => [
          "library" => [
            'bo/bo_operations',
            'bo/bo_ajax_commands',
          ],
        ],
        '#markup' => Markup::create($html_header),
        '#cache' => [
          "tags" => [
            "bo:collection:" . $collection_id,
          ],
        ],
      ];
    }


    return [];
  }

  /**
   * @param $collection_id
   * @return array
   */
  private function getSettingsLink($collection_id) {
    $collection_label = $this->boCollection->getCollectionLabel($collection_id);
    if ($collection_label != '') {
      $title = $this->t("BO collection settings for '@collection_label'", ['@collection_label' => $collection_label]);
    }
    else {
      $title = $this->t('BO collection settings');
    }

    $url = Url::fromRoute('bo.collection_settings_form', [
      'via' => 'view',
      'collection_id' => $collection_id,
      'title' => $title,
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => [
        'class' => [
          'bo-operation-gear',
        ],
      ],
      '#cache' => [
        'tags' => [
          'bo:collection:' . $collection_id,
        ],
      ],
    ];
  }

  /**
   * @param $link_parameters
   * @return array
   */
  private function getReorderLink($link_parameters) {

    $url = Url::fromRoute('bo.reorder', [
      'collection_id' => $link_parameters['collection_id'],
      'view_dom_id' => $link_parameters['view_dom_id'],
      'to_path' => $link_parameters['to_path'],
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => [
        'class' => [
          'bo-operation-reorder',
          'use-ajax',
        ],
        'id' => 'bo_trigger_reorder_' . $link_parameters["view_dom_id"],
      ],
      '#cache' => [
        'tags' => [
          'bo:collection:' . $link_parameters['collection_id'],
        ],
      ],
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
