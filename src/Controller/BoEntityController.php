<?php

namespace Drupal\bo\Controller;

use Drupal\bo\Service\BoView;
use Drupal\bo\Service\BoVars;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class BoEntityController extends ControllerBase {

  /**
   * @var BoVars
   */
  private BoVars $boVars;

  /**
   * @var BoView
   */
  private BoView $boView;

  /**
   * @var Request
   */
  private Request $request;

  /**
   *
   */
  public function __construct(BoView $boView, BoVars $boVars, RequestStack $request) {
    $this->boView = $boView;
    $this->boVars = $boVars;
    $this->request = $request->getCurrentRequest();
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bo.view'),
      $container->get('bo.vars'),
      $container->get('request_stack'),
    );
  }

  /**
   * Get title.
   */
  public function getInsertTitle($bo_bundle) {

    $title = $this->t(
      "Insert @bo_bundle",
      [
        '@bo_bundle' => $bo_bundle->get("label"),
      ]
    );

    return $title;
  }

  /**
   * Render help dialog via Twig link.
   *
   * @param $view_id
   * @param $display_id
   * @param $collection_id
   * @param $entity_id
   * @return array
   */
  public function help($view_id, $display_id, $collection_id, $entity_id) {

    $view = $this->boView->prepareBoView($view_id, $display_id, $collection_id, $this->request->query->get('to_path'));

    $help = '';
    foreach ($view->result as $row) {
      if ($row->_entity->id() == $entity_id) {
        $variables = [];
        $this->boVars->getVariables(
          $view,
          $row,
          $variables, [
            'basic',
            'fields',
            'collection',
          ]
        );

        $help .= '<div class="bo-fields-help" id="bo_fields_help_' . $variables["id"] . '">';
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

      }
    }

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
