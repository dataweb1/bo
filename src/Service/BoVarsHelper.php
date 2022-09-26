<?php

namespace Drupal\bo\Service;


use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Language\LanguageManager;
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

  /**
   * @var \Drupal\Core\Language\LanguageManager
   */
  private LanguageManager $languageManager;

  public function __construct(FileUrlGenerator $fileUrlGenerator, LanguageManager $languageManager) {
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->languageManager = $languageManager;
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
   * @param $elements
   * @param \Drupal\Core\Template\Attribute $attributes
   */
  public function addAttributesToElement(&$content, array $elements, Attribute $attributes) {
    foreach($elements as $element) {
      $content = Markup::create(
        str_replace('<' . $element, '<' . $element . ' ' . $attributes->jsonSerialize(), $content));
    }
  }

  /**
   * @param $content
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addCurrentLanguagePrefixToLinkitLinks(&$content) {
    if ($this->languageManager->getCurrentLanguage()->getId() != $this->languageManager->getDefaultLanguage()->getId()) {
      $doc = new \DOMDocument();
      $doc->loadHTML($content);
      $xml = \simplexml_import_dom($doc); // just to make xpath more simple
      $links = $xml->xpath('//a');
      $links_transformed = FALSE;
      foreach ($links as $link) {
        if (strpos($link['href'], '/' . $this->languageManager->getCurrentLanguage()->getId() . '/') === FALSE) {
          if (isset($link['data-entity-type']) && $link['data-entity-type'] == 'node') {
            // Get the link entity by the UUID.
            $link_entity_uuid = $link['data-entity-uuid']->__toString();
            if ($link_entity_uuid != '') {
              // Search for the link entity by the UUID.
              $link_entity = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties(['uuid' => $link_entity_uuid]);

              /** @var \Drupal\node\Entity\Node $link_entity */
              $link_entity = reset($link_entity);
              if ($link_entity) {
                // If entity has a translation.
                if (isset($link_entity->getTranslationLanguages()[$this->languageManager->getCurrentLanguage()
                    ->getId()])) {
                  // Add prefix itself.
                  $updated_href = $link['href'];
                  $updated_href = '/' . $this->languageManager->getCurrentLanguage()
                      ->getId() . $updated_href;

                  // Replace to href by the updates href.
                  $content = str_replace($link['href'], $updated_href, $content);

                  $links_transformed = TRUE;
                }
              }
            }
          }
        }
      }
      if ($links_transformed) {
        $content = Markup::create($content);
      }
    }
  }
}
