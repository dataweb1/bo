<?php

namespace Drupal\bo\Twig;

use Drupal\bo\Service\BoSettings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * Extend Drupal's Twig_Extension class.
 */
class BoSettingsTwigExtension extends AbstractExtension {

  private BoSettings $boSettings;

  /**
   *
   */
  public function __construct() {
    $this->boSettings = \Drupal::getContainer()->get('bo.settings');
  }

  /**
   * {@inheritdoc}
   * Let Drupal know the name of your extension
   * must be unique name, string
   */
  public function getName() {
    return 'bo.bosettingstwigextension';
  }

  /**
   * @return \Twig\TwigFunction[]
   */
  public function getFunctions() {
    return [
      new TwigFunction('get_google_maps_key', [$this, 'getGoogleMapsKey']),
    ];
  }

  /**
   * @return \Twig\TwigFilter[]
   */
  public function getFilters() {
    return [
      new TwigFilter('replace_tokens', [$this, 'replaceTokens']),
    ];
  }

  /**
   * Returns $_GET query parameter.
   *
   * @param string $name
   *   name of the query parameter.
   *
   * @return string
   *   value of the query parameter name
   */
  public function getGoogleMapsKey(): string {
    return $this->boSettings->getBoSetting("google_maps_key");
  }

  /**
   * Replaces available values to entered tokens
   * Also accept HTML text
   *
   * @param string $text
   *   replaceable tokens with/without entered HTML text.
   *
   * @return string
   *   replaced token values with/without entered HTML text
   */
  public function replaceTokens($text): string {
    return \Drupal::token()->replace($text);
  }

}
