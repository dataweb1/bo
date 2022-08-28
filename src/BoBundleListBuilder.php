<?php

namespace Drupal\bo;

use Drupal\bo\Entity\BoBundleInterface;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BoBundleElementListBuilder.
 */
class BoBundleListBuilder extends ConfigEntityListBuilder implements FormInterface {

  protected $weightKey;
  protected $groups;
  protected $type;

  /**
   * The theme containing the blocks.
   *
   * @var string
   */
  protected $theme;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  private BoSettings $boSettings;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ThemeManagerInterface $theme_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger, \Drupal\bo\Service\BoSettings $boSettings) {
    parent::__construct($entity_type, $storage);

    $this->themeManager = $theme_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->boSettings = $boSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
          $entity_type,
          $container->get('entity_type.manager')->getStorage($entity_type->id()),
          $container->get('theme.manager'),
          $container->get('form_builder'),
          $container->get('messenger'),
          $container->get('bo.settings')
      );
  }

  /**
   * {@inheritdoc}
   *
   * @param string|null $theme
   *   (optional) The theme to display the blocks for. If NULL, the current
   *   theme will be used.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The block list as a renderable array.
   */
  public function render($theme = NULL, Request $request = NULL) {
    $this->request = $request;
    $this->theme = $theme;

    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name == "bo.entity.bundle.content_list") {
      $this->type = "content";
    }
    if ($route_name == "bo.entity.bundle.element_list") {
      $this->type = "element";
    }

    return $this->formBuilder->getForm($this);
  }

  /**
   *
   */
  public function getFormId() {
    return "bo_bundle_list_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['id'] = $this->t('Machine name');
    $header['default'] = $this->t('Default');
    $header['internal_title'] = $this->t('Internal title');
    $header['override_title_label'] = $this->t('Override title label');
    $header['icon'] = $this->t('Icon');
    $header['collection'] = $this->t('Collection');
    $header['weight'] = $this->t('Weight');
    $header['operations'] = $this->t('Operations');
    // + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(BoBundleInterface $entity) {

    $bundle = $this->boSettings->getBundles($entity->id());

    $row['label']['#markup'] = $this->t($entity->label());
    $row['description']['#markup'] = $entity->getDescription();

    $row['id']['#markup'] = "<div class='bundle-machine-name'>" . $entity->id() . "</div>";

    $row['default'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle["default"] ?? FALSE,
      "#name" => 'default[' . $entity->id() . ']',
        // '#checked' => (bool)$bundle["default"],
      '#attributes' => ["class" => ["bo-bundle-checkbox-default"]],
    ];

    $row['internal_title'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle["internal_title"] ?? FALSE,
      "#name" => 'internal_title[' . $entity->id() . ']',
        // '#checked' => (bool)$bundle["internal_title"],
      '#attributes' => ["class" => ["bo-bundle-checkbox-internal-title"]],
    ];

    $row['override_title_label'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t("Label"),
      "#name" => 'override_title_label[' . $entity->id() . ']',
      '#value' => $bundle["override_title_label"] ?? '',
      '#attributes' => ["class" => ["bo-bundle-text-override-title-label"]],
    ];

    $row['icon'] = [
      '#type' => 'textfield',
      "#name" => 'icon[' . $entity->id() . ']',
      "#required" => 0,
      '#value' => $bundle["icon"] ?? '',
      "#size" => 10,
      '#attributes' => ["class" => ["bo-bundle-text-icon"]],
    ];

    $url = Url::fromRoute('bo.collection_settings_form', [
      'overview_or_collection' => 'collection',
      'collection_machine_name' => $entity->id(),
    ]);
    $collection_settings_link = Link::fromTextAndUrl('Settings', $url)->toString();

    $row['collection'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle["collection"] ?? FALSE,
      "#name" => 'collection[' . $entity->id() . ']',
      '#suffix' => '<div class="right-to-checkbox bo-bundle-collection">' . $collection_settings_link . '</div>',
      '#attributes' => ["class" => ["bo-bundle-checkbox-collection"]],
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBundlesForm() {
    $entities = $this->load();

    foreach ($entities as $entity_id => $entity) {

      if ($entity->getType() == $this->type) {
        $bundle_settings = $this->boSettings->getBundles($entity_id);
        $group = $entity->getGroup();
        if ($group == "" || $group == "n-a") {
          $group = "_empty";
        }

        $bundles[$group][$entity_id] = [
          'label' => $entity->label(),
          'entity_id' => $entity_id,
          'weight' => $entity->getWeight(),
          'entity' => $entity,
          'settings' => $bundle_settings,
          'group' => (string) $group,
        ];
      }
    }

    $form = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#attributes' => [
        'id' => 'bundles',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // group get an unique weight.
    $weight_delta = round(count($entities) / 2);

    $groups = $this->boSettings->getBoBundleGroups();
    $groups_options = ["_empty" => ""];
    foreach ($groups as $group_machine_name => $group_label) {
      $groups_options[$group_machine_name] = $group_label;
    }

    $groups = ["_empty" => ""] + $groups;

    foreach ($groups as $group_machine_name => $group_label) {
      // kint($group_machine_name);
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'bundle-group-select',
        'subgroup' => 'bundle-group-' . $group_machine_name,
        'hidden' => FALSE,
      ];

      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'bundle-weight',
        'subgroup' => 'bundle-weight-' . $group_machine_name,
      ];

      $form['group-' . $group_machine_name] = [
        '#attributes' => [
          'class' => ['bundle-title', 'bundle-title-' . $group_machine_name],
          'no_striping' => TRUE,
        ],
      ];

      $form['group-' . $group_machine_name]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'group-title__action'],
          ],
        ],
        '#prefix' => $group_label,
        '#wrapper_attributes' => [
          'colspan' => 10,
        ],
      ];

      $form['group-' . $group_machine_name . '-message'] = [
        '#attributes' => [
          'class' => [
            'group-message',
            'group-' . $group_machine_name . '-message',
            empty($bundles[$group_machine_name]) ? 'group-empty' : 'group-populated',
          ],
        ],
      ];
      $form['group-' . $group_machine_name . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No bundles in this group yet.') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 10,
        ],
      ];

      if (isset($bundles[$group_machine_name])) {
        foreach ($bundles[$group_machine_name] as $bundle) {
          $entity_id = $bundle["entity_id"];
          $entity = $bundle["entity"];

          $form[$entity_id] = $this->buildRow($entity);

          $form[$entity_id]['#attributes'] = ['class' => ['draggable']];

          $form[$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $entity->getWeight(),
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @bundle', ['@bundle' => $bundle['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => [
                'bundle-weight',
                'bundle-weight-' . $group_machine_name,
              ],
            ],
          ];

          $form[$entity_id]['label']['group'] = [
            '#type' => 'select',
            '#default_value' => $group_machine_name,
            '#required' => TRUE,
            '#title' => $this->t('Group for @bundle bundle', ['@bundle' => $bundle['label']]),
            '#title_display' => 'invisible',
            '#options' => $groups_options,
            '#attributes' => [
              'class' => [
                'bundle-group-select',
                'bundle-group-' . $group_machine_name,
              ],
            ],
            '#parents' => ['bundles', $entity_id, 'group'],
          ];

          $form[$entity_id]['operations'] = $this->buildOperations($entity);

        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attributes']['class'][] = 'clearfix';

    $form['#attached']['library'][] = "bo/bo_init";
    $form['#attached']['library'][] = "bo/bo_bundle";
    $form['#attached']['library'][] = "bo/bo_bundle_list";

    $form['bundles'] = $this->buildBundlesForm();

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save bundles'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entities = $this->storage->loadMultiple(array_keys($form_state->getValue('bundles')));

    /** @var \Drupal\bo\Entity\BoBundleInterface $entity */
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(['bundles', $entity_id]);

      $group = $entity_values['group'];
      if ($group == "_empty") {
        $group = "";
      }

      $entity->setWeight($entity_values['weight']);
      $entity->setGroup($group);
      $entity->save();

    }

    $groups_with_bundles = [];
    $all_entities = $this->load();
    foreach ($all_entities as $entity_id => $entity) {
      $group = $entity->getGroup();

      if ($group != "") {
        $groups_with_bundles[] = $group;
      }
    }
    $this->boSettings->cleanupBundleGroups($groups_with_bundles);

    $this->messenger->addStatus($this->t('The bundles settings have been updated.'));

    $input = $form_state->getUserInput();

    $bundle_settings = $this->boSettings->getBundles();

    $bundles = $form_state->getValue('bundles');
    foreach ($bundles as $bundle_machine_name => $bundle) {
      $bundle_settings[$bundle_machine_name]["label"] = $bundle["label"] ?? '';
      $bundle_settings[$bundle_machine_name]["default"] = $input["default"][$bundle_machine_name] ?? FALSE;
      $bundle_settings[$bundle_machine_name]["internal_title"] = $input["internal_title"][$bundle_machine_name] ?? FALSE;
      $bundle_settings[$bundle_machine_name]["collection"] = $input["collection"][$bundle_machine_name] ?? FALSE;
      $bundle_settings[$bundle_machine_name]["icon"] = $input["icon"][$bundle_machine_name];
      $bundle_settings[$bundle_machine_name]["override_title_label"] = $input["override_title_label"][$bundle_machine_name];
    }

    $this->boSettings->replaceSettings($bundle_settings, "bundles");
  }

}
