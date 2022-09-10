<?php

namespace Drupal\bo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the BO bundle entity. A configuration entity used to manage
 * bundles for the BO bundle.
 *
 * @ConfigEntityType(
 *   id = "bundle",
 *   label = @Translation("BO element"),
 *   bundle_of = "bo",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "type" = "type",
 *     "group" = "group",
 *     "default" = "default",
 *     "internal_title" = "internal_title",
 *     "override_title_label" = "override_title_label",
 *     "icon" = "icon",
 *     "collection" = "collection",
 *     "related_bundles" = "related_bundles",
 *     "weight" = "weight",
 *   },
 *   config_prefix = "bundle",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "type",
 *     "group",
 *     "default",
 *     "internal_title",
 *     "override_title_label",
 *     "icon",
 *     "collection",
 *     "related_bundles",
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
 *   admin_permission = "administer bo bundles",
 *   links = {
 *     "add-form" = "/admin/structure/bo/bundle/add/{type}",
 *     "edit-form" = "/admin/structure/bo/bundle/{bundle}/edit",
 *     "delete-form" = "/admin/structure/bo/bundle/{bundle}/delete",
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
   * The default of the bo bundle.
   *
   * @var boolean
   */
  protected $default;

  /**
   * The internal title of the bo bundle.
   *
   * @var string
   */
  protected $internal_title;

  /**
   * The override title label of the bo bundle.
   *
   * @var string
   */
  protected $override_title_label;

  /**
   * The icon of the bo bundle.
   *
   * @var string
   */
  protected $icon;

  /**
   * The collection of the bo bundle.
   *
   * @var array
   */
  protected $collection;

  /**
   * The related bundle of the bo bundle.
   *
   * @var array
   */
  protected $related_bundles;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function getInternalTitle() {
    return $this->internal_title;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverrideTitleLabel() {
    return $this->override_title_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon() {
    return $this->icon;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionEnabled() {
    return $this->collection['enabled'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionBundles() {
    return $this->collection['bundles'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionOptions() {
    return $this->collection['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedBundleS() {
    return $this->related_bundles;
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
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->default = $default;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setInternalTitle($internal_title) {
    $this->internal_title = $internal_title;
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function setOverrideTitleLabel($override_title_label) {
    $this->override_title_label = $override_title_label;
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function setIcon($icon) {
    $this->icon = $icon;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCollectionEnabled($enabled) {
    $this->collection['enabled'] = $enabled;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCollectionBundles($bundles) {
    $this->collection['bundles'] = $bundles;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCollectionOptions($options) {
    $this->collection['options'] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelatedBundles($related_bundles) {
    $this->related_bundles = $related_bundles;
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
