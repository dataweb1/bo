<?php

namespace Drupal\bo\Plugin\Menu\CustomLinks;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;

/**
 *
 */
class BoBundleAdministerElementsMenuLinksDerivative extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected $account;

  protected $uid;

  /**
   *
   */
  public function __construct(AccountProxy $current_user) {
    $this->uid = $current_user->id();
    $this->account = User::load($current_user->id());
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
          $container->get('current_user')
      );
  }

  /**
   *
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $links['bo_bundle_entity_element_list_menu'] = [
      'title' => 'BO ' . $this->t("elements"),
      'description' => 'Administer BO elements',
              // 'parent' => 'system.admin_structure',
              // 'weight' => 3,
              // 'route_name' => 'entity.bo_bundle.element_list',
              // 'route_parameters' => ['user' => $this->uid]
    ] + $base_plugin_definition;

    return $links;
  }

}
