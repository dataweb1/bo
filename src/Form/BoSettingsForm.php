<?php

namespace Drupal\bo\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BoSettingsForm.
 *
 * @package Drupal\bo\Form
 */
class BoSettingsForm extends ConfigFormBase {

  private BoSettings $boSettings;

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
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, BoSettings $boSettings) {
    parent::__construct($config_factory);
    $this->boSettings = $boSettings;
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
      '#default_value' => $this->boSettings->getBoSetting("google_translate_enabled"),
    ];

    $form['google']['google_translate_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Translate API Key'),
      '#default_value' => $this->boSettings->getBoSetting("google_translate_key"),
    ];

    $form['google']['google_maps_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#description' => $this->t('Get it at <a target="_blank" href="https://developers.google.com/maps/documentation/embed/get-api-key">https://developers.google.com/maps/documentation/embed/get-api-key</a>'),
      '#default_value' => $this->boSettings->getBoSetting("google_maps_key"),
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
      '#default_value' => $this->boSettings->getBoSetting("load_bootstrap"),
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
