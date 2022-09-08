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
class BoBundleForm extends BundleEntityFormBase {

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

    /** @var \Drupal\bo\Entity\BoBundleInterface $entity */
    $entity_bundle = $this->entity;
    $bundle = $entity_bundle->getEntityType()->getBundleOf();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_bundle->label(),
      '#description' => $this->t("Label for the %bundle bundle.", ['%bundle' => $bundle]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_bundle->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bo\Entity\BoBundle::load',
      ],
      '#disabled' => !$entity_bundle->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity_bundle->getDescription(),
      '#description' => $this->t('This text will be displayed on the "Add %bundle" page.', ['%bundle' => $bundle]),
    ];

    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $entity_bundle->getDefault(),
      '#description' => $this->t("Default %bundle bundle?", ['%bundle' => $bundle]),
    ];

    $form['internal_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Internal title'),
      '#default_value' => $entity_bundle->getInternalTitle(),
      '#description' => $this->t("Internal title for the %bundle bundle?", ['%bundle' => $bundle]),
    ];

    $form['override_title_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override title label'),
      '#maxlength' => 255,
      '#default_value' => $entity_bundle->getOverrideTitleLabel(),
      '#description' => $this->t("Override title label for the %bundle bundle.", ['%bundle' => $bundle]),
    ];

    $form['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#maxlength' => 255,
      '#default_value' => $entity_bundle->getIcon(),
      '#description' => $this->t("Icon for the %bundle bundle.", ['%bundle' => $bundle]),
    ];

    $form['collection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collection'),
      '#default_value' => $entity_bundle->getCollectionEnabled(),
      '#description' => $this->t("Is %bundle bundle collection?", ['%bundle' => $bundle]),
    ];

    $form['group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group'),
      '#default_value' => $entity_bundle->getGroup(),
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

    $group = $form_state->getValue("group");
    $entity_bundle->setGroup($group);

    $default = $form_state->getValue("default");
    $entity_bundle->setDefault($default);

    $internal_title = $form_state->getValue("internal_title");
    $entity_bundle->setInternalTitle($internal_title);

    $override_title_label = $form_state->getValue("override_title_label");
    $entity_bundle->setOverrideTitleLabel($override_title_label);

    $icon = $form_state->getValue("icon");
    $entity_bundle->setIcon($icon);

    $collection = $form_state->getValue("collection");
    $entity_bundle->setCollectionEnabled($collection);

    $status = $entity_bundle->save();
    $message_params = [
      '%label' => $entity_bundle->label(),
      '%bundle' => $entity_bundle->getEntityType()->getBundleOf(),
    ];

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label %bundle bundle.', $message_params));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label %bundle bundle.', $message_params));
    }

    /* @todo: weight instellen */

    $url = Url::fromRoute('bo.entity.bundle.' . $entity_bundle->getType() . '_list');
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
