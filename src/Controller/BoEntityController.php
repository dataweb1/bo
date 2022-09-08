<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoVars;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class BoEntityController extends ControllerBase {

  /**
   * @var Request
   */
  private Request $request;

  /**
   * @var Renderer
   */
  private Renderer $renderer;

  /**
   * @var BoVars
   */
  private BoVars $boVars;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;


  /**
   *
   */
  public function __construct(RequestStack $request,Renderer $renderer, BoVars $boVars, BoCollection $boCollection) {
    $this->request = $request->getCurrentRequest();
    $this->renderer = $renderer;
    $this->boVars = $boVars;
    $this->boCollection = $boCollection;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('renderer'),
      $container->get('bo.vars'),
      $container->get('bo.collection'),

    );
  }

  /**
   * Get title.
   */
  public function getInsertTitle($bundle) {

    $title = $this->t(
      "Insert @bundle",
      [
        '@bundle' => $bundle->get("label"),
      ]
    );

    return $title;
  }

  /**
   * Render help dialog via Twig link.
   *
   * @param $collection_id
   * @param $entity_id
   * @return array
   */
  public function help($collection_id, $entity_id) {
    return ['#markup' => '<div id="help-content" data-collection-id="' . $collection_id . '" data-entity-id="' . $entity_id . '"></div>'];
  }

}
