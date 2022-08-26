<?php

namespace Drupal\bo\Service;

use Drupal\bo\Entity\BoEntity;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 *
 */
class BoVars {

  /**
   * @var BoSettings
   */
  private BoSettings $boSettings;

  /**
   * @var Renderer
   */
  private Renderer $renderer;

  /**
   * @var FileUrlGenerator
   */
  private FileUrlGenerator $fileUrlGenerator;

  /**
   * @var EntityFieldManager
   */
  private EntityFieldManager $entityFieldManager;

  /**
   * @var EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   *
   */
  public function __construct(BoSettings $boSettings, Renderer $renderer, FileUrlGenerator $fileUrlGenerator, EntityFieldManager $entityFieldManager, EntityTypeManager $entityTypeManager) {
    $this->boSettings = $boSettings;
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param $view
   * @param $row
   * @param $vars
   * @param array $return
   * @return array
   */
  public function getVariables(ViewExecutable $view, ResultRow $row, &$vars, array $return = []) {

    $library = $vars["#attached"]["library"] ?? [];

    /** @var \Drupal\bo\Entity\BoEntity $entity */
    $entity = $row->_entity;
    if ($entity) {
      $current_user = \Drupal::currentUser();

      $bundle = $entity->getBundle();
      $current_display = $view->getDisplay();
      $entity_size = $entity->getSize();
      $current_view_display_settings = $entity->getCurrentViewDisplaySettings();
      $entity_id = $entity->id();

      $view_id = $view->id();
      $display_id = $view->current_display;

      if (in_array("basic", $return)) {
        $vars["bo"]["id"] = $entity_id;
        $vars["bo"]["view_id"] = $view_id;
        $vars["bo"]["display_id"] = $display_id;
        $vars["bo"]["plugin_id"] = $current_view_display_settings["plugin_id"];

        $vars["bo"]["row_count"] = count($view->result);
        if (isset($view->row_index)) {
          $vars["bo"]["row_index"] = $view->row_index;
        }
        $vars["bo"]["bundle"] = $bundle;
        $vars["bo"]["size"] = $entity_size;
      }

      $fields = $entity->getFields();

      if (in_array("fields", $return)) {
        foreach ($fields as $field_name => $field) {

          $level = 0;
          if ($field_name != "bundle" &&
            $field_name != "id" &&
            $field_name != "size" &&
            $field_name != "display_id" &&
            $field_name != "changed" &&
            $field_name != "weight") {
            if ($field_name == "title") {
              if ($this->boSettings->isTitleInternal($bundle) == TRUE) {
                continue;
              }
            }

            if ($entity->hasField($field_name)) {
              $element = $this->processField($entity, $field_name, $vars, $level);
              $this->getRenderedViewFields($current_display, $row, $field_name, $element);
              $vars["bo"][$field_name] = $element;
            }

          }
        }
      }

      if (in_array("collection", $return)) {
        $is_collection = $this->boSettings->isBundleCollection($bundle);

        if ($is_collection) {

          $collection_data = $this->getCollectionData(
            $view,
            $entity,
            $vars,
            [
              'items',
              'rendered_collection',
            ]
          );
          $vars["bo"]["collection"] = $collection_data["items"];
          $vars["collection"] = $collection_data["collection"];
        }
      }

      if (in_array("help", $return)) {
        if ($current_user->hasPermission("show twig help")) {
          $vars["help"] = $this->getHelpLink(
            $view->id(),
            $view->current_display,
            $view->filter['bo_current_collection_id_filter']->value,
            $view->argument['bo_current_path_argument']->argument,
            $vars['bo']['id']
          );

          $library[] = 'bo/bo_fields_twig_help';
        }
      }
    }

    $library[] = 'bo/bo_frontend';

    $vars['#attached']['library'] = $library;
  }

  /**
   * @param \Drupal\views\ViewExecutable $view
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $vars
   * @param array $return
   * @return array
   */
  private function getCollectionData(ViewExecutable $view, BoEntity $entity, &$vars, array $return = []) {

    $data = [];

    $view_id = $view->id();
    $display_id = $view->current_display;

    $collection_id = $entity->id();

    if (in_array("items", $return)) {
      $overview_name = $view_id . "__" . $display_id;
      if ($collection_id != "" && $collection_id != "-") {
        $overview_name .= "__" . $collection_id;
      }

      $specific_view = $this->boSettings->getCollectionOptions($overview_name, "specific_view");

      if ($specific_view == "") {
        $collection_machine_name = $this->boSettings->getCollectionBundleMachineNameViaId($collection_id);

        $specific_view = $this->boSettings->getCollectionOptions($collection_machine_name, "specific_view");
      }

      if ($specific_view != "") {
        $a_specific_view = explode("__", $specific_view);
        $collection_view_id = $a_specific_view[0];
        $collection_display_id = $a_specific_view[1];
      }
      else {
        $collection_view_id = $view_id;
        $collection_display_id = $display_id;
      }

      $view_collection = Views::getView($collection_view_id);
      $view_collection->setDisplay($collection_display_id);

      $_POST["collection_id"] = "";

      if (!isset($view_collection->filter["bo_current_collection_id_filter"])) {
        $view_collection->filter["bo_current_collection_id_filter"] = new \stdClass();
      }
      $view_collection->filter["bo_current_collection_id_filter"]->value = $collection_id;

      $view_collection->preExecute();
      $view_collection->execute();

      $items = [];
      foreach ($view_collection->result as $row) {

        $item = [];

        /** @var \Drupal\bo\Entity\BoEntity $item_entity */
        $item_entity = $row->_entity;
        $item_entity_id = $item_entity->id();
        $item_entity_bundle = $item_entity->getBundle();

        $item_current_display = $view_collection->getDisplay();

        $item["id"] = $item_entity_id;

        $fields = $item_entity->getFields();
        foreach ($fields as $field_name => $field) {
          if ($field_name != "bundle" &&
            $field_name != "id" &&
            $field_name != "size" &&
            $field_name != "display_id" &&
            $field_name != "changed" &&
            $field_name != "weight") {

            $level = 0;

            if ($field_name == "title") {
              if ($this->boSettings->isTitleInternal($item_entity_bundle) == TRUE) {
                continue;
              }
            }

            if ($item_entity->hasField($field_name)) {
              $element = $this->processField($item_entity, $field_name, $vars, $level);
              $this->getRenderedViewFields($item_current_display, $row, $field_name, $element);

              $item[$field_name] = $element;
            }

          }
        }

        $items[] = $item;
      }
      $data["items"] = $items;

    }

    if (in_array('rendered_collection', $return)) {
      $collection = $view_collection->render();
      $collection["#cache"]["tags"][] = $entity->getBundle();
      // kint($collection["#cache"]);.
      $data["collection"] = $collection;
    }

    return $data;
  }

  /**
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $current_display
   * @param \Drupal\views\ResultRow $row
   * @param $field_name
   * @param $element
   * @return array
   */
  public function getRenderedViewFields(DisplayPluginBase $current_display, ResultRow $row, $field_name, &$element) {

    foreach ($current_display->getHandlers('field') as $name => $field) {

      if ($field->options["label"] != "") {
        $n = str_replace("-", "_", slugify($field->options["label"]));
      }
      else {
        $n = $name;
      }

      $field_table = $current_display->getHandlers('field')[$name]->ensureMyTable();

      if (strpos($field_table, $field_name) !== FALSE) {
        $rendered_markup = $current_display->getHandlers('field')[$name]->advancedRender($row);
        $element["rendered_value"]["view_" . $n] = $rendered_markup;
      }
      else {
        if (strpos($name, $field_name) !== FALSE) {
          $rendered_markup = $current_display->getHandlers('field')[$name]->advancedRender($row);
          $element["rendered_value"]["view_" . $n] = $rendered_markup;
        }
      }
    }
    return $element;
  }

  /**
   * @param BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param int $level
   * @param array $element
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processField(BoEntity $entity, $field_name, &$vars, int $level = 0, array &$element = []) {

    // Name.
    if (isset($entity->get($field_name)->name)) {
      $element = $this->processNameField($entity, $field_name, $vars, $level, $element);
    }

    // Link.
    if (isset($entity->get($field_name)->uri)) {
      $element = $this->processUriField($entity, $field_name, $vars, $level, $element);
    }

    // Text.
    if (isset($entity->get($field_name)->summary)) {
      $element = $this->processSummaryField($entity, $field_name, $vars, $level, $element);
    }

    // text, integer, ...
    if (isset($entity->get($field_name)->value)) {
      $element = $this->processValueField($entity, $field_name, $vars, $level, $element);
    }

    // file, image, media, ...
    if (isset($entity->get($field_name)->target_id)) {
      if ($level <= 2) {
        $level++;
        $element = $this->processTargetField($entity, $field_name, $vars, $level, $element);
        $level--;
      }
    }

    return $element;
  }

  /**
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processUriField(BoEntity $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {

      $uri = $item->uri;
      $url = Url::fromUri($uri);
      $title = $item->title;

      $url_string = $url->toString();
      $target = "";
      $attributes = [];
      if ($url->isExternal() == 1) {
        $attributes = ["target" => "_blank"];
        $target = "_blank";
      }

      $basic = [
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => $attributes,
        '#title' => $title,
      ];

      $e["rendered_value"]["basic"] = $this->renderer->render($basic);
      $e["raw_value"]["uri"] = $uri;
      $e["raw_value"]["url"] = $url_string;
      $e["raw_value"]["title"] = $title;
      $e["raw_value"]["target"] = $target;

      $this->smartValue($url_string, $e, $vars);

      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element[$key] = $e;
      }
    }

    return $element;
  }

  /**
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processSummaryField(BoEntity $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $raw_summary = $item->summary;

      $raw_markup = Markup::create($raw_summary);

      $e["raw_value"]["summary"] = $raw_markup;

      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element[$key] = $e;
      }

    }
    return $element;
  }

  /**
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processNameField(BoEntity $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();
      $raw_value = $item->name;
      $raw_markup = Markup::create($raw_value);

      $e["raw_value"]["name"] = $raw_markup;

      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element[$key] = $e;
      }

    }

    return $element;
  }

  /**
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return mixed
   */
  private function processValueField(BoEntity $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $raw_value = $item->value;

      $raw_markup = Markup::create($raw_value);

      if ($field_name == "created" || $field_name == "changed") {
        $e["raw_value"]["timestamp"] = $raw_markup;
        $e["raw_value"]["day"] = date("d", $raw_markup->__toString());
        $e["raw_value"]["month"] = date("m", $raw_markup->__toString());
        $e["raw_value"]["year"] = date("Y", $raw_markup->__toString());
        $e["raw_value"]["hour"] = date("H", $raw_markup->__toString());
        $e["raw_value"]["minute"] = date("i", $raw_markup->__toString());
        $e["raw_value"]["second"] = date("s", $raw_markup->__toString());
      }
      else {
        $e["raw_value"]["value"] = $raw_markup;
      }

      $this->smartValue($item->value, $e, $vars);

      if ($cardinality == 1) {

        if (!empty($element["raw_value"])) {
          $element["raw_value"] = array_merge($element["raw_value"], $e["raw_value"]);
        }
        else {
          $element = $e;
        }

      }
      else {
        if (!empty($element[$key]["raw_value"])) {
          $element[$key]["raw_value"] = array_merge($element[$key]["raw_value"], $e["raw_value"]);
        }
        else {
          $element[$key] = $e;
        }
      }

    }

    return $element;
  }

  /**
   * @param \Drupal\bo\Entity\BoEntity $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function processTargetField(BoEntity $entity, $field_name, &$vars, $level, &$element) {
    static $bo_entity_size;
    if ($entity->getEntityType()->id() == "bo") {
      if ($vars["view"]) {
        $bo_entity_size = $entity->getSize($vars["view"]);
      }
    }

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $parent_key => $item) {
      $target_id = $item->target_id;
      $settings = $entity->getFields()[$field_name]->getSettings();

      $target_type = $settings["target_type"];
      if ($target_entity = $this->entityTypeManager->getStorage($target_type)->load($target_id)) {

        $uri = "/" . str_replace("_", "/", $target_type) . "/" . $target_entity->id();
        $url = Url::fromUserInput($uri)->toString();

        if ($settings["handler"] == "default:webform") {
          if ($cardinality == 1) {
            $r = &$element;
          }
          else {
            $r = &$element[$parent_key];
          }

          $r["entity_type"] = $target_type;
          $r["link"]["raw_value"]["form"] = $target_id;
          $r["cardinality"] = $cardinality;
        }

        if ($settings["handler"] == "default:node" || $settings["handler"] == "default:taxonomy_term") {

          if ($cardinality == 1) {
            $r = &$element;
          }
          else {
            $r = &$element[$parent_key];
          }

          $r["entity_type"] = $target_type;
          $r["link"]["raw_value"]["url"] = $url;
          $r["cardinality"] = $cardinality;

          foreach ($settings["handler_settings"]["target_bundles"] as $target_bundle) {

            $r["entity_bundle"] = $target_bundle;

            $bundle_fields = $this->entityFieldManager->getFieldDefinitions($target_type, $target_bundle);
            foreach ($bundle_fields as $field) {
              $field_name_2 = $field->getName();
              if ($field_name_2 == "name" ||
                $field_name_2 == "title" ||
                $field_name_2 == "created" ||
                $field_name_2 == "changed" ||
                $field_name_2 == "body" ||
                substr($field_name_2, 0, 6) == "field_") {

                $r[$field_name_2] = $this->processField($target_entity, $field_name_2, $vars, $level, $r[$field_name_2]);
              }
            }
          }

        }

        if ($settings["handler"] == "default:file" && !isset($settings["default_image"])) {
          $target_media_entity = $this->entityTypeManager->getStorage("file")->load($target_id);

          $uri = $target_media_entity->getFileUri();
          $filename = $target_media_entity->getFileName();
          $size = $target_media_entity->getSize();
          $attributes = ["target" => "_blank"];
          $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($target_media_entity->getFileUri()));
          // $url->setOptions(array("attributes" => $attributes));
          $type = str_replace("/", "-", $target_media_entity->getMimeType());
          $basic = [
            '#type' => 'link',
            '#url' => $url,
            '#attributes' => ["target" => "_blank"],
            '#title' => $filename,
          ];

          $e["rendered_value"]["basic"] = $this->renderer->render($basic);
          $e["raw_value"]["uri"] = $uri;
          $e["raw_value"]["url"] = $url;
          $e["raw_value"]["filename"] = $filename;
          $e["raw_value"]["size"] = $size;
          $e["rendered_value"]["size"] = format_bytes($size);
          $e["raw_value"]["type"] = $type;
          $e["raw_value"]["target"] = "_blank";

          if ($cardinality == 1) {
            if (!empty($element)) {
              $element = array_merge($element, $e);
            }
            else {
              $element = $e;
            }

          }
          else {
            $element[$parent_key] = $e;
          }
        }

        if ($settings["handler"] == "default:file" && isset($settings["default_image"])) {

          $target_media_entity = $this->entityTypeManager->getStorage("file")->load($target_id);

          $uri = $target_media_entity->getFileUri();
          $filename = $target_media_entity->getFileName();
          $size = $target_media_entity->getSize();
          $original_url = $this->fileUrlGenerator->generateAbsoluteString($target_media_entity->getFileUri());
          $type = str_replace("/", "-", $target_media_entity->getMimeType());
          $alt = $item->alt;

          $optimized_url = "";
          $style_name = 'bo_' . $bo_entity_size;

          $image_style = ImageStyle::load($style_name);
          if ($image_style) {
            $optimized_url = $image_style->buildUrl($uri);

            $basic = [
              '#theme' => 'image_style',
              '#style_name' => $style_name,
              '#alt' => $alt,
              '#uri' => $uri,
            ];
            $e["rendered_value"]["basic"] = $this->renderer->render($basic);
          }

          $e["raw_value"]["uri"] = $uri;
          $e["raw_value"]["original_url"] = $original_url;
          $e["raw_value"]["optimized_url"] = $optimized_url;
          $e["raw_value"]["type"] = $type;
          $e["raw_value"]["alt"] = $alt;
          $e["raw_value"]["filename"] = $filename;
          $e["raw_value"]["size"] = $size;
          $e["rendered_value"]["size"] = format_bytes($size);

          if ($cardinality == 1) {
            if (!empty($element)) {
              $element = array_merge($element, $e);
            }
            else {
              $element = $e;
            }

          }
          else {
            $element[$parent_key] = $e;
          }
        }

        if ($settings["handler"] == "default:media") {
          $target_media_entity = $this->entityTypeManager->getStorage("media")->load($target_id);

          if ($target_media_entity) {
            $e["media_bundle"] = $target_media_entity->bundle();

            $name = "";

            if ($target_media_entity->bundle() == "remote_video") {
              $url = $target_media_entity->field_media_oembed_video->value;
              $name = $target_media_entity->name->value;

              $thumbnail_target_id = $target_media_entity->thumbnail->target_id;
              $thumbnail = $this->entityTypeManager->getStorage("media")->load($thumbnail_target_id);
              $this->smartValue($url, $e, $vars);
            }

            if ($target_media_entity->bundle() == "image") {

              $uri = $target_media_entity->field_media_image->entity->getFileUri();
              $original_url = $this->fileUrlGenerator->generateAbsoluteString($target_media_entity->field_media_image->entity->getFileUri());
              $alt = $target_media_entity->field_media_image->alt;
              $type = str_replace("/", "-", $target_media_entity->field_media_image->entity->getMimeType());
              $filename = $target_media_entity->field_media_image->entity->getFileName();
              $size = $target_media_entity->field_media_image->entity->getSize();

              $optimized_url = "";
              $style_name = 'bo_' . $bo_entity_size;
              $image_style = ImageStyle::load($style_name);
              if ($image_style) {
                $optimized_url = $image_style->buildUrl($uri);

                $basic = [
                  '#theme' => 'image_style',
                  '#style_name' => $style_name,
                  '#alt' => $alt,
                  '#uri' => $uri,
                ];
                $e["rendered_value"]["basic"] = $this->renderer->render($basic);
              }

              $e["raw_value"]["uri"] = $uri;
              $e["raw_value"]["original_url"] = $original_url;
              $e["raw_value"]["optimized_url"] = $optimized_url;
              $e["raw_value"]["type"] = $type;
              $e["raw_value"]["alt"] = $alt;
              $e["raw_value"]["filename"] = $filename;
              $e["raw_value"]["size"] = $size;
              $e["rendered_value"]["size"] = format_bytes($size);
            }

            if ($target_media_entity->bundle() == "file") {
              $uri = $target_media_entity->field_media_file->entity->getFileUri();
              $filename = $target_media_entity->field_media_file->entity->getFileName();
              $name = $target_media_entity->get("name")->value;
              $size = $target_media_entity->field_media_file->entity->getSize();
              $attributes = ["target" => "_blank"];
              $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($target_media_entity->field_media_file->entity->getFileUri()));
              // $url->setOptions(array("attributes" => $attributes));
              $type = str_replace("/", "-", $target_media_entity->field_media_file->entity->getMimeType());
              $basic = [
                '#type' => 'link',
                '#url' => $url,
                '#attributes' => $attributes,
                '#title' => $name,
              ];

              $e["rendered_value"]["basic"] = $this->renderer->render($basic);
              $e["raw_value"]["uri"] = $uri;
              $e["raw_value"]["url"] = $url;
              $e["raw_value"]["name"] = $name;
              $e["raw_value"]["filename"] = $filename;
              $e["raw_value"]["size"] = $size;
              $e["rendered_value"]["size"] = format_bytes($size);
              $e["raw_value"]["type"] = $type;
              $e["raw_value"]["target"] = "_blank";
            }

            $e["raw_value"]["name"] = $name;
            $e["raw_value"]["url"] = $url;

            if ($cardinality == 1) {
              if (!empty($element)) {
                $element = array_merge($element, $e);
              }
              else {
                $element = $e;
              }

            }
            else {
              $element[$parent_key] = $e;
            }
          }
        }
      }
    }
    return $element;
  }

  /**
   * @param $value
   * @param $element
   * @param $vars
   */
  private function smartValue($value, &$element, &$vars) {

    if (preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $value, $matches)) {
      $element["smart_value"]["type"] = "youtube_url";
      $element["smart_value"]["video_id"] = $matches[1];
    }

    if (preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $value, $matches)) {
      $element["smart_value"]["type"] = "vimeo_url";
      $element["smart_value"]["video_id"] = $matches[5];
    }

    if (isset($element["smart_value"])) {
      if ($element["smart_value"]["type"] == "youtube_url" ||
        $element["smart_value"]["type"] == "vimeo_url") {
        if (!array_search("bo/bo_fields_remote_video", $vars["#attached"]["library"])) {
          $vars["#attached"]["library"][] = "bo/bo_fields_remote_video";
        }
      }
    }
  }

  /**
   * @param $view_id
   * @param $display_id
   * @param $collection_id
   * @param $to_path
   * @param $entity_id
   * @return array
   */
  public function getHelpLink($view_id, $display_id, $collection_id, $to_path, $entity_id) {

    $attributes = [
      'class' => [
        'bo-trigger',
        'bo-trigger-help',
      ],
    ];

    $url = Url::fromRoute('bo.help', [
      'view_id' => $view_id,
      'display_id' => $display_id,
      'collection_id' => $collection_id,
      'to_path' => $to_path,
      'entity_id' => $entity_id,
    ]);

    return [
      '#title' => '',
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => $attributes,
    ];
  }

}
