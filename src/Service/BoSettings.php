<?php

namespace Drupal\bo\Service;

use Drupal\Core\Config\ConfigFactory;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class BoSettings {


  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var array|mixed|null
   */
  private $settings;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  private $subModules;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(AccountProxyInterface $currentUser, ConfigFactory $config, ModuleHandlerInterface $moduleHandler, EntityTypeManager $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->config = $config->getEditable('bo.settings');
    $this->settings = $this->config->get('config');
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param $setting
   * @return mixed
   */
  public function getSetting($setting) {
    return $this->settings[$setting];
  }

  /**
   * @param $settings_to_save
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
   * @param $settings_to_save
   * @param $key
   */
  public function replaceSettings($settings_to_save, $key) {
    $this->settings[$key] = $settings_to_save;
    $this->saveSettings();
  }

  /**
   * @return false|mixed
   */
  public function getCollections() {
    if (isset($this->settings["collection"])) {
      return $this->settings["collection"];
    }
    return FALSE;
  }

  /**
   * @param $collection_id
   * @return false|mixed
   */
  public function getCollection($collection_id) {
    if (isset($this->settings["collection"][$collection_id])) {
      return $this->settings["collection"][$collection_id];
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param $collection_id
   * @return false|mixed
   */
  public function getCollectionBundles($collection_id) {
    $collection = $this->getCollection($collection_id);
    if ($collection && !empty($collection['bundles'])) {
      return $collection;
    }
    return FALSE;
  }

  /**
   * @param $collection_id
   * @param string $option
   * @return array|mixed|string
   */
  public function getCollectionOptions($collection_id, string $option = "") {

    $collection_settings = $this->getCollection($collection_id);

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
  public function getStyles() {
    return $this->settings["styles"];
  }

  /**
   * @return mixed
   */
  public function getGoogleTranslateEnabled() {
    return $this->settings["google_translate_enabled"];
  }

  /**
   * @return mixed
   */
  public function getGoogleTranslateKey() {
    return $this->settings['google_translate_key'];
  }

  /**
   * @return mixed
   */
  public function getBundleTypes() {
    return $this->settings['bundle_types'];
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
  public function loadInstallSettings() {
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
   * @param $input
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
   *
   */
  private function saveSettings() {
    $this->config->set('config', $this->settings)->save();
  }

  /**
   * @return mixed
   */
  public function getNoneBoDialogsDisabled() {
    return $this->settings["none_bo_dialogs_disabled"];
  }

}
