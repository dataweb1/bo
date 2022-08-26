<?php

namespace Drupal\bo\Form;

use Drupal\bo\Service\BoSettings;
use Drupal\bo\Ajax\RefreshPageCommand;
use Drupal\bo\Service\BoView;
use Drupal\Core\Ajax\AjaxResponse;
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
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var BoView
   */
  private BoView $boView;

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, BoSettings $boSettings, BoView $boView) {
    parent::__construct($config_factory);
    $this->boSettings = $boSettings;
    $this->boView = $boView;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('bo.settings'),
      $container->get('bo.view')
    );
  }

  /**
   *
   */
  protected $overview_or_collection;
  protected $display_id;
  protected $collection_name;
  protected $save_to_collection_name;
  protected $collection_id;
  protected $collection_machine_name;
  protected $current_options;
  protected $bundle_name;

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

    $this->display_id = \Drupal::request()->query->get('display_id');
    $this->collection_id = \Drupal::request()->query->get('collection_id');
    $this->collection_machine_name = \Drupal::request()->query->get('collection_machine_name');
    $this->overview_or_collection = \Drupal::request()->query->get('overview_or_collection');

    $parameters = [
      'overview_or_collection' => $this->overview_or_collection,
      'display_id' => $this->display_id,
      'collection_id' => $this->collection_id,
      'collection_machine_name' => $this->collection_machine_name,
    ];

    $active_collection = $this->boSettings->getActiveCollectionData($parameters);
    $this->collection_name = $active_collection["collection_name"];
    $this->save_to_collection_name = $active_collection["save_to_collection_name"];
    // kint($active_collection);
    $this->current_options = $this->boSettings->getCollectionOptions($this->collection_name);

    if ($active_collection["options_overrided"] == TRUE) {
      $reset_to_url = Url::fromRoute('bo.reset_collection_options_form', [
        'collection_id' => urlencode($this->collection_id),
        'display_id' => urlencode($this->display_id),
      ]);

      $reset_to_link = Link::fromTextAndUrl($this->t('here'), $reset_to_url);
      $reset_markup = Markup::create(t('Reset the element settings to the @reset_to settings', ["@reset_to" => $active_collection["reset_to"]]) . " " . $reset_to_link->toString());
      \Drupal::messenger()->addMessage($reset_markup);
    }

    $form['elements'] = [
      '#type' => 'fieldset',
      '#title' => 'BO ' . $this->t('elementen'),
          // '#prefix' => $reset_markup,
      '#attributes' => [
        'class' => [
          'elements',
        ],
      ],
      '#description' => $this->t('Select what BO elements are allowed for this collection'),
      '#collapsibl' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#weight' => 0,
    ];

    $bundles = $this->boSettings->getSortedBundles();
    foreach ($bundles as $group => $group_bundles) {
      $weight = 0;
      if ($group != "") {
        $form['elements'][$group] = [
          '#type' => 'container',
          '#markup' => "<div class='group-title'>" . $this->boSettings->getBoBundleGroups($group) . "</div>",
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
      foreach ($group_bundles as $group_bundle) {
        $default_value = $this->boSettings->isCollectionElementChecked($this->collection_name, $group_bundle["machine_name"]);
        $form['elements'][$g][$group_bundle["machine_name"]] = [
          '#type' => 'checkbox',
          '#title' => '<span class="' . $group_bundle["icon"] . '"></span>' . $this->t($group_bundle["label"]),
          '#weight' => $weight,
          '#attributes' => [
            'checkbox-group' => 'bo-settings-bundle',
            'toggle-fieldset' => 'fieldset-bo-settings-bo-' . str_replace('_', '-', $group_bundle['machine_name'] . '-types'),
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
    // Int or "-".
    if ($this->overview_or_collection == "overview") {
      $default_value = $this->current_options["label"];

      $form["bo_options"]["label"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#description' => $this->t('The label is shown next to the collection operation buttons'),
        '#default_value' => $default_value,
      ];
    }

    // bo_options > specific_view.
    if ($this->overview_or_collection == "collection" ||
          ($this->overview_or_collection == "overview" && $this->collection_id != "" && $this->collection_id != "-")) {
      $default_value = $this->current_options["specific_view"];

      $bo_views = $this->boView->getBoViews();

      $specific_view_options = ["" => $this->t("default")];
      foreach ($bo_views as $view_id => $view) {

        foreach ($view as $display) {
          $specific_view_options[$display["view_label"]][$view_id . "__" . $display["display_id"]] = $display["display_title"];
        }
      }

      $form["bo_options"]["specific_view"] = [
        '#type' => 'select',
        '#title' => $this->t('Specific view'),
        '#options' => $specific_view_options,
        '#description' => $this->t('When default the most outer view will be used'),
        '#default_value' => $default_value,
      ];

    }

    // bo_options > max_element_count.
    $default_value = $this->current_options["max_element_count"];
    $form["bo_options"]["max_element_count"] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum elements count'),
      '#description' => $this->t('0 = Unlimited elements'),
      '#weight' => $weight,
      '#default_value' => $default_value,
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

    foreach ($form_state->getValue("elements") as $group => $elements) {
      foreach ($elements as $element => $value) {
        $settings["collection"][$this->save_to_collection_name]["elements"][$element] = $value;
      }
    }
    $settings["collection"][$this->save_to_collection_name]["options"]["max_element_count"] = $form_state->getValue("bo_options")['max_element_count'];

    if ($this->overview_or_collection == "collection") {

      $settings["collection"][$this->save_to_collection_name]["options"]["max_element_count"] = $form_state->getValue("bo_options")['max_element_count'];
      $settings["collection"][$this->save_to_collection_name]["options"]["specific_view"] = $form_state->getValue("bo_options")['specific_view'];
    }

    if ($this->overview_or_collection == "overview") {

      $settings["collection"][$this->save_to_collection_name]["options"]["label"] = $form_state->getValue("bo_options")['label'];

      if ($this->collection_id != "" && $this->collection_id != "-") {
        $settings["collection"][$this->save_to_collection_name]["options"]["specific_view"] = $form_state->getValue("bo_options")['specific_view'];
      }
    }

    $this->boSettings->setSettings($settings);

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
    $response->addCommand(new RefreshPageCommand());
    return $response;
  }

}
