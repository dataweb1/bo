<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Entity\BoBundle as BoBundleEntity;
use Drupal\bo\Enum\BoBundleType;
use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoSettings;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class BoBundleController extends ControllerBase {

  private BoBundle $boBundle;

  /**
   * @var mixed
   */
  private $boBundleTypes;

  /**
   * @param \Drupal\bo\Service\BoBundle $boBundle
   */
  public function __construct(BoBundle $boBundle, BoSettings $boSettings) {
    $this->boBundle = $boBundle;
    $this->boBundleTypes = $boSettings->getBundleTypes();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \Drupal\bo\Service\BoBundleController|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.bundle'),
      $container->get('bo.settings'),
    );
  }

  /**
   * Get title.
   */
  public function getBoBundleAddFormTitle($type) {
    return $this->t('Add new @bo_bundle', [
      '@bo_bundle' => "BO " . $this->t($this->boBundleTypes[$type]['singular']),
    ]);
  }

  /**
   * Get title.
   */
  public function getBoBundleElementsListTitle($type) {
    return 'BO ' . $this->t($this->boBundleTypes[$type]['plural']);
  }

  /**
   * Add form.
   */
  public function renderBoBundleAddForm($type) {
    $entity = BoBundleEntity::create();
    $entity->setType($type);
    return [
      'form' => $this->entityFormBuilder()->getForm($entity, 'default'),
    ];
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleGroupAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);

    $groups = $this->boBundle->getBundleGroups();
    foreach ($groups as $group) {
      if (strpos(strtolower($group), strtolower($input)) !== FALSE) {
        $results[] = [
          'value' => $group,
          'label' => $group,
        ];
      }
    }

    return new JsonResponse($results);
  }

}
