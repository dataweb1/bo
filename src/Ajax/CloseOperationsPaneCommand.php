<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 *
 */
class CloseOperationsPaneCommand implements CommandInterface {
  /**
   * Render custom ajax command.
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'closeOperationsPaneCommand',
    ];
  }

}
