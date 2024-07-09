<?php

namespace Drupal\bo\Service;

use Drupal\Core\Entity\EntityTypeManager;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
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

    $this->sortedBundles = [];
    $bundles = $this->getBundles();
    foreach ($bundles as $bundle) {
      $w = $bundle->getWeight();
      // Fix a possible duplicate weight by incrementing until a unique one if founds.
      if (array_key_exists($bundle->getType(), $this->sortedBundles)) {
        if (is_array($this->sortedBundles[$bundle->getType()][$bundle->getGroup()])) {
          while (array_key_exists($w, (array)$this->sortedBundles[$bundle->getType()][$bundle->getGroup()])) {
            $w++;
          }
        }
      }
      $this->sortedBundles[$bundle->getType()][$bundle->getGroup()][$w] = $bundle;
    }

    foreach ($this->getBundleTypes() as $type) {
      foreach ($this->sortedBundles[$type] as &$group_bundles) {
        ksort($group_bundles);
      }
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

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundleTypes() {
    $types_with_bundles = [];
    $all_bundles = $this->getBundles();
    foreach ($all_bundles as $bundle) {
      $type = $bundle->getType();
      if ($type != "" && !in_array($type, $types_with_bundles)) {
        $types_with_bundles[$type] = $type;
      }
    }
    return $types_with_bundles;
  }

}
