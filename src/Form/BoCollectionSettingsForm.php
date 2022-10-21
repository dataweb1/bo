<?php

namespace Drupal\bo\Form;

use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoSettings;
use Drupal\bo\Ajax\RefreshPageCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoCollectionSettingsForm.
 *
 * @package Drupal\bo\Form
 */
class BoCollectionSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\bo\Service\BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var \Drupal\bo\Service\BoBundle
   */
  private BoBundle $boBundle;

  /**
   * @var mixed
   */
  private $collection_id;
  /**
   * @var mixed
   */
  private $bundle_id;
  /**
   * @var mixed
   */
  private $via;
  /**
   * @var array|mixed|string
   */
  private $current_options;

  /**
   * @var \Drupal\bo\Service\BoCollection
   */
  private BoCollection $boCollection;

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, BoSettings $boSettings, BoBundle $boBundle, BoCollection $boCollection) {
    parent::__construct($config_factory);
    $this->boSettings = $boSettings;
    $this->boBundle = $boBundle;
    $this->boCollection = $boCollection;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('bo.settings'),
      $container->get('bo.bundle'),
      $container->get('bo.collection'),
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
    return 'collection_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $this->collection_id = \Drupal::request()->query->get('collection_id');
    $this->bundle_id = \Drupal::request()->query->get('bundle_id');
    $this->via = \Drupal::request()->query->get('via');

    $reset_markup = '';
    if ($this->via == 'view') {
      $this->current_options = $this->boSettings->getCollectionOptions($this->collection_id);
      if ($this->boSettings->getCollection($this->collection_id)) {
        $reset_to_url = Url::fromRoute('bo.reset_collection_options_form', [
          'collection_id' => $this->collection_id,
        ]);
        $reset_to_url->setOptions([
          'attributes' => [
            'class' => [
              'button',
              'button--small',
            ],
          ],
        ]);

        $reset_to_link = Link::fromTextAndUrl($this->t('here'), $reset_to_url);
        if (intval($this->collection_id) > 0) {
          $reset_to_collection_label = $this->boCollection->getCollectionLabel($this->collection_id);
        }
        else {
          // A view, so reset to default.
          $reset_to_collection_label = '';
        }

        if ($reset_to_collection_label != '') {
          $reset_markup = Markup::create(t('Reset to the %reset_to bundle collection settings', [
            "%reset_to" => $reset_to_collection_label,
          ]) . " " . $reset_to_link->toString());
        }
        else {
          $reset_markup = Markup::create(t('Reset to the default bundle settings') . " " . $reset_to_link->toString());
        }
      }
    }

    if ($this->via == 'bundle') {
      if ($bundle = $this->boBundle->getBundle($this->bundle_id)) {
        $this->current_options = $this->boBundle->getBundleCollectionOptions($bundle);
      }
    }

    $form['reset'] = [
      '#type' => 'markup',
      '#markup' => $reset_markup,
      '#weight' => 0,
    ];

    $form['bundles']['#type'] = "fieldset";
    $form['bundles']['#tree'] = TRUE;
    $form['bundles']['#weight'] = 1;
    $form['bundles']['#title'] = $this->t('Collection elements');
    $form['bundles']['#description'] = $this->t('Select what BO elements are allowed for this collection');

    $bundle_types = $this->boSettings->getBundleTypes();

    // Bundle groups in fieldsets.
    $bundles = $this->boBundle->getSortedBundles();
    foreach ($bundles as $type => $typed_bundles) {

      $form['bundles']['check_uncheck_' . $type] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check/Uncheck all'),
        '#tree' => FALSE,
        '#attributes' => [
          'check-uncheck-type' => str_replace('_', '-', $type),
          'class' => [
            'check-uncheck-all',
          ],
        ],
      ];

      $form['bundles'][$type] = [
        '#type' => 'details',
        '#title' => 'BO ' . $this->t($bundle_types[$type]['plural']),
        '#attributes' => [
          'class' => [
            'bundles',
            str_replace('_', '-', $type) . '-bundles',
          ],
        ],
        '#open' => TRUE,
      ];
      foreach ($typed_bundles as $group => $grouped_bundles) {
        $weight = 0;
        if ($group != "") {
          $form['bundles'][$type][$group] = [
            '#type' => 'container',
            '#markup' => "<div class='group-title'>" . $group . "</div>",
            '#attributes' => [
              "class" => [
                'group-wrapper',
              ],
            ],

          ];
        }

        $g = $group;
        if ($group == "") {
          $g = "_empty";
        }

        /** @var \Drupal\bo\Entity\BoBundle $bundle */
        foreach ($grouped_bundles as $bundle) {
          if ($this->via == 'view') {
            $default_value = $this->boCollection->isEnabledBundle($this->collection_id, $bundle);
          }
          if ($this->via == 'bundle') {
            $default_value = $this->boCollection->isEnabledBundle($this->bundle_id, $bundle);
          }
          $form['bundles'][$type][$g][$bundle->id()] = [
            '#type' => 'checkbox',
            '#title' => '<span class="' . $bundle->getIcon() . '"></span>' . $this->t($bundle->label()),
            '#weight' => $weight,
            '#attributes' => [
              'checkbox-group' => 'bo-settings-bundle',
              'toggle-fieldset' => 'fieldset-bo-settings-bo-' . str_replace('_', '-', $bundle->id() . '-types'),
            ],
            '#default_value' => $default_value,
          ];

          $weight++;
        }
      }
    }

    $form['bo_options'] = [
      '#type' => 'fieldset',
      '#title' => 'Collection Options',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 3,
    ];

    // bo_options > label.
    $default_value_label = $this->current_options["label"] ?? '';
    if ($this->via == 'view' && $default_value_label == '') {
      $default_value_label = $this->boCollection->getCollectionLabel($this->collection_id);
    }

    $form["bo_options"]["label"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label is shown next to the collection operation buttons'),
      '#default_value' => $default_value_label,
    ];

    // bo_options > specific_view.
    $default_value_specific_view = $this->current_options["specific_view"] ?? '';
    if ($default_value_specific_view == '') {
      $default_value_specific_view = implode('__', $this->boCollection->getCollectionView($this->collection_id));
    }

    // Get the BO views options.
    $specific_view_options = [];
    $collection_views = $this->boCollection->getBoViews();
    foreach ($collection_views as $view_id => $view) {
      foreach ($view as $display) {
        $specific_view_options[$display["view_label"]][$view_id . "__" . $display["display_id"]] = $display["display_title"];
      }
    }

    if ($this->via == 'bundle' || ($this->via == 'view' && intval($this->collection_id) > 0)) {
      $form["bo_options"]["specific_view"] = [
        '#type' => 'select',
        '#title' => $this->t('BO view to use'),
        '#required' => TRUE,
        '#options' => $specific_view_options,
        '#default_value' => $default_value_specific_view,
      ];
    }
    else {
      $form["bo_options"]["specific_view"] = [
        '#type' => 'hidden',
        '#default_value' => $default_value_specific_view,
      ];
    }

    // bo_options > max_element_count.
    $default_max_count = $this->current_options['max_element_count'] ?? '';
    if ($this->via == 'view' && (string) $default_max_count == '') {
      $default_max_count = $this->boCollection->getCollectionMaxElementCount($this->collection_id);
    }
    $form["bo_options"]["max_element_count"] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum item count'),
      '#description' => $this->t('0 = Unlimited items'),
      '#weight' => $weight,
      '#default_value' => $default_max_count,
    ];

    // Collection options > max_element_count.
    $default_reload = $this->current_options['reload'] ?? '';
    if ($this->via == 'view' && (string) $default_reload == '') {
      $default_reload = $this->boCollection->getCollectionReload($this->collection_id);
    }
    $form["bo_options"]["reload"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload'),
      '#description' => $this->t('Reload after add/insert/delete instead of an AJAX refresh.'),
      '#weight' => $weight,
      '#default_value' => $default_reload,
    ];

    // Collection options > header_operations_overlap.
    $default_header_operations_overlap = $this->current_options['header_operations_overlap'] ?? '';
    if ($this->via == 'view' && (string) $default_header_operations_overlap == '') {
      $default_header_operations_overlap = $this->boCollection->getHeaderOperationsOverlap($this->collection_id);
    }
    $form["bo_options"]["header_operations_overlap"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Header operations overlap?'),
      '#description' => $this->t('Show header operations on top of the content instead of above.'),
      '#weight' => $weight,
      '#default_value' => $default_header_operations_overlap,
    ];

    // Collection options > header_operations_overlap.
    $default_operations_position = $this->current_options['operations_position'] ?? '';
    if ((string) $default_operations_position == '') {
      $default_operations_position = $this->boCollection->getOperationsPosition($this->collection_id);
    }
    $form["bo_options"]["operations_position"] = [
      '#type' => 'radios',
      '#title' => $this->t('Position of operations links'),
      '#options' => ['top' => $this->t('Top'), 'bottom' => $this->t('Bottom')],
      '#description' => $this->t('Position where to put the operations links, on top by default.'),
      '#weight' => $weight,
      '#default_value' => $default_operations_position,
    ];

    // Collection options > insert_element_button.
    $default_disable_insert = $this->current_options['disable_insert'] ?? '';
    if ($this->via == 'view' && (string) $default_disable_insert == '') {
      $default_disable_insert = $this->boCollection->getDisableInsert($this->collection_id);
    }
    $form["bo_options"]["disable_insert"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Insert element?'),
      '#description' => $this->t('Disable insert element for this collection?'),
      '#weight' => $weight,
      '#default_value' => $default_disable_insert,
    ];

    // Collection options > disable_bundle_label.
    $default_disable_bundle_label = $this->current_options['disable_bundle_label'] ?? '';
    if ($this->via == 'view' && (string) $default_disable_bundle_label == '') {
      $default_disable_bundle_label = $this->boCollection->getDisableBundleLabel($this->collection_id);
    }

    $form["bo_options"]["disable_bundle_label"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable element label?'),
      '#description' => $this->t('Disable element label for collection items?'),
      '#weight' => $weight,
      '#default_value' => $default_disable_bundle_label,
    ];

    // Collection options > ignore_current_path.
    $default_ignore_current_path = $this->current_options['ignore_current_path'] ?? '';
    if ($this->via == 'view' && (string) $default_ignore_current_path == '') {
      $default_ignore_current_path = $this->boCollection->getCollectionIgnoreCurrentPath($this->collection_id);
    }
    $form["bo_options"]["ignore_current_path"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore current path filter'),
      '#description' => $this->t('Show all collection items regardless of the current path'),
      '#weight' => $weight,
      '#default_value' => $default_ignore_current_path,
    ];

    $form['#attached']['library'] = [
      'bo/bo_collection_settings',
      'bo/bo_ajax_commands',
    ];

    $form['actions']['submit']['#ajax']['callback'] = [
      $this,
      'afterSubmitCallback',
    ];

    $form['#prefix'] = '<div id="form_wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($this->via == 'view') {
      foreach ($form_state->getValue("bundles") as $type_id => $type_bundles) {
        foreach ($type_bundles as $group_id => $group_bundles) {
          if ($group_id != '_all') {
            foreach ($group_bundles as $bundle_id => $checked) {
              $settings["collection"][$this->collection_id]["bundles"][$bundle_id] = $checked;
            }
          }
        }
      }

      $settings["collection"][$this->collection_id]["options"]["max_element_count"] = $form_state->getValue("bo_options")['max_element_count'];
      $settings["collection"][$this->collection_id]["options"]["reload"] = $form_state->getValue("bo_options")['reload'];
      $settings["collection"][$this->collection_id]["options"]["header_operations_overlap"] = $form_state->getValue("bo_options")['header_operations_overlap'];
      $settings["collection"][$this->collection_id]["options"]["operations_position"] = $form_state->getValue("bo_options")['operations_position'];
      $settings["collection"][$this->collection_id]["options"]["disable_insert"] = $form_state->getValue("bo_options")['disable_insert'];
      $settings["collection"][$this->collection_id]["options"]["disable_bundle_label"] = $form_state->getValue("bo_options")['disable_bundle_label'];
      $settings["collection"][$this->collection_id]["options"]["ignore_current_path"] = $form_state->getValue("bo_options")['ignore_current_path'];
      $settings["collection"][$this->collection_id]["options"]["label"] = $form_state->getValue("bo_options")['label'];

      if ($this->collection_id != "" && $this->collection_id != "-") {
        $settings["collection"][$this->collection_id]["options"]["specific_view"] = $form_state->getValue("bo_options")['specific_view'];
      }
      $this->boSettings->setSettings($settings);
    }

    if ($this->via == "bundle") {
      if ($bundle = $this->boBundle->getBundle($this->bundle_id)) {
        $collection_bundles = [];
        foreach ($form_state->getValue("bundles") as $type_id => $type_bundles) {
          foreach ($type_bundles as $group_id => $group_bundles) {
            if ($group_id != '_all') {
              foreach ($group_bundles as $bundle_id => $checked) {
                $collection_bundles[$bundle_id] = $checked;
              }
            }
          }
        }

        $bundle->setCollectionBundles($collection_bundles);
        $bundle->setCollectionOptions([
          'label' => $form_state->getValue("bo_options")['label'],
          'max_element_count' => $form_state->getValue("bo_options")['max_element_count'],
          'reload' => $form_state->getValue("bo_options")['reload'],
          'header_operations_overlap' => $form_state->getValue("bo_options")['header_operations_overlap'],
          'operations_position' => $form_state->getValue("bo_options")['operations_position'],
          'specific_view' => $form_state->getValue("bo_options")['specific_view'],
          'disable_bundle_label' => $form_state->getValue("bo_options")['disable_bundle_label'],
          'ignore_current_path' => $form_state->getValue("bo_options")['ignore_current_path'],
          'disable_insert' => $form_state->getValue("bo_options")['disable_insert'],
        ]);
        $bundle->save();
      }
    }

    Cache::invalidateTags(["bo:collection:" . $this->collection_id]);
  }

  /**
   * After collection settings submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function afterSubmitCallback(array $form, FormStateInterface $formState) {
    $response = new AjaxResponse();
    $messages = \Drupal::messenger()->all();
    if (!isset($messages['error'])) {
      if ($this->via == 'view') {
        $response->addCommand(new RefreshPageCommand());
      }
      else {
        $response->addCommand(new CloseDialogCommand('.bo-dialog .ui-dialog-content'));
      }
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

}
