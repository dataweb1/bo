<?php

namespace Drupal\bo\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class DefaultService.
 *
 * @package Drupal\twig_comment_stripper
 */
class CommentStripperTwigExtension extends AbstractExtension {

  /**
   * @return \Twig\TwigFunction[]
   */
  public function getFunctions() {
    return [
      new TwigFunction('commentStripper', [$this, 'commentStripper'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * The method commentStripper itself.
   *
   * @param $string
   *
   * @return string
   */
  public function commentStripper($string) {
    $output = preg_replace('/<!--(.|\s)*?-->\s*/', '', $string);
    return trim($output);
  }

}
