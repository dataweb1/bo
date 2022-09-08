<?php

namespace Drupal\bo\Service;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 *
 */
class BoHelp {

  use StringTranslationTrait;

  /**
   * @param $collection_id
   * @param $to_path
   * @param $entity_id
   * @return array
   */
  public function getHelpLink($collection_id, $to_path, $entity_id) {
    $attributes = [
      'class' => [
        'bo-trigger',
        'bo-trigger-help',
      ],
    ];

    $url = Url::fromRoute('bo.help', [
      'collection_id' => $collection_id,
      'to_path' => $to_path,
      'entity_id' => $entity_id,
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => $attributes,
      '#attached' => [
        'library' => [
          'bo/bo_help',
        ],
      ],
    ];
  }

  public function renderHelp($variables) {
    $help = '';
    $help .= '<div class="bo-fields-help" id="bo_bundle_help_' . $variables["id"] . '">';
    $help .= '<div class="bo-fields-help-content">';
    $help .= '<table>';
    $help .= "<tr><th>" . $this->t("Field name") . "</th><th>" . $this->t("twig variable") . "</th><th>" . $this->t("output") . "</th></tr>";

    foreach ($variables as $fieldName => $field) {
      if (substr($fieldName, 0, 1) != '#') {
        if (!is_array($field)) {
          $help .= "<tr>";
          $help .= "<td>";

          $help .= $fieldName;

          $help .= "</td>";

          $twigFieldName = "{{&nbsp;bo." . $fieldName . "&nbsp;}}";

          $help .= '<td><code class="copy" data-clipboard-action="copy" data-clipboard-text="' . str_replace("&nbsp;", " ", $twigFieldName) . '">' . $twigFieldName . "</code></td>";
          $help .= "<td>" . htmlentities(remove_html_comments($field)) . "</td>";

          $help .= "</tr>";
        }
        else {
          $this->helpRow($fieldName, $field, $keys, $help);
        }
        $keys = [];
      }
    }

    $help .= "</table>";
    $help .= "</div>";
    $help .= "</div>";

    return [
      "#markup" => Markup::create($help),
    ];

  }

  /**
   * @param $fieldName
   * @param $field
   * @param $keys
   * @param $help
   */
  private function helpRow($fieldName, $field, $keys, &$help) {

    foreach ($field as $key => $value) {
      $keys[] = $key;
      if (substr($key, 0, 1) != '#') {
        if (!is_array($value)) {
          $help .= "<tr>";
          $help .= "<td>";
          $help .= $fieldName;
          $help .= "</td>";

          $elements = $fieldName;
          foreach ($keys as $k) {
            $elements .= "." . $k;
          }
          $twigFieldName = "{{&nbsp;" . $elements . "&nbsp;}}";

          $help .= '<td><code class="copy" data-clipboard-action="copy" data-clipboard-text="' . str_replace("&nbsp;", " ", $twigFieldName) . '">' . $twigFieldName . "</code></td>";
          $help .= "<td>" . htmlentities(remove_html_comments($value)) . "</td>";
          $help .= "</tr>";
        }
        else {
          $this->helpRow($fieldName, $value, $keys, $help);
        }
        array_pop($keys);
      }
    }
  }

}
