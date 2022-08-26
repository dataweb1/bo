<?php

namespace Drupal\bo\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class BoBundleEntityForm extends BundleEntityFormBase {

  private $bundle_name;
  private BoSettings $boSettings;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.settings')
    );
  }

  public function __construct(BoSettings $boSettings) {
    $this->boSettings = $boSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, $type = NULL) {

    $form = parent::form($form, $form_state);

    /** @var \Drupal\bo\Entity\BoBundleEntityInterface $entity */
    $entity_bundle = $this->entity;
    $content_entity_id = $entity_bundle->getEntityType()->getBundleOf();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_bundle->label(),
      '#description' => $this->t("Label for the %content_entity_id bundle.", ['%content_entity_id' => $content_entity_id]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_bundle->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bo\Entity\BoBundleEntity::load',
      ],
      '#disabled' => !$entity_bundle->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity_bundle->getDescription(),
      '#description' => $this->t('This text will be displayed on the "Add %content_entity_id" page.', ['%content_entity_id' => $content_entity_id]),
    ];


    $default_value = "";
    if ($entity_bundle->getGroup() != "") {
      $default_value = $this->boSettings->getBoBundleGroups($entity_bundle->getGroup());
    }

    $form['group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group'),
      '#default_value' => $default_value,
      '#autocomplete_route_name' => 'bo.autocomplete.bundle_groups',
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (\Drupal::moduleHandler()->moduleExists('field_ui') && $this->getEntity()->isNew()) {
      $actions['save_continue'] = $actions['submit'];
      $actions['save_continue']['#value'] = $this->t('Save and manage fields');
      $actions['save_continue']['#submit'][] = [$this, 'redirectToFieldUi'];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_bundle = $this->entity;

    /*
    $group = "";
    if ($form_state->getValue("group") == 1) {
    $group = "custom";
    }
    else {
    $group = "-";
    }
     */

    $group = $form_state->getValue("group");
    $this->boSettings->addBoBundleGroupIfNotExisting($group);

    $entity_bundle->setGroup(slugify($group));

    $status = $entity_bundle->save();
    $message_params = [
      '%label' => $entity_bundle->label(),
      '%content_entity_id' => $entity_bundle->getEntityType()->getBundleOf(),
    ];

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label %content_entity_id bundle.', $message_params));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label %content_entity_id bundle.', $message_params));
    }

    /* @todo: weight instellen */

    $url = Url::fromRoute('entity.bo_bundle.' . $entity_bundle->getType() . '_list');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Form submission handler to redirect to Manage fields page of Field UI.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function redirectToFieldUi(array $form, FormStateInterface $form_state) {
    $route_info = FieldUI::getOverviewRouteInfo($this->entity->getEntityType()->getBundleOf(), $this->entity->id());

    if ($form_state->getTriggeringElement()['#parents'][0] === 'save_continue' && $route_info) {
      $form_state->setRedirectUrl($route_info);
    }
  }

}
