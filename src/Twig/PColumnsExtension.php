<?php

namespace Drupal\bo\Twig;

use Drupal\bo\Service\BoVarsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Drupal\Core\Template\Attribute;

/**
 * Class DefaultService.
 *
 * @package Drupal\twig_comment_stripper
 */
class PColumnsExtension extends AbstractExtension {

  /**
   * @var BoVarsHelper
   */
  private BoVarsHelper $boVarsHelper;

  public function __construct(BoVarsHelper $boVarsHelper) {
    $this->boVarsHelper = $boVarsHelper;
  }

  /**
   * @return \Twig\TwigFunction[]
   */
  public function getFunctions() {
    return [
      new TwigFunction('pColumns', [$this, 'pColumns'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * The method commentStripper itself.
   *
   * @param $string
   *
   * @return string
   */
  public function pColumns($content, $column_count) {
    $attributes = new Attribute();
    $attributes->addClass(['p-columns-' . $column_count]);
    $element = ['p'];
    $this->boVarsHelper->addAttributesToElement($content, $element, $attributes);
    return $content;
  }

}
