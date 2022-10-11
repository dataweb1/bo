<?php
namespace Drupal\bo\Plugin\views\field;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Field handler to show block fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_entity_operations")
 */
class NodeOperations extends EntityOperations {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values)
  {

    $markup = '';

    $view_dom_id = $this->view->dom_id;

    /** @var Node $entity */
    $entity = $values->_entity;


    $links = [];
    $node_content = parent::render($values);
    if (isset($node_content["#links"]["edit"])) {
      $links[] = $this->getEditLink($node_content["#links"]["edit"]["url"], $entity, $view_dom_id);
    }
    if (isset($node_content["#links"]["delete"])) {
      $links[] = $this->getDeleteLink($node_content["#links"]["delete"]["url"], $entity, $view_dom_id);
    }

    $node_operations = [
      '#theme' => 'node_entity_operations_item_list',
      '#items' => $links,
      '#label' => '',
      '#attached' => [
        'library' => [
          'bo/bo_operations',
        ],
      ],
    ];

    $markup .= $this->renderer->render($node_operations);

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
   * @param $parameters
   * @return array
   */
  public function getEditLink($url, $entity, $view_dom_id) {
    $attributes = [
      'class' => [
        'bo-operation-edit',
      ],
    ];

    $options = [
      "query" => [
        "view_dom_id" => $view_dom_id,
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
