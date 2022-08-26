<?php

namespace Drupal\bo\Form;

use Drupal\bo\Ajax\RefreshViewCommand;
use Drupal\bo\Service\BoView;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bo\Entity\BoEntity;
use Drupal\views\Views;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\bo\Ajax\SlideCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoReorderForm.
 */
class BoReorderForm extends FormBase {

  protected $view_dom_id;
  protected $display_id;
  protected $collection_id;
  protected $to_path;
  protected static $instance_id;
  protected static $instance_args;
  protected $args;

  /**
   * @var BoView
   */
  private BoView $boView;

  public function __construct(BoView $boView) {
    $this->boView = $boView;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.view')
    );
  }

  /**
   *
   */
  public function getFormId() {
    return "bo_reorder_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = NULL) {

    // For refreshing view after reorder.
    $view_dom_id = $args["view_dom_id"];

    // Prepare the view.
    $display_id = $args["display_id"];
    $display_id_parts = explode('__', $display_id);
    $to_path = $args["to_path"];
    $collection_id = $args["collection_id"];

    $view = $this->boView->prepareBoView($display_id_parts[0], $display_id_parts[1], $collection_id, $to_path);

    // Process the result.
    $weight = 0;
    foreach ($view->result as $row) {
      $bo_entity = $row->_entity;
      $items[$bo_entity->getId()] = [
        'id' => $bo_entity->getId(),
        'title' => $bo_entity->getTitle(),
        'bundle' => $bo_entity->getBundle(),
        'weight' => $weight,
      ];
      $weight++;
    }

    $group_class = 'group-order-weight';

    $form['#prefix'] = '<div id="bo_reorder_form_wrapper__' . $args["display_id"] . "__" . $args["collection_id"] . '" class="bo-reorder-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form["title"] = [
      '#type' => 'markup',
      '#markup' => '<h3><i class=\"fas fa-sort\"></i> ' . $this->t('Reorder') . '</h3>',
    ];

    if (!isset($form["view_dom_id"])) {
      $form["view_dom_id"] = [
        '#type' => 'hidden',
        '#value' => $view_dom_id,
      ];
    }

    $form["message"] = [
      '#type' => 'markup',
      '#markup' => '<div class="result-message"></div>',
    ];

    // Build table.
    $form['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Element'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No items.'),
      '#tableselect' => FALSE,
      '#attributes' => [
        'id' => 'bo_table_reorder_' . $display_id . '__' . $collection_id,
        'class' => [
          'bo-table-reorder',
        ],
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $group_class,
        ],
      ],
      "#prefix" => '<div class="bo-table-reorder-wrapper">',
      "#suffix" => '</div>',
    ];

    $form["message"] = [
      '#type' => 'markup',
      '#markup' => '<div class="result-message"></div>',
    ];

    // Build rows.
    foreach ($items as $key => $value) {
      $form['items'][$key]['#attributes']['class'][] = 'draggable';
      $form['items'][$key]['#weight'] = $value['weight'];

      // Title col.
      $form['items'][$key]['title'] = [
        '#plain_text' => $value['title'],
      ];

      // Title col.
      $form['items'][$key]['type'] = [
        '#plain_text' => $value['bundle'],
      ];

      // Weight col.
      $form['items'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $value['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $value['weight'],
        '#attributes' => ['class' => [$group_class]],
      ];
    }

    // Form action buttons.
    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => [
          'bo-reorder--submit',
        ],
      ],
      '#submit' => ['::submitForm'],
      '#ajax' => [
        'wrapper' => 'bo_reorder_form_wrapper__' . $display_id . "__" . $collection_id,
        'callback' => [
          $this,
          'afterReorderCallback',
        ],
        'options' => [
          'query' => [
            'ajax_form' => 1
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $weight = 0;
    foreach ($form_state->getValues()["items"] as $key => $value) {
      $entity = BoEntity::load($key);
      $entity->setWeight($weight);
      $entity->save();
      $weight++;

    }
    $form_state->setCached(FALSE);
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback after reorder.
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function afterReorderCallback(array &$form, FormStateInterface $form_state) {

    $input = $form_state->getUserInput();
    $view_dom_id = $input["view_dom_id"];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.tabledrag-changed-warning', ''));

    \Drupal::messenger()->addMessage($this->t('Order has changed successfully'), 'status', TRUE);

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => \Drupal::messenger()->all(),
    ];

    $messages = \Drupal::service('renderer')->render($message);

    $response->addCommand(new HtmlCommand('.result-message', $messages));
    $response->addCommand(new HtmlCommand('#bo_operations_pane_' . $view_dom_id, $form));
    $response->addCommand(new SlideCommand("reorder", $view_dom_id, 0));
    $response->addCommand(new RefreshViewCommand($view_dom_id));

    return $response;

  }

}
