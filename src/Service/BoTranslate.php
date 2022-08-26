<?php

namespace Drupal\bo\Service;

use Drupal\Core\Entity\EntityTypeManager;

/**
 *
 */
class BoTranslate {
  /**
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * @var string
   */
  private $endpoint = 'https://www.googleapis.com/language/translate/v2';

  /**
   * @var mixed
   */
  private $key;
  /**
   * @var mixed
   */
  private $enabled;

  /**
   *
   */
  public function __construct(BoSettings $boSettings, EntityTypeManager $entityTypeManager) {
    $this->boSettings = $boSettings;
    $this->enabled = $this->boSettings->getBoSetting("google_translate_enabled");
    $this->key = $this->boSettings->getBoSetting("google_translate_key");
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param $from_langcode
   * @param $to_langcode
   * @param $to_path
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function translatePathContent($from_langcode, $to_langcode, $to_path) {
    if ($this->enabled == TRUE) {

      $query = \Drupal::entityQuery('bo')
        ->condition('to_path', $to_path)
        ->condition('langcode', $from_langcode);
      $bo_ids = $query->execute();

      $bo_entities = $this->entityTypeManager->getStorage('bo')->loadMultiple($bo_ids);

      foreach ($bo_entities as $bo_entity) {

        $new_bo_entity = $bo_entity->createDuplicate();
        $new_bo_entity->set("langcode", $to_langcode);

        $fields = $this->boSettings->getFields($new_bo_entity->getBundle());

        foreach ($fields as $field_name) {
          if ($new_bo_entity->hasField($field_name)) {
            if (isset($new_bo_entity->get($field_name)->value)) {
              foreach ($new_bo_entity->get($field_name) as $key => &$item) {
                $new_bo_entity->get($field_name)[$key]->value = $this->translateValue($new_bo_entity->get($field_name)[$key]->value, $from_langcode, $to_langcode);
              }
            }

            if (isset($new_bo_entity->get($field_name)->summary)) {
              foreach ($new_bo_entity->get($field_name) as $key => &$item) {
                $new_bo_entity->get($field_name)[$key]->summary = $this->translateValue($new_bo_entity->get($field_name)[$key]->summary, $from_langcode, $to_langcode);
              }
            }
          }
        }
        $new_bo_entity->save();
      }
    }
  }

  /**
   * @param $value
   * @param $from_langcode
   * @param $to_langcode
   * @return mixed
   */
  public function translateValue($value, $from_langcode, $to_langcode) {
    if ($this->key != '') {
      $url = $this->endpoint . '?key=' . $this->key . '&q=' . rawurlencode($value) . '&source=' . $from_langcode . '&target=' . $to_langcode;

      $handle = curl_init($url);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
      $response = curl_exec($handle);
      $responseDecoded = json_decode($response, TRUE);
      curl_close($handle);

      return $responseDecoded['data']['translations'][0]['translatedText'];
    }
    else {
      \Drupal::messenger()->addWarning('Google translation key undefined.');
    }
  }

}
