<?php

namespace Drupal\bo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the BO bundle entity. A configuration entity used to manage
 * bundles for the BO bundle.
 *
 * @ConfigEntityType(
 *   id = "bo_bundle",
 *   label = @Translation("BO element"),
 *   bundle_of = "bo",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "type" = "type",
 *     "group" = "group",
 *     "weight" = "weight",
 *   },
 *   config_prefix = "bo_bundle",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "type",
 *     "group",
 *     "weight",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bo\BoBundleListBuilder",
 *     "form" = {
 *       "default" = "Drupal\bo\Form\BoBundleForm",
 *       "add" = "Drupal\bo\Form\BoBundleForm",
 *       "edit" = "Drupal\bo\Form\BoBundleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer bo elements",
 *   links = {
 *     "add-form" = "/admin/structure/bo/bundle/add/{type}",
 *     "edit-form" = "/admin/structure/bo/bundle/{bo_bundle}/edit",
 *     "delete-form" = "/admin/structure/bo/bundle/{bo_bundle}/delete",
 *     "element-list" = "/admin/structure/bo/bundle/element/list",
 *     "content-list" = "/admin/structure/bo/bundle/content/list",
 *   }
 * )
 */
class BoBundle extends ConfigEntityBundleBase implements BoBundleInterface {

  /**
   * The machine name of the bo bundle.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the bo bundle.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the bo bundle.
   *
   * @var string
   */
  protected $description;

  /**
   * The group of the bo bundle.
   *
   * @var string
   */
  protected $group;

  /**
   * The type of the bo bundle.
   *
   * @var string
   */
  protected $type;

  /**
   * The weight of the bo bundle.
   *
   * @var string
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group) {
    $this->group = $group;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getGroups() {

  }

}
