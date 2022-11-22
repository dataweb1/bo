<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class SlideCommand implements CommandInterface {
  private $action;
  private $bo_view_dom_id;
  private $entity_id;

  /**
   *
   */
  public function __construct($action, $bo_view_dom_id, $entity_id) {
    $this->action = $action;
    $this->bo_view_dom_id = $bo_view_dom_id;
    $this->entity_id = $entity_id;
  }

  /**
   * Render custom ajax command.
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'slideCommand',
      'action' => $this->action,
      'bo_view_dom_id' => $this->bo_view_dom_id,
      'entity_id' => $this->entity_id,
    ];
  }

}
