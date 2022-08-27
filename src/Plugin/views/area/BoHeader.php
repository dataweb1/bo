<?php

namespace Drupal\bo\Plugin\views\area;

use Drupal\bo\Service\BoOperations;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\bo\Service\BoSettings;
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
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var BoOperations
   */
  private BoOperations $boOperations;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, AccountInterface $current_user, BoSettings $boSettings, BoOperations $boOperations) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->access_manager = $access_manager;
    $this->current_user = $current_user;
    $this->boSettings = $boSettings;
    $this->boOperations = $boOperations;
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
      $container->get('bo.settings'),
      $container->get('bo.operations')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    if (!$empty || !empty($this->options['empty'])) {

      $display_id = $this->view->id() . "__" . $this->view->current_display;
      $view_dom_id = $this->view->dom_id;
      $view_result_count = count($this->view->result);
      $view_sort = $this->view->sort;
      $collection_id = $this->view->filter["bo_current_collection_id_filter"]->value;
      $to_path = $this->view->argument["bo_current_path_argument"]->argument ?? '';

      $collection_machine_name = "";
      if ((int) $collection_id > 0) {
        $collection_machine_name = $this->boSettings->getCollectionBundleMachineNameViaId($collection_id);
      }

      $parameters = [
        'collection_id' => $collection_id,
        'display_id' => $display_id,
        'view_dom_id' => $view_dom_id,
        'to_path' => $to_path,
        'collection_machine_name' => $collection_machine_name,
        'entity_id' => 0,
        'entity_weight' => 0,
        'action' => 'add',
      ];

      $buttons = [];

      // Gear button.
      $administer_bundles = $this->current_user->hasPermission("administer bo elements");

      if ($administer_bundles) {
        $buttons[] = $this->getSettingsLink($parameters);
      }

      // Add button (single or multi) + buttons item list if multi.
      $enabled_bundles = $this->boSettings->getEnabledBundles($parameters);
      $edit_permissions = $this->boSettings->hasEditPermissions($enabled_bundles);
      $create_permissions = $this->boSettings->hasCreatePermissions($enabled_bundles);
      $show_add_button = $this->boOperations->showAddInsertLink($view_result_count, $display_id . '__' . $collection_id);

      if ($create_permissions && $show_add_button == TRUE) {
        $buttons[] = $this->boOperations->getAddInsertEnabledBundlesLinks($parameters, $enabled_bundles);
      }

      // Reorder.
      $show_reorder = FALSE;
      if ($edit_permissions) {
        if ($view_result_count > 1) {
          foreach ($view_sort as $s) {
            if ($s->realField == "weight") {
              $show_reorder = TRUE;
              break;
            }
          }
          if ($show_reorder) {
            $buttons[] = $this->getReorderLink($parameters);
          }
        }
      }

      $bo_header_operations = [
        '#theme' => 'bo_header_operations_item_list',
        '#items' => $buttons,
      ];

      // Render all.
      if ($edit_permissions || $create_permissions) {
        $class = "";

        $html_header = '<div class="bo-header ' . $class . '">';
        $html_header .= \Drupal::service('renderer')->render($bo_header_operations);

        if ($show_reorder || count($enabled_bundles) > 1) {
          $html_header .= '<div id="bo_operations_pane_' . $view_dom_id . '" class="bo-operations-pane"></div>';
        }

        $html_header .= '</div>';

        $markup = Markup::create($html_header);

        return [
          '#attached' => [
            "library" => [
              'bo/bo_operations',
              'bo/bo_ajax_commands',
            ],
          ],
          '#markup' => $markup,
          '#cache' => [
            "tags" => [
              "bo:settings",
            ],
          ],
        ];
      }
    }

    return [];
  }

  /**
   * @param $parameters
   * @return array
   */
  public function getSettingsLink($parameters) {
    $classes = ["bo-trigger", "bo-trigger-gear"];
    $attributes = ["class" => $classes];

    $url = Url::fromRoute('bo.collection_settings_form', [
      'collection_id' => urlencode($parameters["collection_id"]),
      'display_id' => urlencode($parameters["display_id"]),
      'collection_machine_name' => urlencode($parameters["collection_machine_name"]),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => $attributes,
      '#cache' => ["tags" => ["bo:settings"]],
    ];
  }

  /**
   * @param $parameters
   * @return array
   */
  private function getReorderLink($parameters) {

    $attributes = [
      'class' => [
        'bo-trigger',
        'bo-trigger-reorder',
      ],
      'id' => 'bo_trigger_reorder_' . $parameters["view_dom_id"],
    ];

    $url = Url::fromRoute('bo.reorder', [
      'display_id' => $parameters['display_id'],
      'collection_id' => $parameters['collection_id'],
      'view_dom_id' => $parameters['view_dom_id'],
      'to_path' => $parameters['to_path'],
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => $attributes,
      '#cache' => [
        'tags' => [
          'bo:settings',
        ],
      ],
    ];
  }

}
