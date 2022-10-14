<?php

namespace Drupal\bo;

use Drupal\bo\Entity\BoBundleInterface;
use Drupal\bo\Service\BoBundle;
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
   * @var BoBundle
   */
  private BoBundle $boBundle;

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
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ThemeManagerInterface $theme_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger, BoSettings $boSettings, BoBundle $boBundle) {
    parent::__construct($entity_type, $storage);
    $this->themeManager = $theme_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->boSettings = $boSettings;
    $this->boBundle = $boBundle;
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
          $container->get('bo.settings'),
          $container->get('bo.bundle')
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

    $this->type = \Drupal::routeMatch()->getParameter('type');

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attributes']['class'][] = 'clearfix';
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
  public function buildBundlesForm() {

    $form = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#attributes' => [
        'id' => 'bundles',
      ],
    ];

    $groups = ['_empty' => ''] + $this->boBundle->getBundleGroups($this->type);

    $entities = $this->load();
    $bundles = [];
    $bundle_count = 0;
    foreach ($entities as $bundle) {
      if ($this->type != $bundle->getType()) {
        continue;
      }
      $group = $bundle->getGroup();
      if ($group == '') { $group = '_empty'; }

      $bundles[$group][] = $bundle;

      if (!in_array($group, $groups)) {
        $groups[$group] = $group;
      }
      $bundle_count++;
    }

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // group get an unique weight.
    $weight_delta = round($bundle_count / 2);

    foreach ($groups as $label) {
      $group_machine_name = $label;
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
        '#prefix' => $label,
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
          'data-group-name' => $group_machine_name,
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

          $form[$bundle->id()] = $this->buildRow($bundle);

          $form[$bundle->id()]['#attributes'] = ['class' => ['draggable']];

          $form[$bundle->id()]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $bundle->getWeight(),
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @bundle', ['@bundle' => $bundle->label()]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => [
                'bundle-weight',
                'bundle-weight-' . $group_machine_name,
              ],
            ],
          ];

          $form[$bundle->id()]['label']['group'] = [
            '#type' => 'select',
            '#default_value' => $group_machine_name,
            '#required' => TRUE,
            '#title' => $this->t('Group for @bundle bundle', ['@bundle' => $bundle->label()]),
            '#title_display' => 'invisible',
            '#options' => $groups,
            '#attributes' => [
              'class' => [
                'bundle-group-select',
                'bundle-group-' . $group_machine_name,
              ],
            ],
            '#parents' => ['bundles', $bundle->id(), 'group'],
          ];

          $form[$bundle->id()]['operations'] = $this->buildOperations($bundle);
        }
      }
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function buildRow(BoBundleInterface $bundle) {

    $row['label']['#markup'] = $this->t($bundle->label());
    $row['description']['#markup'] = $bundle->getDescription();

    $row['id']['#markup'] = "<div class='bundle-machine-name'>" . $bundle->id() . "</div>";

    $row['default'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle->getDefault() ?? FALSE,
      "#name" => 'default[' . $bundle->id() . ']',
      '#attributes' => ["class" => ["bo-bundle-checkbox-default"]],
    ];

    $row['internal_title'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle->getInternalTitle() ?? FALSE,
      "#name" => 'internal_title[' . $bundle->id() . ']',
      // '#checked' => (bool)$bundle["internal_title"],
      '#attributes' => ["class" => ["bo-bundle-checkbox-internal-title"]],
    ];

    $row['override_title_label'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t("Label"),
      "#name" => 'override_title_label[' . $bundle->id() . ']',
      '#value' => $bundle->getOverrideTitleLabel() ?? '',
      '#attributes' => ["class" => ["bo-bundle-text-override-title-label"]],
    ];

    $row['icon'] = [
      '#type' => 'textfield',
      "#name" => 'icon[' . $bundle->id() . ']',
      "#required" => 0,
      '#value' => $bundle->getIcon() ?? '',
      "#size" => 10,
      '#attributes' => ["class" => ["bo-bundle-text-icon"]],
    ];

    $url = Url::fromRoute('bo.collection_settings_form', [
      'via' => 'bundle',
      'title' => $this->t("BO collection settings for bundle '@bundle'", ['@bundle' => $bundle->label()]),
      'bundle_id' => $bundle->id(),
    ]);
    $collection_settings_link = Link::fromTextAndUrl('Settings', $url)->toString();

    $row['collection_enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $bundle->getCollectionEnabled() ?? FALSE,
      "#name" => 'collection_enabled[' . $bundle->id() . ']',
      '#suffix' => '<div class="right-to-checkbox bo-bundle-collection">' . $collection_settings_link . '</div>',
      '#attributes' => ["class" => ["bo-bundle-checkbox-collection"]],
    ];

    return $row;
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
    $entity_input = $form_state->getUserInput();

    /** @var \Drupal\bo\Entity\BoBundleInterface $entity */
    foreach ($entities as $entity_id => $entity) {

      $entity_values = $form_state->getValue(['bundles', $entity_id]);

      $default = (int) $entity_input['default'][$entity_id];
      $entity->setDefault($default);

      $internal_title = (int) $entity_input['internal_title'][$entity_id];
      $entity->setInternalTitle($internal_title);

      $override_title_label = $entity_input['override_title_label'][$entity_id];
      if ($internal_title == 1) {
        $override_title_label = '';
      }
      $entity->setOverrideTitleLabel($override_title_label);

      $icon = $entity_input['icon'][$entity_id];
      $entity->setIcon($icon);

      $collection_enabled = (int) $entity_input['collection_enabled'][$entity_id];
      $entity->setCollectionEnabled($collection_enabled);

      $group = $entity_values['group'];
      if ($group == "_empty") {
        $group = "";
      }
      $entity->setGroup($group);

      $weight = $entity_values['weight'];
      $entity->setWeight($weight);

      $entity->save();
    }

    $this->messenger->addStatus($this->t('The bundles settings have been updated.'));
  }

}
