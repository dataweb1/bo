<?php

namespace Drupal\bo\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("bo_current_collection_id")
 */
class BoCurrentCollectionId extends Standard {

  protected $currentCollectionId;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->value = $this->t("Current Collection ID");

    /*
     * use $_POST["collection_id"] instead of \Drupal::request()->get('collection_id') because \Drupal::request() doesn't
     * support the possibility to empty the collection_id variable (needed on rendering sub views).
     */
    $this->currentCollectionId = "";
    if (isset($_POST["collection_id"]) && $_POST["collection_id"] != "") {
      $this->currentCollectionId = \Drupal::request()->get('collection_id');
    }
    else {
      if (isset($view->filter["bo_current_collection_id_filter"])) {
        $this->currentCollectionId = $view->filter["bo_current_collection_id_filter"]->value;
      }
    }
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    $this->value = $this->currentCollectionId;

    if ($this->value == "") {
      $this->value = $_SESSION["current_block_id"];

      $_SESSION["current_block_id"] = "";
    }

    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

}
