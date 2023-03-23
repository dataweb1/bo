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
  private array $boViews = [];

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
  public function getBoViews() {
    if (!empty($this->boViews)) {
      return $this->boViews;
    }

    $views = Views::getAllViews();
    foreach ($views as $view_key => $view) {
      if ($view->get("base_table") == "bo") {
        $displays = $view->get("display");
        foreach ($displays as $display_key => $display) {
          $this->boViews[$view_key][] = [
            "view_label" => $view->get("label"),
            "display_id" => $display_key,
            "display_title" => $display["display_title"],
          ];
        }
      }
    }
    return $this->boViews;
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
  public function getHeaderOperationsOverlap($collection_id) {
    // If collection settings overridden on view level.
    $collection_header_operations_overlap = $this->boSettings->getCollectionOptions($collection_id)['header_operations_overlap'] ?? '';
    if ($collection_header_operations_overlap == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $collection_header_operations_overlap = $collection_bundle->getCollectionOptions()['header_operations_overlap'];
      }
    }
    return (bool) $collection_header_operations_overlap;
  }


  /**
   * @param $collection_id
   * @return mixed|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOperationsPosition($collection_id) {
    // If collection settings overridden on view level.
    $operations_position = $this->boSettings->getCollectionOptions($collection_id)['operations_position'] ?? '';
    if ($operations_position == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $operations_position = $collection_bundle->getCollectionOptions()['operations_position'];
      }
    }

    if ($operations_position == '') {
      $operations_position = 'top';
    }
    return $operations_position;
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
    if ((string) $max_element_count == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $max_element_count = $collection_bundle->getCollectionOptions()['max_element_count'];
      }
    }
    return (int) $max_element_count;
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
    if ((string) $reload == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $reload = $collection_bundle->getCollectionOptions()['reload'];
      }
    }
    return (bool) $reload;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDisableInsert($collection_id) {
    // If collection settings overridden on view level.
    $disable_insert = $this->boSettings->getCollectionOptions($collection_id)['disable_insert'] ?? '';
    if ((string) $disable_insert == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $disable_insert = $collection_bundle->getCollectionOptions()['disable_insert'];
      }
    }
    return (bool) $disable_insert;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDisableBundleLabel($collection_id) {
    // If collection settings overridden on view level.
    $disable_bundle_label = $this->boSettings->getCollectionOptions($collection_id)['disable_bundle_label'] ?? '';
    if ((string) $disable_bundle_label == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $disable_bundle_label = $collection_bundle->getCollectionOptions()['disable_bundle_label'];
      }
    }
    return (bool) $disable_bundle_label;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCollectionIgnoreCurrentPath($collection_id) {
    // If collection settings overridden on view level.
    $ignore_current_path = $this->boSettings->getCollectionOptions($collection_id)['ignore_current_path'] ?? '';
    if ((string) $ignore_current_path == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $ignore_current_path = $collection_bundle->getCollectionOptions()['ignore_current_path'];
      }
    }
    return (bool) $ignore_current_path;
  }


  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDisableSize($collection_id) {
    // If collection settings overridden on view level.
    $disable_size = $this->boSettings->getCollectionOptions($collection_id)['disable_size'] ?? '';
    if ((string) $disable_size == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $disable_size = $collection_bundle->getCollectionOptions()['disable_size'];
      }
    }
    return (bool) $disable_size;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSmallOperations($collection_id) {
    // If collection settings overridden on view level.
    $small_operations = $this->boSettings->getCollectionOptions($collection_id)['small_operations'] ?? '';
    if ((string) $small_operations == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $small_operations = $collection_bundle->getCollectionOptions()['small_operations'];
      }
    }
    return (bool) $small_operations;
  }

  /**
   * @param $collection_id
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getInsertPosition($collection_id) {
    // If collection settings overridden on view level.
    $insert_position = $this->boSettings->getCollectionOptions($collection_id)['insert_position'] ?? '';
    if ((string) $insert_position == '') {
      // If not get collection settings from bundle.
      if ($collection_bundle = $this->getCollectionBundle($collection_id)) {
        $insert_position = $collection_bundle->getCollectionOptions()['insert_position'];
      }
    }

    if ($insert_position == '') {
      $insert_position = 'auto';
    }
    return $insert_position;
  }

  /**
   * @param $collection_id
   *
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

    if ($collection_view !== NULL && $collection_view != '') {
      return explode('__', $collection_view);
    }
    else {
      if ($collection_id !== NULL) {
        if ($block = Block::load($collection_id)) {
          [$type, $view] = explode(':', $block->getPluginId());
          return explode('-', $view);
        }
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
    foreach ($this->boBundle->getSortedBundles() as $typed_bundles) {
      if (is_array($typed_bundles)) {
        foreach ($typed_bundles as $grouped_bundles) {
          /** @var \Drupal\bo\Entity\BoBundle $bundle */
          foreach ($grouped_bundles as $bundle) {
            if ($this->isEnabledBundle($collection_id, $bundle)) {
              $enabled_bundles[] = $bundle;
            }
          }
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

  public function getCollectionEntities($collection_id, $to_path, $insert_under_entity_weight = 0) {
    /** @var Connection $connection */
    $connection = \Drupal::service('database');
    return $connection->query("SELECT id, weight FROM {bo} WHERE collection_id = :collection_id AND to_path = :to_path AND weight > :weight ORDER BY weight", [
      ':collection_id' => $collection_id,
      ':to_path' => $to_path,
      ':weight' => $insert_under_entity_weight,
    ]);
  }

}
