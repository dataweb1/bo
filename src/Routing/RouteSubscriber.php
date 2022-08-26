<?php

namespace Drupal\bo\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($bo_add_route = $collection->get('entity.bo.add_form')) {
      $bo_insert_route = clone($bo_add_route);
      $bo_insert_route->setPath("/bo/insert/{bo_bundle}");
      $bo_insert_route->setDefault('_title_callback', '\Drupal\bo\Controller\BoEntityController::getInsertTitle');
      $collection->add("entity.bo.insert_form", $bo_insert_route);
    }
  }

}
