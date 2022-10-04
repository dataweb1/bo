<?php

namespace Drupal\bo\Form;

use Drupal\bo\Ajax\RefreshViewCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a BO entity.
 */
class BoEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form["actions"]["submit"]["#name"] = 'submit';
    $form["actions"]["submit"]["#attributes"]['class'][] = 'button--danger';
    $form["actions"]["submit"]["#ajax"]['callback'] = [
      $this,
      'afterSubmitFallback',
    ];

    $form["actions"]["cancel"]["#type"] = 'submit';
    $form["actions"]["cancel"]["#value"] = $this->t("Cancel");
    $form["actions"]["cancel"]['#name'] = 'cancel';
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
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * After submit callback.
   *
   * @param array $form
   * @param FormStateInterface $formState
   * @return AjaxResponse
   */
  public function afterSubmitFallback(array $form, FormStateInterface $formState) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('.bo-dialog .ui-dialog-content'));
    $response->addCommand(new RefreshViewCommand(\Drupal::request()->query->get('view_dom_id')));
    return $response;
  }

  /**
   * After cancel callback.
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
