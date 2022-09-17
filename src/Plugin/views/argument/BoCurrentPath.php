<?php

namespace Drupal\bo\Plugin\views\argument;

use Drupal\bo\Service\BoCollection;
use Drupal\views\Plugin\views\argument\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a filter for Current Path.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("bo_current_path")
 */
class BoCurrentPath extends Standard {

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @var string
   */
  private $collection_id;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, BoCollection $boCollection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->boCollection = $boCollection;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static ($configuration, $plugin_id, $plugin_definition,
      \Drupal::service('bo.collection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setArgument($arg) {

    if ((string) $arg == "" || (string) $arg == "current_path") {
      $arg = $this->getCurrentPath();
    }

    // Set arg to 'all' if bypass current path options is set for collection.
    $this->collection_id = $this->view->filter['bo_current_collection_id_filter']->value;
    if ($this->boCollection->getCollectionIgnoreCurrentPath($this->collection_id)) {
      $arg = 'all';
    }

    // If we are not dealing with the exception argument, example "all".
    if ($this->isException($arg)) {
        return parent::setArgument($arg);
    }

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
