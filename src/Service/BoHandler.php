<?php

namespace Drupal\bo\Service;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandler;

/**
 *
 */
class BoHandler {

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private ModuleHandler $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private ModuleExtensionList $extension;

  /**
   * @var null
   */
  private $subModules = NULL;

  /**
   * @var null
   */
  private $modulesPaths = NULL;

  /**
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension
   */
  public function __construct(ModuleHandler $moduleHandler, ModuleExtensionList $extension) {
    $this->moduleHandler = $moduleHandler;
    $this->extension = $extension;
  }

  /**
   * @return mixed
   */
  public function getModules() {
    if ($this->subModules !== NULL) {
      return $this->subModules;
    }

    $modules = $this->extension->getList();
    foreach ($modules['bo']->required_by as $module_name => $required_by) {
      $this->subModules[$module_name] = $modules[$module_name];
    }

    return $this->subModules;
  }

  /**
   * @return array
   */
  public function getModulesPaths() {
    if ($this->subModules !== NULL) {
      return $this->modulesPaths;
    }

    foreach ($this->getModules() as $module_name => $sub_module) {
      $this->modulesPaths[$module_name] = $sub_module->getPath();
    }

    // Add the bo module path as last one.
    $this->modulesPaths['bo'] = $this->getBoPath();

    return $this->modulesPaths;
  }

  /**
   * @return string
   */
  public function getBoPath() {
    return $this->moduleHandler->getModule('bo')->getPath();
  }

}
