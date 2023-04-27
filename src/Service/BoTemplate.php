<?php

namespace Drupal\bo\Service;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandler;

/**
 *
 */
class BoTemplate {

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
  public function getRequiredByBoModules() {
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
  public function getRequiredByBoModulesPaths() {
    if ($this->subModules !== NULL) {
      return $this->modulesPaths;
    }

    foreach ($this->getRequiredByBoModules() as $module_name => $sub_module) {
      $this->modulesPaths[$module_name] = $sub_module->getPath();
    }

    // Add the bo module path as last one.
    $this->modulesPaths['bo'] = $this->moduleHandler->getModule('bo')->getPath();

    return $this->modulesPaths;
  }

  /**
   * @param $template_key
   * @return array|false
   */
  function detectMoreSpecificBoTemplate($template_key) {
    $bo_template_detect_paths = $this->getBoTemplateDetectPaths();
    $template_parts = explode('__', $template_key);
    foreach($template_parts as $template_part_delta => $template_part) {
      $template = str_replace('_', '-', implode('--', $template_parts));
      foreach ($bo_template_detect_paths as $template_path) {
        $template_file = DRUPAL_ROOT . '/' . $template_path . '/' . $template . ".html.twig";
        if (file_exists($template_file)) {
          return [
            'template' => $template,
            'path' => $template_path
          ];
        }
      }

      array_pop($template_parts);
    }
    return FALSE;
  }

  /**
   * @return array
   */
  public function getBoTemplateDetectPaths() {
    $paths = [];

    // Get active theme template paths.
    $active_theme = \Drupal::theme()->getActiveTheme();
    $paths[$active_theme->getName()] = $active_theme->getPath() . '/templates';

    // Get base theme template paths.
    $themeHandler = \Drupal::service('theme_handler');
    $paths[$active_theme->getExtension()->base_theme] = $themeHandler->getTheme($active_theme->getExtension()->base_theme)->getPath() . '/templates';

    // Get required by bo module template paths.
    foreach($this->getRequiredByBoModulesPaths() as $module => $module_path) {
      $paths[$module] = $module_path . '/templates';
    }

    return $paths;
  }

}
