<?php

namespace Drupal\bo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Provides a 'Node Operations' Block.
 *
 * @Block(
 *   id = "node_operations_block",
 *   admin_label = @Translation("Node Operations block"),
 *   category = @Translation("BO"),
 * )
 */
class NodeOperations extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $links = [];
      // Edit link.
      $edit_permissions = \Drupal::currentUser()->hasPermission('edit ' . $node->bundle() . ' content');
      if (!$edit_permissions) {
        $edit_permissions = \Drupal::currentUser()->hasPermission('edit own ' . $node->bundle() . ' content');
      }
      if ($edit_permissions) {
        $links[] = $this->getEditLink($node);
      }

      // Delete link.
      $delete_permissions = \Drupal::currentUser()->hasPermission('delete ' . $node->bundle() . ' content');
      if (!$delete_permissions) {
        $delete_permissions = \Drupal::currentUser()->hasPermission('delete own ' . $node->bundle() . ' content');
      }
      if ($delete_permissions) {
        $links[] = $this->getDeleteLink($node);
      }

      // Translate link.
      $translate_permissions = \Drupal::currentUser()->hasPermission('create content translations');
      if (!$translate_permissions) {
        $translate_permissions = \Drupal::currentUser()->hasPermission('delete content translations');
      }
      if (!$translate_permissions) {
        $translate_permissions = \Drupal::currentUser()->hasPermission('update content translations');
      }
      if ($translate_permissions && $node->isTranslatable()) {
        $links[] = $this->getTranslateLink($node);
      }

      $label = '';
      if (count($links) > 0) {
        $node_type = NodeType::load($node->bundle());
        // $label = $node_type->label();
        $title = $node->get('title')->value;
        if (strlen($title) > 25) {
          $label .= substr($title, 0, 14) . '…';
        }
        else {
          $label .= $title;
        }
      }

      $attributes = [
        'class' => [
          'node-entity-operations-block',
        ],
      ];

      return [
        '#theme' => 'node_entity_operations_block',
        '#items' => $links,
        '#label' => $label,
        '#attributes' => $attributes,
        '#list_class' => 'bo-operations',
        '#attached' => [
          "library" => [
            'bo/bo_operations',
          ],
        ],
      ];
    }

    return [];
  }

  /**
   *
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   *
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch()
    // you must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @return array
   */
  public function getEditLink(Node $node) {
    $url = Url::fromRoute('entity.node.edit_form', [
      'node' => $node->id(),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    return [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'bo-operation-edit',
        ],
      ],
      '#title' => [
        '#markup' =>  '<span>' . $this->t('Update') . '</span>',
      ],
      '#url' => $url,
    ];
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @return array
   */
  public function getDeleteLink(Node $node) {
    $url = Url::fromRoute('entity.node.delete_form', [
      'node' => $node->id(),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    return [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'bo-operation-delete',
        ],
      ],
      '#title' => [
        '#markup' =>  '<span>' . $this->t('Remove') . '</span>',
      ],
      '#url' => $url,
    ];
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @return array
   */
  public function getTranslateLink(Node $node) {
    $url = Url::fromRoute('entity.node.content_translation_overview', [
      'node' => $node->id(),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    return [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'bo-operation-translate',
        ],
      ],
      '#title' => [
        '#markup' => '<span>' . $this->t('Translate') . '</span>',
      ],
      '#url' => $url,
    ];
  }

}
