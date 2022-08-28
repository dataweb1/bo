<?php

namespace Drupal\bo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface for defining BO bundle type entities.
 */
interface BoBundleInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the BO bundle group.
   *
   * @return string
   *   Group of the BO bundle.
   */
  public function getGroup();

  /**
   * Sets the BO bundle group.
   */
  public function setGroup($group);

}
