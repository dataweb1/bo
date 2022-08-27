<?php

namespace Drupal\bo\Plugin\views\style;

use Drupal\views_bootstrap\Plugin\views\style\ViewsBootstrapGrid;

/**
 * Style plugin to render an overview in flexible Bootstrap layout depending on row count.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_view_bo_bootstrap_grid",
 *   title = @Translation("BO Bootstrap Grid"),
 *   help = @Translation("Render an overview in flexible Bootstrap layout depending on row count."),
 *   theme = "views_view_bo_bootstrap_grid",
 *   theme_file = "../templates/inc/views_view_bo_bootstrap_grid.theme",
 *   display_types = { "normal" }
 * )
 */
class BoBootstrapGrid extends ViewsBootstrapGrid {

}
