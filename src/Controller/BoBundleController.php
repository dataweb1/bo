<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Entity\BoBundle as BoBundleEntity;
use Drupal\bo\Service\BoBundle;
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
   * @param BoBundle $boBundle
   */
  public function __construct(BoBundle $boBundle) {
    $this->boBundle = $boBundle;
  }

  /**
   * @param ContainerInterface $container
   * @return BoBundleController|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.bundle')
    );
  }

  /**
   * Get title.
   */
  public function getBoBundleAddFormTitle($type) {

    if ($type == "element") {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("element"),
      ]);
    }

    if ($type == "content") {
      $title = $this->t('Add new @bo_type', [
        '@bo_type' => "BO " . $this->t("content"),
      ]);
    }

    return $title;
  }

  /**
   * Get title.
   */
  public function getBoBundleContentTypeListTitle() {
    $title = "BO " . $this->t("content");
    return $title;
  }

  /**
   * Get title.
   */
  public function getBoBundleElementListTitle() {
    $title = "BO " . $this->t("elements");
    return $title;
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
