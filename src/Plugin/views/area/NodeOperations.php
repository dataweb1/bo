<?php

namespace Drupal\bo\Plugin\views\area;

use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoOperations;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
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
 * @ViewsArea("node_area_operations")
 */
class NodeOperations extends AreaPluginBase {

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
    dpm('area node operations');
    if (!$empty || !empty($this->options['empty'])) {
      if (isset($this->view->filter["type"])) {
        $links = [];
        foreach ($this->view->filter['type']->value as $node_type_id) {

          $add_permissions = \Drupal::currentUser()->hasPermission('create ' . $node_type_id . ' content');
          if ($add_permissions) {
            $node_type = NodeType::load($node_type_id);
            $links[] = $this->getAddLink($node_type);
          }
        }

        // Render all.
        $html = '';
        if (count($links) > 0) {
          $attributes = [
            'class' => [
              'node-area-operations',
              'bo-operations',
            ],
          ];

          $node_area_operations = [
            '#theme' => 'node_area_operations_item_list',
            '#items' => $links,
            '#attributes' => $attributes,
            '#label' => '',
          ];
          $html = \Drupal::service('renderer')->render($node_area_operations);
        }
        else {
          return [];
        }

        return [
          '#attached' => [
            "library" => [
              'bo/bo_operations',
              'bo/bo_ajax_commands',
            ],
          ],
          '#markup' => Markup::create($html),
        ];
      }
    }

    return [];
  }

  /**
   * @param NodeType $node_type
   * @return array
   */
  public function getAddLink(NodeType $node_type) {
    $url = Url::fromRoute('node.add', [
      'node_type' => $node_type->id(),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    return [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'bo-operation-add',
        ],
      ],
      '#title' => [
        '#markup' => '<span>' . $node_type->label() . '</span>',
      ],
      '#url' => $url,
    ];
  }

}
