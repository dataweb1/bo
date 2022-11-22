<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class RefreshViewCommand implements CommandInterface {
  private $bo_view_dom_id;

  /**
   *
   */
  public function __construct($bo_view_dom_id) {
    $this->bo_view_dom_id = $bo_view_dom_id;
  }

  /**
   * Render custom ajax command.
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'refreshViewCommand',
      'bo_view_dom_id' => $this->bo_view_dom_id,
    ];
  }

}
