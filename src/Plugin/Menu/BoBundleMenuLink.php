<?php

namespace Drupal\bo\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * A menu link that cache by user context.
 */
class BoBundleMenuLink extends MenuLinkDefault {

    public function getCacheMaxAge() {
        return 0;
    }

    public function getCacheContexts() {
        return ['user'];
    }
}