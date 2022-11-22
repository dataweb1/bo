<?php

use Drupal\Core\Theme\ActiveTheme;

/**
 * @param $elements
 * @param ActiveTheme $theme
 * @param $templates
 */
function add_views_view_bo_suggestion_to_templates(array $elements, ActiveTheme $theme, array &$templates) {
  $all_elements = "";
  foreach ($elements as $key => $element) {
    if ($key > 0) {
      $all_elements .= "__";
    }
    $all_elements .= $element;
    $template = 'views-view-bo--' . str_replace("_", "-", $all_elements);

    $module_template_file = DRUPAL_ROOT . "/" . \Drupal::service('module_handler')->getModule('bo')->getPath() . "/templates/" . $template . ".html.twig";
    $theme_template_file = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template . ".html.twig";

    // $s = 'views_view_bo__' . str_replace("-", "_", $all_elements);
    $s = 'views_view_bo__' . $all_elements;
    $t = $template;
    $template_path = "";
    if (file_exists($module_template_file)) {
      $template_path = \Drupal::service('module_handler')->getModule('bo')->getPath() . "/templates";

    }
    if (file_exists($theme_template_file)) {
      $template_path = $theme->getPath() . "/templates";
    }

    if ($template_path != "") {
      $preprocess_functions = [
        'template_preprocess_views_view',
        'bo_preprocess_views_view_bo',
        'bo_preprocess_views_view_bo__' . $elements[0],
        'bo_preprocess_views_view_bo__' . $elements[0] . '__' . $elements[1],
        $theme->getName() . '_preprocess_views_view_bo',
        $theme->getName() . '_preprocess_views_view_bo__' . $elements[0],
        $theme->getName() . '_preprocess_views_view_bo__' . $elements[0] . '__' . $elements[1],
      ];
      $templates[$s] = [
        'template' => $t,
        'file' => 'inc/views_view_bo.theme',
        'path' => $template_path,
        'base_hook' => 'views_view',
        'preprocess functions' => $preprocess_functions,
      ];
    }
    else {
      $template = 'views-view-bo';

      $module_template_file = DRUPAL_ROOT . "/" . \Drupal::service('module_handler')->getModule('bo')->getPath() . "/templates/" . $template . ".html.twig";
      $theme_template_file = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template . ".html.twig";

      $s = 'views_view_' . $all_elements;
      $t = $template;
      $template_path = "";
      if (file_exists($module_template_file)) {
        $template_path = \Drupal::service('module_handler')->getModule('bo')->getPath() . "/templates";

      }
      if (file_exists($theme_template_file)) {
        $template_path = $theme->getPath() . "/templates";
      }

      if ($template_path != "") {
        if ($s == str_replace("-", "_", $template)) {
          $preprocess_functions = [
            'template_preprocess_views_view',
            'bo_preprocess_views_view_bo',
            $theme->getExtension()->base_theme . '_preprocess_views_view',
            $theme->getExtension()->base_theme . '_preprocess_views_view_bo',
            $theme->getName() . '_preprocess_views_view',
            $theme->getName() . '_preprocess_views_view_bo',
          ];
        }
        else {
          $preprocess_functions = [
            'template_preprocess_views_view',
            'bo_preprocess_views_view_bo',
            'bo_preprocess_views_view_bo__' . $elements[0],
            'bo_preprocess_views_view_bo__' . $elements[0] . '__' . $elements[1],
            $theme->getExtension()->base_theme . '_preprocess_views_view',
            $theme->getExtension()->base_theme . '_preprocess_views_view_bo',
            $theme->getExtension()->base_theme . '_preprocess_views_view_bo__' . $elements[0],
            $theme->getExtension()->base_theme . '_preprocess_views_view_bo__' . $elements[0] . '__' . $elements[1],
            $theme->getName() . '_preprocess_views_view',
            $theme->getName() . '_preprocess_views_view_bo',
            $theme->getName() . '_preprocess_views_view_bo__' . $elements[0],
            $theme->getName() . '_preprocess_views_view_bo__' . $elements[0] . '__' . $elements[1],
          ];
        }

        $templates[$s] = [
          'template' => $t,
          'file' => 'inc/views_view_bo.theme',
          'path' => $template_path,
          'base_hook' => 'views_view',
          'preprocess functions' => $preprocess_functions,
        ];
      }

    }
  }

}

/**
 * @param array $elements
 * @param ActiveTheme $theme
 * @param array $templates
 */
function add_views_view_unformatted_bo_suggestion_to_templates(array $elements, ActiveTheme $theme, array &$templates) {

  /** @var \Drupal\bo\Service\BoHandler $boHandler */
  $boHandler = \Drupal::service('bo.handler');

  $all_elements = "";
  foreach ($elements as $key => $element) {
    if ($key > 0) {
      $all_elements .= "__";
    }
    $all_elements .= $element;
    $template = 'views-view-unformatted-bo--' . str_replace("_", "-", $all_elements);

    $s = 'views_view_unformatted_bo__' . $all_elements;
    $t = $template;
    $template_path = "";

    foreach ($boHandler->getModulesPaths() as $modules_path) {
      $module_template_file = DRUPAL_ROOT . '/' . $modules_path . "/templates/" . $template . ".html.twig";
      if (file_exists($module_template_file)) {
        $t = $template;
        $template_path = $modules_path . "/templates";
        break;
      }
    }

    $theme_template_file = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template . ".html.twig";
    if (file_exists($theme_template_file)) {
      $template_path = $theme->getPath() . "/templates";
    }

    if ($template_path != "") {
      $preprocess_functions = [
        'template_preprocess_views_view_unformatted',
        'bo_preprocess_views_view_unformatted_bo',
        'bo_preprocess_views_view_unformatted_bo__' . $elements[0],
        'bo_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1]
      ];

      foreach ($boHandler->getModulesPaths() as $module_name => $modules_path) {
        $preprocess_functions = array_merge($preprocess_functions, [
          $module_name . '_preprocess_views_view_unformatted',
          $module_name . '_preprocess_views_view_unformatted_bo',
          $module_name . '_preprocess_views_view_unformatted_bo__' . $elements[0],
          $module_name . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
        ]);
      }

      $preprocess_functions = array_merge($preprocess_functions, [
        $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted',
        $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo',
        $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo__' . $elements[0],
        $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
      ]);

      $preprocess_functions = array_merge($preprocess_functions, [
        $theme->getName() . '_preprocess_views_view_unformatted',
        $theme->getName() . '_preprocess_views_view_unformatted_bo',
        $theme->getName() . '_preprocess_views_view_unformatted_bo__' . $elements[0],
        $theme->getName() . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
      ]);

      $templates[$s] = [
        'template' => $t,
        'file' => 'inc/views_view_unformatted_bo.theme',
        'path' => $template_path,
        'base_hook' => 'views_view_unformatted',
        'preprocess functions' => $preprocess_functions,
      ];
    }
    else {

      $template = 'views-view-unformatted-bo';

      $s = 'views_view_unformatted_' . $all_elements;
      $t = $template;
      $template_path = "";
      foreach ($boHandler->getModulesPaths() as $modules_path) {
        $module_template_file = DRUPAL_ROOT . '/' . $modules_path . "/templates/" . $template . ".html.twig";
        if (file_exists($module_template_file)) {
          $t = $template;
          $template_path = $modules_path . "/templates";
          break;
        }
      }

      $theme_template_file = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template . ".html.twig";
      if (file_exists($theme_template_file)) {
        $template_path = $theme->getPath() . "/templates";
      }

      if ($template_path != "") {
        if ($s == str_replace("-", "_", $template)) {
          $preprocess_functions = [
            'template_preprocess_views_view_unformatted',
            'bo_preprocess_views_view_unformatted_bo',
            $theme->getName() . '_preprocess_views_view_unformatted',
            $theme->getName() . '_preprocess_views_view_unformatted_bo',
          ];
        }
        else {
          $preprocess_functions = [
            'template_preprocess_views_view_unformatted',
            'bo_preprocess_views_view_unformatted_bo',
            'bo_preprocess_views_view_unformatted_bo__' . $elements[0],
            'bo_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1]
          ];

          foreach ($boHandler->getModulesPaths() as $module_name => $modules_path) {
            $preprocess_functions = array_merge($preprocess_functions, [
              $module_name . '_preprocess_views_view_unformatted',
              $module_name . '_preprocess_views_view_unformatted_bo',
              $module_name . '_preprocess_views_view_unformatted_bo__' . $elements[0],
              $module_name . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
            ]);
          }

          $preprocess_functions = array_merge($preprocess_functions, [
            $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted',
            $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo',
            $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo__' . $elements[0],
            $theme->getExtension()->base_theme . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
          ]);

          $preprocess_functions = array_merge($preprocess_functions, [
            $theme->getName() . '_preprocess_views_view_unformatted',
            $theme->getName() . '_preprocess_views_view_unformatted_bo',
            $theme->getName() . '_preprocess_views_view_unformatted_bo__' . $elements[0],
            $theme->getName() . '_preprocess_views_view_unformatted_bo__' . $elements[0] . '__' . $elements[1],
          ]);
        }
        $templates[$s] = [
          'template' => $t,
          'file' => 'inc/views_view_unformatted_bo.theme',
          'path' => $template_path,
          'base_hook' => 'views_view_unformatted',
          'preprocess functions' => $preprocess_functions,
        ];
      }
    }
  }
}

/**
 * @param string $bundle
 * @param ActiveTheme $theme
 * @param array $templates
 */
function add_views_view_field_bo_suggestion_to_templates(string $bundle, ActiveTheme $theme, array &$templates) {
  /** @var \Drupal\bo\Service\BoHandler $boHandler */
  $boHandler = \Drupal::service('bo.handler');

  $template_first = 'views-view-field-bo--' . str_replace("_", "-", $bundle);

  $s = 'views_view_field_bo__' . $bundle;

  $t = "";

  $template_path = "";
  $theme_template_file_first = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template_first . ".html.twig";
  if (file_exists($theme_template_file_first)) {
    $t = $template_first;
    $template_path = $theme->getPath() . "/templates";
  }
  else {
    foreach ($boHandler->getModulesPaths() as $modules_path) {
      $module_template_file_first = DRUPAL_ROOT . '/' . $modules_path . "/templates/" . $template_first . ".html.twig";
      if (file_exists($module_template_file_first)) {
        $t = $template_first;
        $template_path = $modules_path . "/templates";
        break;
      }
    }
  }

  if ($t != "") {
    $preprocess_functions = [
      'template_preprocess_views_view_field',
      'bo_preprocess_views_view_field_bo',
      'bo_preprocess_views_view_field_bo__bo_fields',
      'bo_preprocess_views_view_field_bo__' . $bundle,
    ];

    foreach ($boHandler->getModulesPaths() as $module_name => $modules_path) {
      $preprocess_functions = array_merge($preprocess_functions, [
        $module_name . '_preprocess_views_view_field',
        $module_name . '_preprocess_views_view_field_bo',
        $module_name . '_preprocess_views_view_field_bo__bo_fields',
        $module_name . '_preprocess_views_view_field_bo__' . $bundle,
      ]);
    }

    $preprocess_functions = array_merge($preprocess_functions, [
      $theme->getExtension()->base_theme . '_preprocess_views_view_field',
      $theme->getExtension()->base_theme . '_preprocess_views_view_field_bo',
      $theme->getExtension()->base_theme . '_preprocess_views_view_field_bo__bo_fields',
      $theme->getExtension()->base_theme . '_preprocess_views_view_field_bo__' . $bundle,
    ]);

    $preprocess_functions = array_merge($preprocess_functions, [
      $theme->getName() . '_preprocess_views_view_field',
      $theme->getName() . '_preprocess_views_view_field_bo',
      $theme->getName() . '_preprocess_views_view_field_bo__bo_fields',
      $theme->getName() . '_preprocess_views_view_field_bo__' . $bundle,
    ]);

    $templates[$s] = [
      'template' => $t,
      'file' => 'inc/views_view_field_bo__bo_fields.theme',
      'path' => $template_path,
      'base_hook' => 'views_view_field',
      'preprocess functions' => $preprocess_functions,
    ];
  }
  else {

    $template = 'views-view-field-bo--bo-fields';

    $s = 'views_view_field_bo__' . $bundle;

    $t = $template;

    $template_path = "";
    $theme_template_file = DRUPAL_ROOT . "/" . $theme->getPath() . "/templates/" . $template . ".html.twig";
    if (file_exists($theme_template_file)) {
      $template_path = $theme->getPath() . "/templates";
    }
    else {
      foreach ($boHandler->getModulesPaths() as $modules_path) {
        $module_template_file = DRUPAL_ROOT . "/" . $modules_path . "/templates/" . $template . ".html.twig";
        if (file_exists($module_template_file)) {
          $template_path = $modules_path . "/templates";
        }
      }
    }

    if ($template_path != "") {
      $preprocess_functions = [
        'template_preprocess_views_view_field',
        'bo_preprocess_views_view_field_bo',
        'bo_preprocess_views_view_field_bo__bo_fields',
      ];

      foreach ($boHandler->getModulesPaths() as $module_name => $modules_path) {
        $preprocess_functions = array_merge($preprocess_functions, [
          $module_name . '_preprocess_views_view_field',
          $module_name . '_preprocess_views_view_field_bo',
          $module_name . '_preprocess_views_view_field_bo__bo_fields',
        ]);
      }

      $preprocess_functions = array_merge($preprocess_functions, [
        $theme->getExtension()->base_theme . '_preprocess_views_view_field',
        $theme->getExtension()->base_theme . '_preprocess_views_view_field_bo',
        $theme->getExtension()->base_theme . '_preprocess_views_view_field_bo__bo_fields',
      ]);

      $preprocess_functions = array_merge($preprocess_functions, [
        $theme->getName() . '_preprocess_views_view_field',
        $theme->getName() . '_preprocess_views_view_field_bo',
        $theme->getName() . '_preprocess_views_view_field_bo__bo_fields',
      ]);

      $templates[$s] = [
        'template' => $t,
        'file' => 'inc/views_view_field_bo__bo_fields.theme',
        'path' => $template_path,
        'base_hook' => 'views_view_field',
        'preprocess functions' => $preprocess_functions,
      ];
    }
  }

}
