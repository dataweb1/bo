<?php

namespace Drupal\bo\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Session\AccountProxy;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class BoSettings {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  private AccountProxy $currentUser;

  /**
   * @var array|mixed|null
   */
  private $settings;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private ModuleHandler $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * @var array
   */
  private $sortedBundles = [];

  /**
   *
   */
  public function __construct(Connection $connection, AccountProxy $currentUser, ConfigFactory $config, ModuleHandler $moduleHandler, EntityTypeManager $entityTypeManager) {
    $this->connection = $connection;
    $this->currentUser = $currentUser;
    $this->config = $config->getEditable('bo.settings');
    $this->settings = $this->config->get('config');
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   *
   */
  public function getFields($bundle = "") {
    if ($bundle != "") {
      return $this->settings["fields"][$bundle];
    }
    else {
      return $this->settings["fields"];
    }
  }

  /**
   *
   */
  public function getBoBundles($bundle_name = "") {

    $bundles = [];
    $entities = $this->entityTypeManager->getStorage('bundle')->loadByProperties();

    foreach ($entities as $entity) {
      if ($bundle_name == "") {
        $bundle = ["label" => $entity->label(), "machine_name" => $entity->id()];
        $bundles[] = $bundle;
      }
      else {
        if ($bundle_name == $entity->id()) {
          return ["label" => $entity->label(), "machine_name" => $entity->id()];
        }
      }
    }

    return $bundles;
  }

  /**
   *
   */
  public function getSortedBundles() {

    if (!empty($this->sortedBundles)) {
      return $this->sortedBundles;
    }

    $entities = $this->entityTypeManager->getStorage('bundle')->loadByProperties();

    foreach ($entities as $entity) {
      $icon = $this->getBundles($entity->id())["icon"];
      $bundle = [
        'label' => $entity->label(),
        'group' => $entity->getGroup(),
        'machine_name' => $entity->id(),
        'icon' => $icon,
      ];
      $this->sortedBundles[$entity->getGroup()][$entity->getWeight()] = $bundle;
    }

    foreach ($this->sortedBundles as $group => &$group_bundles) {
      ksort($group_bundles);
    }

    return $this->sortedBundles;
  }

  /**
   *
   */
  public function addBoBundleGroupIfNotExisting($chosen_group) {
    $found = FALSE;
    $groups = $this->getBoBundleGroups();
    foreach ($groups as $machine_name => $group) {
      if ($group == $chosen_group) {
        $found = TRUE;
      }
    }

    if ($found == FALSE) {
      $groups[slugify($chosen_group)] = $chosen_group;
    }

    $this->replaceSettings($groups, "bundle_groups");
  }

  /**
   *
   */
  public function getBoBundleGroups($group = "") {
    if ($group != "") {
      return $this->settings["bundle_groups"][$group];
    }
    else {
      return $this->settings["bundle_groups"];
    }
  }

  /**
   *
   */
  public function cleanupBundleGroups($groups_with_bundles) {
    $groups = $this->getBoBundleGroups();
    $updates_groups = $groups;
    foreach ($groups as $machine_name => $group) {
      if (!in_array($machine_name, $groups_with_bundles)) {
        unset($updates_groups[$machine_name]);
      }
    }

    $this->replaceSettings($updates_groups, "bundle_groups");
  }

  /**
   *
   */
  public function getBundles($bundle = '') {
    if ($bundle != '') {
      return $this->settings["bundles"][$bundle] ?? '';
    }
    else {
      return $this->settings["bundles"];
    }
  }

  /**
   *
   */
  public function getCollections($collection = "") {
    if (isset($this->settings["collection"])) {
      if ($collection != "" && isset($this->settings["collection"][$collection])) {
        return $this->settings["collection"][$collection];
      }
      else {
        return $this->settings["collection"];
      }
    }
  }

  /**
   *
   */
  public function getBundleLabel($bundle_name) {
    return $this->getBundles($bundle_name)["label"];
  }

  /**
   *
   */
  public function getFieldProperties($bundle = "") {
    if ($bundle != "") {
      return $this->settings["field_properties"][$bundle];
    }
    else {
      return $this->settings["field_properties"];
    }
  }

  /**
   *
   */
  public function getSelectTypes() {
    $select_types = [];
    foreach ($this->getBundles() as $bundle_name => $bundle) {
      foreach ($bundle as $type_name => $type) {
        if (isset($type["label"])) {
          $select_types[$type_name] = $type["label"];
        }
      }
    }
    return $select_types;
  }

  /**
   *
   */
  public function getStyles() {
    return $this->settings["styles"];
  }

  /**
   *
   */
  public function getCollectionBundleMachineNameViaId($collection_id) {

    $select = $this->connection->select('bo', 'b');
    $select->fields('b');
    $select->condition('id', $collection_id, "=");
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (isset($entries[0])) {
      return $entries[0]["bundle"];
    }

  }

  /**
   *
   */
  public function hasCollectionSpecificView($view_name) {
    $all_collection_settings = $this->getCollections();
    foreach ($all_collection_settings as $collection_machine_name => $collection_setting) {
      if ($collection_setting["options"]["specific_view"] == $view_name) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   *
   */
  public function getCollectionTypeBasedOnSpecificView($view_name) {
    $all_collection_settings = $this->getCollections();

    foreach ($all_collection_settings as $collection_machine_name => $collection_setting) {

      if ($this->getCollectionOptions($collection_machine_name, "specific_view") == $view_name) {

      }

    }

    return "";
  }

  /**
   *
   */
  public function getActiveCollectionData($parameters) {

    $collection_settings = $this->getCollections();

    $overview_or_collection = $parameters["overview_or_collection"];
    $display_id = $parameters["display_id"];
    $collection_id = $parameters["collection_id"];
    $collection_machine_name = $parameters["collection_machine_name"];

    if ($overview_or_collection == "collection") {
      return [
        "options_overrided" => FALSE,
        "collection_name" => $collection_machine_name,
        "save_to_collection_name" => $collection_machine_name,
      ];
    }
    else {
      $collection_name = $display_id . "__" . $collection_id;
      if (isset($collection_settings[$collection_name]) && isset($collection_settings[$collection_name]["elements"])) {
        // Collection settings to go back to.
        if ($collection_machine_name != "") {
          $reset_to = $collection_machine_name . " collection";
        }
        else {
          $reset_to = "default elements";
        }
        return [
          "options_overrided" => TRUE,
          "collection_name" => $collection_name,
          "save_to_collection_name" => $collection_name,
          "reset_to" => $reset_to,
        ];
      }
      else {
        $res = array_key_exists($collection_machine_name, $collection_settings);
        if ($res && $collection_machine_name != "") {
          return [
            "options_overrided" => FALSE,
            "collection_name" => $collection_machine_name,
            "save_to_collection_name" => $collection_name,
          ];
        }
        else {
          if ($this->hasCollectionSpecificView($display_id)) {
            foreach ($collection_settings as $collection_machine_name => $collection_setting) {
              if ($collection_setting["options"]["specific_view"] == $display_id) {
                $res = array_key_exists($collection_machine_name, $collection_settings);
                if ($res) {
                  return [
                    "options_overrided" => FALSE,
                    "collection_name" => $collection_machine_name,
                    "save_to_collection_name" => $collection_name,
                  ];
                }
              }
            }
          }
        }
      }
    }

    return [
      "options_overrided" => FALSE,
      "collection_name" => "default",
      "save_to_collection_name" => $collection_name,
    ];

  }

  /**
   *
   */
  public function isTypeInCollectionBasedOnSpecificView($view_name, $bundle_name, $type_name) {
    $all_collection_settings = $this->getCollections();

    foreach ($all_collection_settings as $collection_machine_name => $collection_setting) {
      if ($collection_setting["options"]["specific_view"] == $view_name) {

        return $this->isCollectionTypeInBundle($collection_machine_name, $bundle_name, $type_name);
      }
    }

    return "";
  }

  /**
   *
   */
  public function getCollectionOptions($collection_machine_name, $option = "") {

    $collection_settings = $this->getCollections($collection_machine_name);

    if (!isset($collection_settings["options"])) {
      return "";
    }

    if ($option != "") {
      return $collection_settings["options"][$option] ?? "";
    }
    else {
      return $collection_settings["options"] ?? [];
    }
  }

  /**
   *
   */
  public function isCollectionElementChecked($collection_name, $bundle_name) {
    if ($collection_name == "default") {
      return (bool) $this->settings["bundles"][$bundle_name]["default"];
    }
    else {
      $collection_settings = $this->settings["collection"][$collection_name]["elements"];
      if (array_key_exists($bundle_name, $collection_settings)) {
        return $collection_settings[$bundle_name];
      }
    }
    return FALSE;
  }

  /**
   *
   */
  public function isCollectionTypeInBundle($collection_machine_name, $bundle_name = NULL, $type_name = NULL) {

    $collection_settings = $this->settings["collection"][$collection_machine_name];

    if (array_key_exists($type_name, $collection_settings[$bundle_name . "_types"])) {
      return $collection_settings[$bundle_name . "_types"][$type_name];
    }
    else {
      return "";
    }
  }

  /**
   *
   */
  public function isTitleInternal($bundle) {
    return (bool) $this->settings["bundles"][$bundle]["internal_title"];
  }

  /**
   *
   */
  public function getGoogleTranslateEnabled() {
    ;
    return $this->settings["google_translate_enabled"];
  }

  /**
   *
   */
  public function getBoSetting($setting) {
    return $this->settings[$setting];
  }

  /**
   *
   */
  public function getBundleLabelOrTypeLabel($collection_name, $bundle_name, $override_bundle_label) {
    // $overview_settings = self::getOverviews();
    $collection_settings = $this->getCollections();
    $types = $this->getSelectTypes();

    $count = 0;

    foreach ($collection_settings[$collection_name][$bundle_name . "_types"] as $type_name => $enabled) {
      if ($enabled == 1) {
        $count++;
        $found_type_name = $type_name;
      }
    }

    if ($count == 1) {
      return ucfirst($types[$found_type_name]);
    }
    else {
      return $override_bundle_label;
    }
  }

  /**
   *
   */
  public function setSettings($settings_to_save) {

    foreach ($settings_to_save as $key => $settings) {
      if (is_array($settings)) {
        foreach ($settings as $key1 => $settings1) {
          $this->settings[$key][$key1] = $settings1;
        }
      }
      else {
        $this->settings[$key] = $settings;
      }
    }
    $this->saveSettings();
  }

  /**
   *
   */
  public function replaceSettings($settings_to_save, $key) {

    $this->settings[$key] = $settings_to_save;

    $this->saveSettings();
  }

  /**
   *
   */
  public function getYmlData($file) {
    $file_contents = file_get_contents($file);
    return Yaml::parse($file_contents);
  }

  /**
   *
   */
  public function parseSettingsYml() {
    $file = DRUPAL_ROOT . "/" . $this->moduleHandler->getModule('bo')->getPath() . '/config/install/bo.settings.yml';
    $file_contents = file_get_contents($file);
    $settings_data = Yaml::parse($file_contents);

    foreach ($settings_data['config']["styles"] as $label => $style) {
      $settings["styles"][$label]["size"] = $style["size"];
      $settings["styles"][$label]["width"] = $style["width"];
    }

    $this->setSettings($settings);
  }

  /**
   *
   */
  public function addFieldToType(&$field_names, $field_name) {
    $key = array_search($field_name, $field_names);
    if (FALSE === $key) {
      $field_names[] = $field_name;
    }
  }

  /**
   *
   */
  public function removeFieldFromType(&$field_names, $field_name) {
    $key = array_search($field_name, $field_names);
    if (FALSE !== $key) {
      unset($field_names[$key]);
    }
  }

  /**
   *
   */
  public function isBundleCollection($bundle_name) {
    $bundle_settings = $this->getBundles();

    return (bool) $bundle_settings[$bundle_name]["collection"];
  }

  /**
   *
   */
  public function getEnabledBundles($parameters) {
    $enabled_bundles = [];
    foreach ($this->getSortedBundles() as $group => $group_bundles) {

      foreach ($group_bundles as $bundle) {
        $bundle_name = $bundle["machine_name"];
        $bundle_label = $bundle["label"];
        $bundle_icon = $bundle["icon"];

        $bundle_create_perms = $this->currentUser->hasPermission("create bo " . $bundle_name);

        $collection_machine_name = "";
        if ((int) $parameters["collection_id"] > 0) {
          $collection_machine_name = $this->getCollectionBundleMachineNameViaId($parameters["collection_id"]);
        }

        if ($bundle_create_perms) {
          $active_collection_parameters = [
            "overview_or_collection" => "overview",
            "display_id" => $parameters["display_id"],
            "collection_id" => $parameters["collection_id"],
            "collection_machine_name" => $collection_machine_name,
          ];

          $active_collection = $this->getActiveCollectionData($active_collection_parameters);

          $element_enabled = $this->isCollectionElementChecked($active_collection["collection_name"], $bundle_name);

          if ($element_enabled) {
            $enabled_bundles[$bundle_name] = [
              "group" => $group,
              "bundle" => $bundle_name,
              "label" => $bundle_label,
              "icon" => $bundle_icon,
            ];
          }
        }
      }
    }

    return $enabled_bundles;
  }

  /**
   *
   */
  public function setBoBootstrapSettings($input) {

    $file = DRUPAL_ROOT . "/" . $this->moduleHandler->getModule('bo')->getPath() . '/bo.libraries.yml';

    $file_contents = file_get_contents($file);
    $bo_libraries_data = Yaml::parse($file_contents);
    $bo_libraries_data["bo_bootstrap"] = Yaml::parse($input["bootstrap"]["bootstrap_yml"]);
    $bo_libraries_data_yml = Yaml::dump($bo_libraries_data);

    file_put_contents($file, $bo_libraries_data_yml);
  }

  /**
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   */
  public function hasEditPermissions($enabled_bundles) {
    foreach ($enabled_bundles as $bundle_name => $bundle) {
      $bundle_edit_perms = $this->currentUser->hasPermission("edit any bo " . $bundle_name);
      if ($bundle_edit_perms) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param $enabled_bundles
   * @return bool
   * @See \Drupal\bo\Plugin\views\area\BoHeader
   * @See \Drupal\bo\Plugin\views\field\BoOperations
   */
  public function hasCreatePermissions($enabled_bundles) {
    foreach ($enabled_bundles as $bundle_name => $bundle) {
      $bundle_create_perms = $this->currentUser->hasPermission("create bo " . $bundle_name);
      if ($bundle_create_perms) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   *
   */
  private function saveSettings() {
    $this->config->set('config', $this->settings)->save();
  }

}
