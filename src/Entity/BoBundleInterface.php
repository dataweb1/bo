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

  /**
   * Gets the BO bundle default.
   *
   * @return string
   *   Default of the BO bundle.
   */
  public function getDefault();

  /**
   * Gets the BO bundle internal title.
   *
   * @return string
   *   Internal title of the BO bundle.
   */
  public function getInternalTitle();

  /**
   * Gets the BO bundle override title label.
   *
   * @return string
   *   Override title label of the BO bundle.
   */
  public function getOverrideTitleLabel();

  /**
   * Gets the BO bundle icon.
   *
   * @return string
   *   Icon of the BO bundle.
   */
  public function getIcon();

  /**
   * Gets the BO bundle collection.
   *
   * @return string
   *   Collection of the BO bundle.
   */
  public function getCollection();

  /**
   * Gets the BO bundle collection enabled.
   *
   * @return string
   *   Collection enabled of the BO bundle.
   */
  public function getCollectionEnabled();

  /**
   * Gets the BO bundle collection bundles.
   *
   * @return string
   *   Collection bundles of the BO bundle.
   */
  public function getCollectionBundles();

  /**
   * Gets the BO collection options options.
   *
   * @return string
   *   Collection options of the BO bundle.
   */
  public function getCollectionOptions();


  /**
   * Gets the BO bundle related bundles.
   *
   * @return string
   *   Related bundles of the BO bundle.
   */
  public function getRelatedBundles();

  /**
   * Sets the BO bundle icon.
   */
  public function setDefault($default);

  /**
   * Sets the BO bundle internal title.
   */
  public function setInternalTitle($internal_title);

  /**
   * Sets the BO bundle override title.
   */
  public function setOverrideTitleLabel($override_title_label);

  /**
   * Sets the BO bundle icon.
   */
  public function setIcon($icon);

  /**
   * Sets the BO bundle collection enabled.
   */
  public function setCollectionEnabled($enabled);

  /**
   * Sets the BO bundle collection bundles.
   */
  public function setCollectionBundles($bundles);

  /**
   * Sets the BO bundle collection options.
   */
  public function setCollectionOptions($options);

  /**
   * Sets the BO bundle related bundles.
   */
  public function setRelatedBundles($related_bundles);

}
