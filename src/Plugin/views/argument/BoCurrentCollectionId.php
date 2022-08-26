<?php

namespace Drupal\bo\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a filter for Collection ID.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("bo_current_collection_id")
 */
class BoCurrentCollectionId extends Standard {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if ((string) $this->argument == "" || (string) $this->argument == "all") {
      $this->argument = "-";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setArgument($arg) {
    if ((string) $arg == "" || (string) $arg == "all") {
      $arg = "-";
    }

    // If we are not dealing with the exception argument, example "all".
    // if ($this->isException($arg)) {
    //    return parent::setArgument($arg);
    // }
    $this->argument = $arg;
    return $this->validateArgument($arg);
  }

  /**
   *
   */
  public function query($group_by = FALSE) {

    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $this->argument);
  }

}
