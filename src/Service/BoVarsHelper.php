<?php

namespace Drupal\bo\Service;


use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;

/**
 *
 */
class BoVarsHelper {

  /**
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  private FileUrlGenerator $fileUrlGenerator;

  public function __construct(FileUrlGenerator $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * @param $content
   * @param $imageStyle
   */
  public function replaceInlineImagesByImageStyle(&$content, $imageStyle) {
    $doc=new \DOMDocument();
    $doc->loadHTML($content);
    $xml=\simplexml_import_dom($doc); // just to make xpath more simple
    $images=$xml->xpath('//img');
    $images_transformed = FALSE;
    foreach ($images as $img) {
      $public_files_folder = $this->fileUrlGenerator->generateString('public://');
      $image_uri = str_replace($public_files_folder . 'inline-images/', 'public://inline-images/', urldecode($img['src']));
      $style = ImageStyle::load($imageStyle);
      if ($image_url = $style->buildUrl($image_uri)) {
        $content = str_replace($img['src'], $image_url, $content);
        $images_transformed = TRUE;
      }
    }
    if ($images_transformed) {
      $content = Markup::create($content);
    }
  }

  /**
   * @param $content
   * @param $element
   * @param \Drupal\Core\Template\Attribute $attributes
   */
  public function addAttributesToElement(&$content, $element, Attribute $attributes) {
    $content = Markup::create(
      str_replace('<' . $element, '<' .$element. ' ' . $attributes->jsonSerialize(), $content));
  }

}
