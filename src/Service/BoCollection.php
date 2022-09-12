<?php

namespace Drupal\bo\Service;

use Drupal\block\Entity\Block;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Database\Connection;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 *
 */
class BoCollection {

  /**
   * @var array
   */
  private array $collectionViews;

  /**
   * @var AccountProxy
   */
  private AccountProxy $currentUser;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var BoBundle
   */
  private BoBundle $boBundle;

  /**
   * @param Connection $connection
   * @param AccountProxy $currentUser
   * @param BoSettings $boSettings
   * @param BoBundle $boBundle
   */
  public function __construct(Connection $connection, AccountProxy $currentUser, BoSettings $boSettings, BoBundle $boBundle) {
    $this->connection = $connection;
    $this->currentUser = $currentUser;
    $this->boSettings = $boSettings;
    $this->boBundle = $boBundle;
  }

  /**
   * @param $view_id
   * @param $display_id
   * @param $collection_id
   * @param string $to_path
   * @return \Drupal\views\ViewExecutable|null
   */
  public function prepareCollectionView($collection_id, string $to_path = ''): ?ViewExecutable {

    if ([$view_id, $display_id] = $this->getCollectionView($collection_id)) {

      /** @var \Drupal\views\ViewExecutable $view */
      $view = Views::getView($view_id);
      $view->setDisplay($display_id);
      $view->preExecute();
      $argument = $view->argument;
      $filter = $view->filter;

      $view = Views::getView($view_id);
      $view->setDisplay($display_id);

      $view->filter = $filter;

      // Set the current collection id filter if defined in the view.
      if (array_key_exists('bo_current_collection_id_filter', $filter)) {
        $view->filter["bo_current_collection_id_filter"]->value = $collection_id;
      }

      // Set current path argument if defined in the view.
      if ($to_path != '') {
        if (array_key_exists('bo_current_path_argument', $argument)) {
          $view->args[array_search("bo_current_path_argument", array_keys($argument))] = $to_path;
        }
      }

      $view->execute();

      return $view;
    }

    return null;
  }

  /**
   * @return array
   */
  public function getCollectionViews() {
    if (!empty($this->collectionViews)) {
      return $this->collectionViews;
    }

    $views = Views::getAllViews();
    foreach ($views as $view_key => $view) {
      if ($view->get("base_table") == "bo") {
        $displays = $view->get("display");
        foreach ($displays as $display_key => $display) {
          $this->collectionViews[$view_key][] = [
            "view_label" => $view->get("label"),
            "display_id" => $display_key,
            "display_title" => $display["display_title"],
          ];
        }
      }
    }
    return $this->collectionViews;
  }

  /**
   * @param $collection_id
   * @return mixed|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionLabel($collection_id) {
    // If collection settings overridden on view level.
    $collection_label = $this->boSettings->getCollectionOptions($collection_id)['label'] ?? '';
    if ($collection_label == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $collection_label = $collection_bundle->getCollectionOptions()['label'];
      }
    }
    return $collection_label;
  }

  /**
   * @param $collection_id
   * @return mixed|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionMaxElementCount($collection_id) {
    // If collection settings overridden on view level.
    $max_element_count = $this->boSettings->getCollectionOptions($collection_id)['max_element_count'] ?? '';
    if ($max_element_count == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $max_element_count = $collection_bundle->getCollectionOptions()['max_element_count'];
      }
    }
    return $max_element_count;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionReload($collection_id) {
    // If collection settings overridden on view level.
    $reload = $this->boSettings->getCollectionOptions($collection_id)['reload'] ?? '';
    if ($reload == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $reload = $collection_bundle->getCollectionOptions()['reload'];
      }
    }
    return (bool)$reload;
  }

  /**
   * @param $collection_id
   * @return false|string[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionView($collection_id) {
    $collection_view = NULL;

    // If collection settings overriden on view level.
    if ($this->boSettings->getCollection($collection_id)) {
      $collection_view = $this->boSettings->getCollectionOptions($collection_id)['specific_view'];
    }
    else {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $collection_view = $collection_bundle->getCollectionOptions()['specific_view'];
      }
    }

    if ($collection_view !== NULL) {
      if ($collection_id !== NULL) {
        $block = Block::load($collection_id);
        [$type, $view] = explode(':', $block->getPluginId());
        return explode('-', $view);
      } else {
        return explode('__', $collection_view);
      }
    }
    return FALSE;
  }

  /**
   * @param $collection_id
   * @return \Drupal\bo\Entity\BoBundle|false
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionBundle($collection_id) {
    $select = $this->connection->select('bo', 'b');
    $select->fields('b');
    $select->condition('id', $collection_id, "=");
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (isset($entries[0])) {
      return $this->boBundle->getBundle($entries[0]["bundle"]);
    }

    return FALSE;
  }

  /**
   * @param $collection_id
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEnabledBundles($collection_id) {
    $enabled_bundles = [];
    foreach ($this->boBundle->getSortedBundles() as $grouped_bundles) {
      /** @var \Drupal\bo\Entity\BoBundle $bundle */
      foreach ($grouped_bundles as $bundle) {
        if ($this->isEnabledBundle($collection_id, $bundle)) {
          $enabled_bundles[] = $bundle;
        }
      }
    }

    return $enabled_bundles;
  }

  /**
   * @param $collection_id
   * @param \Drupal\bo\Entity\BoBundle $bundle
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isEnabledBundle($collection_id, \Drupal\bo\Entity\BoBundle $bundle_to_check): bool {
    // No create permisson, disabled right away.
    if (!$this->currentUser->hasPermission("create bo " . $bundle_to_check->id())) {
      return FALSE;
    }

    // Coming via a view (add / insert / collection settings). xxx
    if ($collection = $this->boSettings->getCollectionBundles($collection_id)) {
      return $collection['bundles'][$bundle_to_check->id()] ?? FALSE;
    }
    else {
      // Coming via een collection in a view (add / insert / collection settings).
      if ((int) $collection_id > 0) {
        $collection_bundle = $this->getCollectionBundle($collection_id);
        return $collection_bundle->getCollectionBundles()[$bundle_to_check->id()] ?? FALSE;
      }
      else {
        // Coming via bundle collection settings.
        if ($collection_bundle = $this->boBundle->getBundle($collection_id)) {
          return $collection_bundle->getCollectionBundles()[$bundle_to_check->id()] ?? FALSE;
        }
        else {
          // If nowhere set take default parameter of the bundle.
          return (bool) $bundle_to_check->getDefault();
        }
      }
    }
  }

  /**
   * @param $collection_id
   * @return bool
   *
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   */
  public function hasEditBundlePermissionsForCollection($collection_id) {
    $enabled_bundles = $this->getEnabledBundles($collection_id);
    /** @var \Drupal\bo\Entity\BoBundle $bundle */
    foreach ($enabled_bundles as $bundle) {
      $bundle_edit_perms = $this->currentUser->hasPermission("edit any bo " . $bundle->id());
      if ($bundle_edit_perms) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param $collection_id
   * @return bool
   *
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   * @See \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function hasCreateBundlePermissionsForCollection($collection_id) {
    $enabled_bundles = $this->getEnabledBundles($collection_id);
    /** @var \Drupal\bo\Entity\BoBundle $bundle */
    foreach ($enabled_bundles as $bundle) {
      $bundle_create_perms = $this->currentUser->hasPermission("create bo " . $bundle->id());
      if ($bundle_create_perms) {
        return TRUE;
      }
    }
    return FALSE;
  }

}