<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Service\BoBundle;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class BoBundleGroupsAutoCompleteController extends ControllerBase {

  private BoBundle $boBundle;

  public function __construct() {
    $this->boBundle = \Drupal::getContainer()->get('bo.bundle');
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);

    $groups = $this->boBundle->getBundleGroups();
    dpm($groups);
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
