<?php

namespace Drupal\bo\Form;

use Drupal\bo\Ajax\RefreshPageCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoResetCollectionOptionsForm.
 *
 * @package Drupal\bo\Form
 */
class BoResetCollectionOptionsForm extends ConfigFormBase {

  private BoSettings $boSettings;

  public function __construct(ConfigFactoryInterface $config_factory, BoSettings $boSettings) {
    parent::__construct($config_factory);
    $this->boSettings = $boSettings;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('bo.settings')
    );
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
    return 'reset_collection_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $form["info"]["#markup"] = $this->t("Reset this overview setting to the default collection settings.");

    $form["actions"]["submit"]["#name"] = 'submit';
    $form["actions"]["submit"]["#value"] = $this->t("Reset");
    $form["actions"]["submit"]["#ajax"]['callback'] = [
      $this,
      'afterSubmitCallback',
    ];

    $form["actions"]["cancel"]["#type"] = "submit";
    $form["actions"]["cancel"]["#value"] = $this->t("Cancel");
    $form["actions"]["cancel"]["#ajax"]['callback'] = [
      $this,
      'afterCancelFallback',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement['#name'] == 'submit') {
      $collection_id = \Drupal::request()->query->get('collection_id');

      $collection_settings = $this->boSettings->getCollections();
      if (isset($collection_settings[$collection_id])) {
        unset($collection_settings[$collection_id]);
      }

      $this->boSettings->replaceSettings($collection_settings, "collection");

      Cache::invalidateTags(["bo:collection:" . $collection_id]);
    }
  }

  /**
   * After reset submit callback.
   *
   * @param array $form
   * @param FormStateInterface $formState
   * @return AjaxResponse
   */
  public function afterSubmitCallback(array $form, FormStateInterface $formState) {
    $response = new AjaxResponse();
    $response->addCommand(new RefreshPageCommand());
    return $response;
  }

  /**
   * After reset cancel callback.
   *
   * @param array $form
   * @param FormStateInterface $formState
   * @return AjaxResponse
   */
  public function afterCancelFallback(array $form, FormStateInterface $formState) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('.bo-dialog .ui-dialog-content'));
    return $response;
  }

}
