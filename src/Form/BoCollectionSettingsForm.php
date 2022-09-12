<?php

namespace Drupal\bo\Form;

use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoCollection;
use Drupal\bo\Service\BoSettings;
use Drupal\bo\Ajax\RefreshPageCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
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
        $reset_to_collection_label = '';
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

    // Bundle groups in fieldsets.
    $form['bundles'] = [
      '#type' => 'fieldset',
      '#title' => 'BO ' . $this->t('elementen'),
      '#prefix' => $reset_markup,
      '#attributes' => [
        'class' => [
          'bundles',
        ],
      ],
      '#description' => $this->t('Select what BO elements are allowed for this collection'),
      '#collapsibl' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
    ];

    $bundles = $this->boBundle->getSortedBundles();
    foreach ($bundles as $group => $grouped_bundles) {
      $weight = 0;
      if ($group != "") {
        $form['bundles'][$group] = [
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
        $form['bundles'][$g][$bundle->id()] = [
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

    $form['bo_options'] = [
      '#type' => 'fieldset',
      '#title' => 'Collection Options',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
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
    $collection_views = $this->boCollection->getCollectionViews();
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
    $default_value_max_count = $this->boCollection->getCollectionMaxElementCount($this->collection_id);

    $form["bo_options"]["max_element_count"] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum item count'),
      '#description' => $this->t('0 = Unlimited items'),
      '#weight' => $weight,
      '#default_value' => $default_value_max_count,
    ];

    // bo_options > max_element_count.
    $default_reload = $this->boCollection->getCollectionReload($this->collection_id);

    $form["bo_options"]["reload"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload'),
      '#description' => $this->t('Reload after add/insert/delete'),
      '#weight' => $weight,
      '#default_value' => $default_reload,
    ];

    $form['#attached']['library'] = [
      'bo/bo_collection_settings',
      'bo/bo_ajax_commands',
    ];

    $form['actions']['submit']['#ajax']['callback'] = [
      $this,
      'afterSubmitCallback',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($this->via == 'view') {
      foreach ($form_state->getValue("bundles") as $bundles) {
        foreach ($bundles as $element => $value) {
          $settings["collection"][$this->collection_id]["bundles"][$element] = $value;
        }
      }

      $settings["collection"][$this->collection_id]["options"]["max_element_count"] = $form_state->getValue("bo_options")['max_element_count'];
      $settings["collection"][$this->collection_id]["options"]["reload"] = $form_state->getValue("bo_options")['reload'];
      $settings["collection"][$this->collection_id]["options"]["label"] = $form_state->getValue("bo_options")['label'];

      if ($this->collection_id != "" && $this->collection_id != "-") {
        $settings["collection"][$this->collection_id]["options"]["specific_view"] = $form_state->getValue("bo_options")['specific_view'];
      }
      $this->boSettings->setSettings($settings);
    }

    if ($this->via == "bundle") {
      if ($bundle = $this->boBundle->getBundle($this->bundle_id)) {
        $collection_bundles = [];
        foreach ($form_state->getValue("bundles") as $bundles) {
          foreach ($bundles as $element => $value) {
            $collection_bundles[$element] = $value;
          }
        }

        $bundle->setCollectionBundles($collection_bundles);
        $bundle->setCollectionOptions([
          'label' => $form_state->getValue("bo_options")['label'],
          'max_element_count' => $form_state->getValue("bo_options")['max_element_count'],
          'specific_view' => $form_state->getValue("bo_options")['specific_view'],
        ]);
        $bundle->save();
      }
    }

    Cache::invalidateTags(["bo:settings"]);
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
    if ($this->via == 'view') {
      $response->addCommand(new RefreshPageCommand());
    }
    else {
      $response->addCommand(new CloseDialogCommand('.bo-dialog .ui-dialog-content'));
    }
    return $response;
  }

}
