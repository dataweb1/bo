<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class RefreshViewCommand implements CommandInterface {
  private $view_dom_id;

  /**
   *
   */
  public function __construct($view_dom_id) {
    $this->view_dom_id = $view_dom_id;
  }

  /**
   * Render custom ajax command.
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'refreshViewCommand',
      'view_dom_id' => $this->view_dom_id,
    ];
  }

}
