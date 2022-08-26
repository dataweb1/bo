<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class SlideCommand implements CommandInterface {
  private $action;
  private $view_dom_id;
  private $entity_id;

  /**
   *
   */
  public function __construct($action, $view_dom_id, $entity_id) {
    $this->action = $action;
    $this->view_dom_id = $view_dom_id;
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
      'view_dom_id' => $this->view_dom_id,
      'entity_id' => $this->entity_id,
    ];
  }

}
