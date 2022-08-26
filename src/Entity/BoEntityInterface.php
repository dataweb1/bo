<?php

namespace Drupal\bo\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining BO bundle entities.
 *
 * @ingroup bo
 */
interface BoEntityInterface extends EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the BO bundle name.
   *
   * @return string
   *   Name of the BO bundle.
   */
  public function getTitle();

  /**
   * Sets the BO bundle name.
   *
   * @param string $name
   *   The BO bundle name.
   *
   * @return \Drupal\bo\Entity\BoEntityInterface
   *   The called BO bundle entity.
   */
  public function setTitle($title);

  /**
   * Gets the BO bundle creation timestamp.
   *
   * @return int
   *   Creation timestamp of the BO bundle.
   */
  public function getCreatedTime();

  /**
   * Sets the BO bundle creation timestamp.
   *
   * @param int $timestamp
   *   The BO bundle creation timestamp.
   *
   * @return \Drupal\bo\Entity\BoEntityInterface
   *   The called BO bundle entity.
   */
  public function setCreatedTime($timestamp);

}
