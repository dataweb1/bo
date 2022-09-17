<?php

namespace Drupal\bo\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a filter for Current Path.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("bo_current_path")
 */
class BoCurrentPath extends Standard {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    //if ((string) $this->argument == "" || (string) $this->argument == "all") {
      $this->argument = $this->getCurrentPath();
    //}
  }

  /**
   * {@inheritdoc}
   */
  public function setArgument($arg) {
    if ((string) $arg == "" || (string) $arg == "all") {
      $arg = $this->getCurrentPath();
    }

    // If we are not dealing with the exception argument, example "all".
    // if ($this->isException($arg)) {
    //    return parent::setArgument($arg);
    // }
    $this->argument = $arg;
    return $this->validateArgument($arg);
  }


  /**
   * @param false $group_by
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $this->argument);
  }

  /**
   * @return array|mixed|string|string[]
   */
  public function getCurrentPath() {
    $current_path = \Drupal::request()->get('to_path');

    if ($current_path == "") {
      $current_path = \Drupal::service('path.current')->getPath();
      $base_url = \Drupal::request()->getBaseUrl();
      $current_path = str_replace($base_url, "", $current_path);
    }

    return $current_path;
  }

}
