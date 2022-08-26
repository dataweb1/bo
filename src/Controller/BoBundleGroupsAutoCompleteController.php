<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Service\BoSettings;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class BoBundleGroupsAutoCompleteController extends ControllerBase {

  private BoSettings $boSettings;

  public function __construct() {
    $this->boSettings = \Drupal::getContainer()->get('bo.settings');
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

    $groups = $this->boSettings->getBoBundleGroups();
    foreach ($groups as $machine_name => $group) {
      if (strpos($group, $input) !== FALSE) {
        $results[] = [
          'value' => $group,
          'label' => $group,
        ];
      }
    }

    return new JsonResponse($results);
  }

}
