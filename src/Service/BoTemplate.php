<?php

namespace Drupal\bo\Service;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 *
 */
class BoTemplate {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ModuleExtensionList $extension) {
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
        if ($found_path = $this->findTemplate($template, $template_path)) {
          return [
            'template' => $template,
            'path' => $found_path
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
    if (property_exists($active_theme->getExtension(), 'base_theme') && $active_theme->getExtension()->base_theme != '') {
      $themeHandler = \Drupal::service('theme_handler');
      $paths[$active_theme->getExtension()->base_theme] = $themeHandler->getTheme($active_theme->getExtension()->base_theme)->getPath() . '/templates';
    }

    // Get required by bo module template paths.
    foreach($this->getRequiredByBoModulesPaths() as $module => $module_path) {
      $paths[$module] = $module_path . '/templates';
    }

    return $paths;
  }

  /**
   * @param $template
   * @param $dir
   * @return false|string
   */
  public function findTemplate($filename, $dir, $root = TRUE) {
    if ($root) {
      $dir = DRUPAL_ROOT . '/' . $dir;
    }
    if (is_dir($dir)) {
      $files = scandir($dir);
      foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
          continue;
        }
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
          $result = $this->findTemplate($filename, $path, FALSE);
          if ($result !== FALSE) {
            return $result;
          }
        }
        else if ($file == $filename . '.html.twig') {
          return str_replace(DRUPAL_ROOT . '/', '', $dir);
        }
      }
    }
    return FALSE;
  }

}
