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
class BoBundleAdministerContentTypesMenuLinksDerivative extends DeriverBase implements ContainerDeriverInterface {

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
    $links['bo_bundle_content_list_menu'] = [
      'title' => 'BO ' . $this->t("content"),
      'description' => 'Administer BO content',
    ] + $base_plugin_definition;

    return $links;
  }

}
