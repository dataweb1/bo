<?php

namespace Drupal\bo\Form;

use Drupal\bo\Service\BoSettings;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BoSettingsForm.
 *
 * @package Drupal\bo\Form
 */
class BoSettingsForm extends ConfigFormBase {

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('bo.settings')
    );
  }

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\bo\Service\BoSettings $boSettings
   */
  public function __construct(ConfigFactoryInterface $config_factory, private readonly BoSettings $boSettings) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bo.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
    ];

    $form["general"]['none_bo_dialogs_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable none-BO dialogs'),
      '#default_value' => $this->boSettings->getSetting("none_bo_dialogs_disabled"),
    ];

    $form['google'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google settings'),
      // '#prefix' => $reset_markup,
      // '#attributes' => array("class" => "google-settings"),
      '#description' => $this->t('Global Google settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
    ];

    $form['google']['google_translate_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google Translate'),
      '#default_value' => $this->boSettings->getGoogleTranslateEnabled(),
    ];

    $form['google']['google_translate_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Translate API Key'),
      '#default_value' => $this->boSettings->getGoogleTranslateKey(),
    ];

    $form['google']['google_maps_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#description' => $this->t('Get it at <a target="_blank" href="https://developers.google.com/maps/documentation/embed/get-api-key">https://developers.google.com/maps/documentation/embed/get-api-key</a>'),
      '#default_value' => $this->boSettings->getSetting("google_maps_key"),
    ];

    $form['bootstrap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bootstrap settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
    ];

    $form["bootstrap"]['load_bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Bootstrap'),
      '#default_value' => $this->boSettings->getSetting("load_bootstrap"),
    ];

    $file = DRUPAL_ROOT . "/" . \Drupal::service('module_handler')->getModule('bo')->getPath() . '/bo.libraries.yml';
    $file_contents = file_get_contents($file);
    $bo_libraries_data = Yaml::parse($file_contents);

    $form['bootstrap']['bootstrap_yml'] = [
      '#type' => 'textarea',
      '#title' => "bo_bootstrap:",
      '#default_value' => Yaml::dump($bo_libraries_data["bo_bootstrap"]),
      '#rows' => 10,
      '#prefix' => "<strong>" . $this->t('Bootstrap YML') . "</strong>",
    ];

    $form['#attached']['library'] = [
      'bo/bo_settings',
    ];

    $form['google']['google_translate_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google Translate'),
      '#default_value' => $this->boSettings->getGoogleTranslateEnabled(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $input = $form_state->getUserInput();

    $settings["none_bo_dialogs_disabled"] = $input["general"]["none_bo_dialogs_disabled"];

    $settings["google_translate_key"] = $input["google"]["google_translate_key"];
    $settings["google_translate_enabled"] = (bool) $input["google"]['google_translate_enabled'];
    $settings["google_maps_key"] = $input["google"]['google_maps_key'];
    $settings["load_bootstrap"] = (bool) $input["bootstrap"]["load_bootstrap"];
    // $settings["bootstrap_yml"] = $input["bootstrap"]["bootstrap_yml"];
    $this->boSettings->setBoBootstrapSettings($input);

    $this->boSettings->setSettings($settings);

    Cache::invalidateTags(["bo:settings"]);
  }

}
