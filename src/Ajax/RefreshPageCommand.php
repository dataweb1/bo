<?php

namespace Drupal\bo\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An Ajax Command that refreshes the current page.
 */
class RefreshPageCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'refreshPageCommand',
    ];
  }

}
