<?php

namespace Drupal\bo\Service;

use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 *
 */
class BoView {

  /**
   * @param $view_id
   * @param $display_id
   * @param $collection_id
   * @param string $to_path
   * @return ViewExecutable|null
   */
  public function prepareBoView($view_id, $display_id, $collection_id, string $to_path = ''): ?ViewExecutable {
    /** @var ViewExecutable $view */
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->preExecute();
    $argument = $view->argument;
    $filter = $view->filter;

    $view = Views::getView($view_id);
    $view->setDisplay($display_id);

    // Set the current display id filter if defined in the view.
    if (array_key_exists('bo_current_display_id_filter', $filter)) {
      $view->filter["bo_current_display_id_filter"]->value = $display_id;
    }

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

  /**
   *
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

}
