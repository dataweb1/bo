<?php

namespace Drupal\bo\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\views\Views;
use Drupal\bo\Service\BoCollection;

/**
 * Defines the BO bundle.
 *
 * @ContentEntityType(
 *   id = "bo",
 *   label = @Translation("BO"),
 *   base_table = "bo",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "uid" = "uid",
 *     "label" = "title",
 *     "created" = "created",
 *     "changed" = "changed",
 *     "nnid" = "nid",
 *   },
 *   fieldable = TRUE,
 *   admin_permission = "administer bo entities",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bo\BoEntityListBuilder",
 *     "access" = "Drupal\bo\BoEntityAccessControlHandler",
 *     "views_data" = "Drupal\bo\BoViewsData",
 *     "form" = {
 *       "default" = "Drupal\bo\Form\BoEntityForm",
 *       "add" = "Drupal\bo\Form\BoEntityForm",
 *       "edit" = "Drupal\bo\Form\BoEntityForm",
 *       "delete" = "Drupal\bo\Form\BoEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/bo/{bo}",
 *     "add-page" = "/bo/add",
 *     "add-form" = "/bo/add/{bundle}",
 *     "edit-form" = "/bo/{bo}/edit",
 *     "delete-form" = "/bo/{bo}/delete",
 *     "collection" = "/admin/content/bos",
 *   },
 *   bundle_entity_type = "bundle",
 *   field_ui_base_route = "entity.bundle.edit_form",
 * )
 */
class BoEntity extends ContentEntityBase implements BoEntityInterface {

  use EntityChangedTrait;

  /**
   * @var \Drupal\bo\Service\BoCollection|object|null
   */
  private BoCollection $boCollection;

  /**
   *
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->boCollection = \Drupal::getContainer()->get('bo.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->get("id")->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get("weight")->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get("type")->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getToPath() {
    return $this->get('to_path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionId() {
    return $this->get('collection_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeId() {
    return $this->get('nid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentViewDisplaySettings() {
    if (!isset($this->boCollection)) {
      $this->boCollection = \Drupal::service('bo.collection');
    }

    if ([$view_id, $display_id] = $this->boCollection->getCollectionView($this->getCollectionId())) {

      $view = Views::getView($view_id);
      $view->setDisplay($display_id);
      $view->preExecute();
      $settings = $view->style_plugin->options;
      $settings["plugin_id"] = $view->style_plugin->getPluginId();

      return $settings;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getViewDisplaySettings($view_id, $display_id) {
    if ($display_id != "") {
      $view = Views::getView($view_id);
      $view->setDisplay($display_id);
      $view->preExecute();
      $settings = $view->style_plugin->options;
      $settings["plugin_id"] = $view->style_plugin->getPluginId();
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    if ($this->isCurrentCustomSizeEnabled()) {
      if (intval($this->get('size')->value) == 0) {
        return 12;
      }
      return $this->get('size')->value;
    }
    else {
      $max_size = 0;
      if ($settings = $this->getCurrentViewDisplaySettings()) {
        if ($settings["plugin_id"] == "views_bootstrap_grid") {
          $size["xs"] = $settings["col_xs"];
          $size["sm"] = $settings["col_sm"];
          $size["md"] = $settings["col_md"];
          $size["lg"] = $settings["col_lg"];

          foreach ($size as $s) {
            if (preg_match('~col-[a-z]{2}-([0-9]*)~', $s, $matches)) {
              if ($matches[1] > $max_size) {
                $max_size = $matches[1];
              }
            }
          }
          return $max_size;
        }
      }
    }
    return 12;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set("weight", $weight);
  }

  /**
   * {@inheritdoc}
   */
  public function setNodeId($nid) {
    $this->set("nid", $nid);
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentCustomSizeEnabled() {
    if ($this->display_id != "") {
      if ($settings = $this->getCurrentViewDisplaySettings()) {
        if ($settings["plugin_id"] == "views_view_bo_bootstrap_grid") {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function isCustomSizeEnabled($view_id, $display_id) {
    if ($display_id != "") {
      $settings = BoEntity::getViewDisplaySettings($view_id, $display_id);
      if ($settings["plugin_id"] == "views_view_bo_bootstrap_grid") {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setSettings([
        'max_length' => 12,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['to_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('To path'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ]);
    // ->setDisplayConfigurable('form', TRUE)
    // ->setDisplayConfigurable('view', TRUE);
    $fields['collection_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('To Collection ID'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('-')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ]);
    // ->setDisplayConfigurable('form', TRUE)
    // ->setDisplayConfigurable('view', TRUE);

    /** @var \Drupal\bo\Service\BoSettings $boSettings */
    $boSettings = \Drupal::getContainer()->get('bo.settings');
    $styles = $boSettings->getStyles();
    $allowed_values = [0 => "-"];
    foreach ($styles as $label => $style) {
      $allowed_values[$style["size"]] = str_replace("BO ", "", $label);
    }

    $fields['size'] = BaseFieldDefinition::create("list_string")
      ->setSettings([
        'max_length' => 10,
        'allowed_values' => $allowed_values,
      ])
      ->setLabel('Size')
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the BO entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setTranslatable(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node added to'))
      ->setDescription(t('The node ID where the BO entity is added to.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default');
      //->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $nid = \Drupal::request()->query->get('nid');
    if (intval($nid) == 0) {
      $nid = NULL;
    }
    $values += [
      'uid' => \Drupal::currentUser()->id(),
      'nid' => $nid,
      'langcode' => $langcode,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $tags = parent::getCacheTags();
    return $tags + ["bo:" . $this->id()];
  }

}
