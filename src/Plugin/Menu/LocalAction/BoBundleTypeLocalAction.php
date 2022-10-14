<?php

namespace Drupal\bo\Plugin\Menu\LocalAction;

use Drupal\bo\Service\BoSettings;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local action plugin with a dynamic title.
 */
class BoBundleTypeLocalAction extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * @var mixed
   */
  private $boBundleTypes;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, BoSettings $boSettings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
    $this->boBundleTypes = $boSettings->getBundleTypes();
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('bo.settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $type = \Drupal::routeMatch()->getParameter('type');
    return $this->t('Add new @type', [
      '@type' => $this->t($this->boBundleTypes[$type]['singular']),
    ]);
  }

}
