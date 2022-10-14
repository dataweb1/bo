<?php

namespace Drupal\bo\Routing;

use Drupal\bo\Enum\BoBundleType;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var mixed
   */
  private $boBundleTypes;

  public function __construct(BoSettings $boSettings) {
    $this->boBundleTypes = $boSettings->getBundleTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($bo_add_route = $collection->get('entity.bo.add_form')) {
      $bo_insert_route = clone($bo_add_route);
      $bo_insert_route->setPath("/bo/insert/{bundle}");
      $bo_insert_route->setDefault('_title_callback', '\Drupal\bo\Controller\BoEntityController::getInsertTitle');
      $collection->add("entity.bo.insert_form", $bo_insert_route);
    }

    foreach ($this->boBundleTypes as $type_id => $type) {
      $route = new Route(
        '/admin/structure/bo/bundle/' . $type_id . '/list',
        [
          '_entity_list' => 'bundle',
          '_title_callback' => '\Drupal\bo\Controller\BoBundleController::getBoBundleElementsListTitle',
          'type' => $type_id,
        ],
        [
          '_permission' => 'administer bo bundles',
        ]
      );
      $collection->add('bo.entity.bundle.' . $type_id . '_list', $route);
    }
  }

}
