<?php

namespace Drupal\bo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'Term Operations' Block.
 *
 * @Block(
 *   id = "term_operations_block",
 *   admin_label = @Translation("Term Operations block"),
 *   category = @Translation("BO"),
 * )
 */
class TermOperations extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var Term $term */
    $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
    if ($term) {
      $links = [];
      // Edit link.
      $edit_permissions = \Drupal::currentUser()->hasPermission('edit terms in ' . $term->bundle());
      if ($edit_permissions) {
        $links[] = $this->getEditLink($term);
      }

      // Delete link.
      $delete_permissions = \Drupal::currentUser()->hasPermission('delete terms in ' . $term->bundle());
      if ($delete_permissions) {
        $links[] = $this->getDeleteLink($term);
      }

      // Translate link.
      $translate_permissions = \Drupal::currentUser()->hasPermission('create content translations');
      if (!$translate_permissions) {
        $translate_permissions = \Drupal::currentUser()->hasPermission('delete content translations');
      }
      if (!$translate_permissions) {
        $translate_permissions = \Drupal::currentUser()->hasPermission('update content translations');
      }
      if ($translate_permissions && $term->isTranslatable()) {
        $links[] = $this->getTranslateLink($term);
      }

      $label = '';
      if (count($links) > 0) {
        $title = $term->get('name')->getString();
        if (strlen($title) > 25) {
          $label .= substr($title, 0, 14) . '…';
        }
        else {
          $label .= $title;
        }
      }

      $attributes = [
        'class' => [
          'term-entity-operations-block',
        ],
      ];

      return [
        '#theme' => 'term_entity_operations_block',
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
    if ($term = \Drupal::routeMatch()->getParameter('taxonomy_term')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $term->id()]);
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
   * @param Term $term
   * @return array
   */
  public function getEditLink(Term $term) {
    $url = Url::fromRoute('entity.taxonomy_term.edit_form', [
      'taxonomy_term' => $term->id(),
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
   * @param Term $term
   * @return array
   */
  public function getDeleteLink(Term $term) {
    $url = Url::fromRoute('entity.taxonomy_term.delete_form', [
      'taxonomy_term' => $term->id(),
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
   * @param Term $term
   * @return array
   */
  public function getTranslateLink(Term $term) {
    $url = Url::fromRoute('entity.taxonomy_term.content_translation_overview', [
      'node' => $term->id(),
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
