<?php

namespace Drupal\bo\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Database\Connection;

/**
 *
 */
class BoBundle {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * @var array
   */
  private array $sortedBundles;

  /**
   * @param BoSettings $boSettings
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @return \Drupal\bo\Entity\BoBundle[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundles() {
    return $this->entityTypeManager->getStorage('bundle')->loadByProperties();
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSortedBundles() {

    if (!empty($this->sortedBundles)) {
      return $this->sortedBundles;
    }

    $bundles = $this->getBundles();
    foreach ($bundles as $bundle) {
      $this->sortedBundles[$bundle->getGroup()][$bundle->getWeight()] = $bundle;
    }

    foreach ($this->sortedBundles as &$group_bundles) {
      ksort($group_bundles);
    }

    return $this->sortedBundles;
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundleGroups($type = '') {
    $groups_with_bundles = [];
    $all_bundles = $this->getBundles();
    foreach ($all_bundles as $bundle) {
      if ($type == '' || $bundle->getType() == $type) {
        $group = $bundle->getGroup();

        if ($group != "" && !in_array($group, $groups_with_bundles)) {
          $groups_with_bundles[$group] = $group;
        }
      }
    }
    return $groups_with_bundles;
  }

  /**
   *
   */
  public function getBundleCollectionOptions(\Drupal\bo\Entity\BoBundle $bundle, $option = "") {
    $collection = $bundle->getCollection();
    if (!isset($collection["options"])) {
      return "";
    }

    if ($option != "") {
      return $collection["options"][$option] ?? "";
    }
    else {
      return $collection["options"] ?? [];
    }
  }

  /**
   * @param $id
   * @return \Drupal\bo\Entity\BoBundle|false
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundle($id) {
    $bundles = $this->entityTypeManager->getStorage('bundle')->loadByProperties(['id' => $id]);
    if (isset($bundles[$id])) {
      return $bundles[$id];
    }
    return FALSE;
  }

}
