<?php

namespace Drupal\bo\Form;

use Drupal\bo\Ajax\RefreshViewCommand;
use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoSettings;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bo\Entity\BoEntity;
use Drupal\Core\Render\Markup;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoEntityForm.
 */
class BoEntityForm extends ContentEntityForm {

  /**
   * @var BoBundle
   */
  private BoBundle $boBundle;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;


  /**
   *
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, BoBundle $boBundle, BoCollection $boCollection) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->boBundle = $boBundle;
    $this->boCollection = $boCollection;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('bo.bundle'),
      $container->get('bo.collection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Needed for working with Media browser. BoBundle / BoCollection service is not set for whatever reason.
    if (!isset($this->boBundle)) {
      $this->boBundle = \Drupal::service('bo.bundle');
    }

    if (!isset($this->boCollection)) {
      $this->boCollection = \Drupal::service('bo.collection');
    }

    /** @var \Drupal\bo\Entity\BoBundle $entityBundle */
    $entityBundle = $this->boBundle->getBundle($this->entity->getBundle());

    $to_path = \Drupal::request()->query->get('to_path');
    $collection_id = \Drupal::request()->query->get('collection_id');

    $current_route_name = \Drupal::routeMatch()->getRouteName();
    if ($current_route_name == "entity.bo.add_form" || $current_route_name == "entity.bo.insert_form") {
      $form["to_path"]["widget"][0]["value"]["#default_value"] = $to_path;
      $form["collection_id"]["widget"][0]["value"]["#default_value"] = $collection_id;
    }

    if ($current_route_name == "entity.bo.add_form") {
      // If maxElementCount is 1 there is no top or bottom.
      if ($this->boCollection->getCollectionMaxElementCount($collection_id) <> 1) {
        $form['position'] = [
          '#type' => 'radios',
          '#title' => $this->t('Insert position'),
          '#default_value' => 'bottom',
          '#options' => [
            "top" => $this->t("Top"),
            "bottom" => $this->t('Bottom'),
          ],
          '#description' => $this->t('Where to add the element in the overview'),
          '#attributes' => ["class" => ["radio-button-group"]],
        ];
      }
    }

    /*
    if ($current_route_name == "entity.bo.edit_form") {
      $relatedBundles = $entityBundle->getRelatedBundles();
      if (count($relatedBundles) > 0) {
        $relatedBundleOptions = [''];
        foreach($relatedBundles as $relatedBundle) {
          $relatedBundleEntity = $this->boBundle->getBundle($relatedBundle);
          $relatedBundleOptions[$relatedBundle] = $relatedBundleEntity->label();
        }

        $form['change_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Change to'),
          '#options' => $relatedBundleOptions,
          '#ajax' => [
            'callback' => [
              $this,
              'changeBundleAjaxCallback',
            ],
            'wrapper' => 'form_wrapper',
          ],
        ];
      }
    }
    */

    // Internal fields. No need to edit them.
    $form["to_path"]["#access"] = FALSE;
    $form["collection_id"]["#access"] = FALSE;

    // Set some bundle depended settings.
    $internal_title = $entityBundle->getInternalTitle();
    if ($internal_title == 1) {
      $form["title"]["widget"][0]["value"]["#title"] = Markup::create(t("Internal title"));
    }
    else {
      $override_title_label = $entityBundle->getOverrideTitleLabel();
      if ($override_title_label != "") {
        $form["title"]["widget"][0]["value"]["#title"] = Markup::create($override_title_label);
      }
    }

    if ($this->boCollection->getDisableSize($collection_id) == TRUE) {
      $form["size"]["widget"]["#default_value"] = 12;
      $form["size"]["widget"]["#required"] = 0;
      $form["size"]["#access"] = FALSE;
    }

    if (isset($form["actions"]["delete"]["#url"])) {
      $query = $form["actions"]["delete"]["#url"]->getOption("query");
      $form["actions"]["delete"]["#url"]->setOption("query", $query);
    }

    $form['actions']['submit']["#ajax"] = [
      'callback' => [$this, 'boEntitySubmitAjaxCallback'],
    ];

    $form['#prefix'] = '<div id="form_wrapper">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'] = [
      'bo/bo_entity_form',
      'bo/bo_ajax_commands',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $message_params = [
      '%entity_label' => $entity->id(),
      '%content_entity_label' => $entity->getEntityType()->getLabel()->render(),
      '%bundle_label' => $entity->bundle->entity->label(),
    ];

    $status = parent::save($form, $form_state);


    switch ($status) {
      case SAVED_NEW:
        $collection_id = \Drupal::request()->query->get('collection_id');
        $to_path = \Drupal::request()->query->get('to_path');

        // Needed for working with Media browser. BoCollection service is not set for whatever reason.
        if (!isset($this->boCollection)) {
          $this->boCollection = \Drupal::service('bo.collection');
        }

        $view = $this->boCollection->prepareCollectionView($collection_id, $to_path);

        $current_route_name = \Drupal::routeMatch()->getRouteName();
        switch ($current_route_name) {
          case 'entity.bo.insert_form':
            $insert_under_entity_weight = \Drupal::request()->query->get('insert_under_entity_weight');

            $connection = \Drupal::service('database');
            $result = $connection->query("SELECT id, weight FROM {bo} WHERE collection_id = :collection_id AND to_path = :to_path AND weight > :weight ORDER BY weight", [
              ':collection_id' => $collection_id,
              ':to_path' => $to_path,
              ':weight' => (int) $insert_under_entity_weight,
            ]);
            if ($result) {
              while ($row = $result->fetchAssoc()) {
                $new_weight = $row["weight"] + 1;
                $reorder_entity = BoEntity::load($row["id"]);
                $reorder_entity->setWeight($new_weight);
                $reorder_entity->save();
              }

            }

            $entity_weight = $insert_under_entity_weight + 1;
            $entity->setWeight($entity_weight);

            break;

          case 'entity.bo.add_form':

            $position = $form_state->getValue("position");
            if ($position == "bottom" || $position == "") {
              $last_row = end($view->result);
              if (!is_null($last_row->_entity)) {
                $last_weight = $last_row->_entity->getWeight();
                $last_weight++;
                $entity->setWeight($last_weight);
              }
            }
            else {
              $first_row = reset($view->result);
              if (!is_null($first_row->_entity)) {
                $first_weight = $first_row->_entity->getWeight();
                $first_weight--;
                $entity->setWeight($first_weight);
              }
            }

            break;
        }

        \Drupal::messenger()->addMessage($this->t('Created element %bundle_label.', $message_params));

        break;

      default:

        \Drupal::messenger()->addMessage($this->t('Saved element %bundle_label.', $message_params));
    }

    $tags = $entity->getCacheTags();
    if (!empty($tags)) {
      Cache::invalidateTags($tags);
    }
    $entity->save();

  }

  /**
   * Ajax callback after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX return object.
   */
  public function boEntitySubmitAjaxCallback(array $form, FormStateInterface &$formState): AjaxResponse {
    $response = new AjaxResponse();
    $messages = \Drupal::messenger()->all();
    if (!isset($messages['error'])) {
      $response->addCommand(new CloseDialogCommand('.bo-dialog .ui-dialog-content'));
      $response->addCommand(new RefreshViewCommand(\Drupal::request()->query->get('view_dom_id')));
      $response->addCommand(new MessageCommand($messages['status'][0], NULL, ['type' => 'status']));
      \Drupal::messenger()->deleteAll();
    }
    else {
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');

      /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('inline_form_errors')) {
        $response->addCommand(new HtmlCommand('#form_wrapper', $form));
      }

      $messagesElement = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'bo-messages',
        ],
        'messages' => ['#type' => 'status_messages'],
      ];

      $response->addCommand(new RemoveCommand('.bo-messages'));

      $response->addCommand(new PrependCommand(
        '#form_wrapper',
        $renderer->renderRoot($messagesElement)
      ));

    }
    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $formState
   * @return mixed
   */
  public function changeBundleAjaxCallback(array $form, FormStateInterface &$formState) {
    $response = new AjaxResponse();

    return $response;
 }

}
