<?php

namespace Drupal\bo\Service;

use Drupal\bo\Entity\BoEntity;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use voku\helper\URLify;

/**
 *
 */
class BoVars {

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  private Renderer $renderer;

  /**
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  private FileUrlGenerator $fileUrlGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private EntityFieldManager $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * @var BoBundle
   */
  private BoBundle $boBundle;

  /**
   * @var BoCollection
   */
  private BoCollection $boCollection;

  /**
   * @var BoHelp
   */
  private BoHelp $boHelp;

  /**
   * @var BoVarsHelper
   */
  private BoVarsHelper $boVarsHelper;

  /**
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\Core\File\FileUrlGenerator $fileUrlGenerator
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param BoBundle $boBundle
   * @param BoCollection $boCollection
   * @param BoHelp $boHelp
   * @param BoVarsHelper $boVarsHelper
   */
  public function __construct(Renderer $renderer, FileUrlGenerator $fileUrlGenerator, EntityFieldManager $entityFieldManager, EntityTypeManager $entityTypeManager, BoBundle $boBundle, BoCollection $boCollection, BoHelp $boHelp, BoVarsHelper $boVarsHelper) {
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->boBundle = $boBundle;
    $this->boCollection = $boCollection;
    $this->boHelp = $boHelp;
    $this->boVarsHelper = $boVarsHelper;
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

      /** @var \Drupal\bo\Entity\BoBundle $bundle */
      if ($bundle = $this->boBundle->getBundle($entity->getBundle())) {
        $current_display = $view->getDisplay();

        if (in_array("basic", $return)) {
          $vars["bo"]["id"] = $entity->id();
          $vars["bo"]["view_id"] = $view->id();
          $vars["bo"]["display_id"] = $view->current_display;

          $vars["bo"]["row_count"] = count($view->result);
          if (isset($view->row_index)) {
            $vars["bo"]["row_index"] = $view->row_index;
          }
          $vars["bo"]["bundle"] = $bundle->id();
          $vars["bo"]["size"] = $entity->getSize();
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
                if ($bundle->getInternalTitle() == TRUE) {
                  continue;
                }
              }

              if ($entity->hasField($field_name)) {
                $element = [
                  'field_type' => $field->getFieldDefinition()->getFieldStorageDefinition()->getType(),
                ];
                $empty_array = [];
                $element = array_merge($element, $this->processField($entity, $field_name, $vars, $level, $empty_array));

                $this->getRenderedViewFields($current_display, $row, $field_name, $element);

                $vars["bo"][$field_name] = $element;
              }

            }
          }
        }

        if (in_array("collection", $return)) {
          $is_collection = $bundle->getCollection()['enabled'] ?? FALSE;
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
            $vars["help"] = $this->boHelp->getHelpLink(
              $view->filter['bo_current_collection_id_filter']->value,
              $view->argument['bo_current_path_argument']->argument,
              $vars['bo']['id']
            );
          }
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
  private function getCollectionData(ViewExecutable $view, BoEntity $collection, &$vars, array $return = []) {

    $data = [];

    if (in_array("items", $return)) {
      [$collection_view_id, $collection_display_id] = $this->boCollection->getCollectionView($collection->id());

      if ($view_collection = Views::getView($collection_view_id)) {
        $view_collection->setDisplay($collection_display_id);

        $_POST["collection_id"] = '';

        if (!isset($view_collection->filter["bo_current_collection_id_filter"])) {
          $view_collection->filter["bo_current_collection_id_filter"] = new \stdClass();
        }
        $view_collection->filter["bo_current_collection_id_filter"]->value = $collection->id();

        $view_collection->preExecute();
        $view_collection->execute();

        $items = [];
        foreach ($view_collection->result as $row) {

          $item = [];

          /** @var \Drupal\bo\Entity\BoEntity $item_entity */
          $item_entity = $row->_entity;
          $item_entity_id = $item_entity->id();

          /** @var \Drupal\bo\Entity\BoBundle $item_entity_bundle */
          $item_entity_bundle = $this->boBundle->getBundle($item_entity->getBundle());

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
                if ($item_entity_bundle->getInternalTitle() == TRUE) {
                  continue;
                }
              }

              if ($item_entity->hasField($field_name)) {
                $empty_array = [];
                $element = $this->processField($item_entity, $field_name, $vars, $level, $empty_array);
                $this->getRenderedViewFields($item_current_display, $row, $field_name, $element);

                $item[$field_name] = $element;
              }

            }
          }

          $items[] = $item;
        }
        $data["items"] = $items;

        if (in_array('rendered_collection', $return)) {
          $rendered_collection = $view_collection->render();
          $rendered_collection["#cache"]["tags"][] = $collection->getBundle();
          $data["collection"] = $rendered_collection;
        }
      }
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
        $n = str_replace("-", "_", URLify::filter($field->options["label"]));
      }
      else {
        $n = $name;
      }

      $field_table = $current_display->getHandlers('field')[$name]->ensureMyTable();

      if (strpos($field_table, $field_name) !== FALSE) {
        $rendered_markup = $current_display->getHandlers('field')[$name]->advancedRender($row);
        $element["rendered"]["view_" . $n] = $rendered_markup;
      }
      else {
        if (strpos($name, $field_name) !== FALSE) {
          $rendered_markup = $current_display->getHandlers('field')[$name]->advancedRender($row);
          $element["rendered"]["view_" . $n] = $rendered_markup;
        }
      }
    }
    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param int $level
   * @param array $element
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processField(EntityInterface $entity, $field_name, &$vars, int $level = 0, array &$element = []) {

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

    // file, image, media, node, ...
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processUriField(EntityInterface $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {

      $uri = $item->uri;
      $url = Url::fromUri($uri);
      $title = $item->title;
      $url_string = $url->toString();
      if ($url_string == '') {
        $url_string = '#';
      }

      $target = "";

      $attributes = [];
      $item_value = $item->getValue();
      if (isset($item_value['options']['attributes'])) {
        $attributes = $item_value['options']['attributes'];
      }

      if ($url->isExternal() == 1) {
        $attributes["target"] = "_blank";
        $target = "_blank";
      }

      $basic = [
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => $attributes,
        '#title' => $title,
      ];

      $e["rendered"]["basic"] = $this->renderer->renderPlain($basic);
      $e["raw"]["uri"] = $uri;
      $e["raw"]["url"] = $url_string;
      $e["raw"]["title"] = $title;
      foreach ($attributes as $attribute_key => $attribute_value) {
        $e['raw'][$attribute_key] = implode(' ', (array) $attribute_value);
      }
      $e["raw"]["target"] = $target;

      // $this->smartValue($url_string, $e, $vars);
      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element['items'][$key] = $e;
      }
    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processSummaryField(EntityInterface $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $raw_summary = $item->summary;

      $raw_markup = Markup::create($raw_summary);

      $e["raw"]["summary"] = $raw_markup;

      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element['items'][$key] = $e;
      }

    }
    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   */
  private function processNameField(EntityInterface $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $raw = $item->name;
      $raw_markup = Markup::create($raw);

      if ($raw_markup instanceof Markup) {
        $e["raw"]["name"] = $raw_markup->__toString();
      }
      else {
        $e["raw"]["name"] = $raw_markup;
      }

      if ($cardinality == 1) {
        if (!empty($element)) {
          $element = array_merge($element, $e);
        }
        else {
          $element = $e;
        }

      }
      else {
        $element['items'][$key] = $e;
      }

    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return mixed
   */
  private function processValueField(EntityInterface $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();

    foreach ($entity->get($field_name) as $key => $item) {
      $raw = $item->value;

      if ($field_name == "created" || $field_name == "changed" || $item instanceof DateTimeItem) {
        if ($item instanceof DateTimeItem) {
          $raw = strtotime($raw);
        }
        $e["raw"]["timestamp"] = $raw;
        $e["raw"]["day"] = date("d", $raw);
        $e["raw"]["month"] = date("m", $raw);
        $e["raw"]["year"] = date("Y", $raw);
        $e["raw"]["hour"] = date("H", $raw);
        $e["raw"]["minute"] = date("i", $raw);
        $e["raw"]["second"] = date("s", $raw);
      }
      else {
        $raw_markup = Markup::create($raw);
        if ($raw_markup instanceof Markup) {
          $e["raw"]["value"] = $raw_markup->__toString();
        }
        else {
          $e["raw"]["value"] = $raw_markup;
        }
      }

      // $this->smartValue($item->value, $e, $vars);
      $this->replaceDrupalMedia($e["raw"]["value"], $entity, $level, $vars);
      if ($cardinality == 1) {

        if (!empty($element["raw"])) {
          $element["raw"] = array_merge($element["raw"], $e["raw"]);
        }
        else {
          $element = $e;
        }

      }
      else {
        if (!empty($element['items'][$key]["raw"])) {
          $element['items'][$key]["raw"] = array_merge($element['items'][$key]["raw"], $e["raw"]);
        }
        else {
          $element['items'][$key] = $e;
        }
      }

    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param $vars
   * @param $element
   * @return array|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function processTargetField(EntityInterface $entity, $field_name, &$vars, $level, &$element) {

    $cardinality = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();
    foreach ($entity->get($field_name) as $parent_key => $item) {

      $settings = $entity->getFields()[$field_name]->getSettings();

      if ($target_entity = $this->entityTypeManager->getStorage($settings["target_type"])->load($item->target_id)) {

        $uri = "/" . str_replace("_", "/", $settings["target_type"]) . "/" . $target_entity->id();
        $url = Url::fromUserInput($uri);

        switch (TRUE) {
          // User.
          case ($settings["handler"] == "default" && $settings["target_type"] == 'user'):
            if ($cardinality == 1) {
              $r = &$element;
            }
            else {
              $r = &$element['items'][$parent_key];
            }

            $r['account_name'] = $target_entity->getAccountName();
            $r['account_email'] = $target_entity->getEmail();
            break;

          // Node or term.
          case (($settings["handler"] == "default" && $settings["target_type"] == 'node') || $settings["handler"] == "default:node" || $settings["handler"] == "default:taxonomy_term"):

            if ($cardinality == 1) {
              $r = &$element;
            }
            else {
              $r = &$element['items'][$parent_key];
            }

            $r["entity_type"] = $settings["target_type"];
            $r["link"]["raw"]["url"] = $url->toString();
            $r["entity_bundle"] = $target_entity->bundle();

            $bundle_fields = $this->entityFieldManager->getFieldDefinitions($settings["target_type"], $target_entity->bundle());
            foreach ($bundle_fields as $field) {
              $field_name_2 = $field->getName();
              if ($field_name_2 == "name" ||
                $field_name_2 == "title" ||
                $field_name_2 == "created" ||
                $field_name_2 == "changed" ||
                $field_name_2 == "body" ||
                substr($field_name_2, 0, 6) == "field_") {
                if (!array_key_exists($field_name_2, $r)) {
                  $r[$field_name_2] = [];
                }
                $r[$field_name_2] = $this->processField($target_entity, $field_name_2, $vars, $level, $r[$field_name_2]);
              }
            }

            break;

          // Regular file.
          case ($settings["handler"] == "default:file" && !isset($settings["default_image"])):
            if ($file_entity = $this->entityTypeManager->getStorage("file")->load($item->target_id)) {
              $e = $this->getFileData($file_entity);
              if ($cardinality == 1) {
                if (!empty($element)) {
                  $element = array_merge($element, $e);
                }
                else {
                  $element = $e;
                }

              }
              else {
                $element['items'][$parent_key] = $e;
              }
            }
            break;

          // Regular image.
          case ($settings["handler"] == "default:file" && isset($settings["default_image"]));
            /** @var \Drupal\file\Entity\File $file_entity */
            if ($file = $this->entityTypeManager->getStorage("file")->load($item->target_id)) {
              $e = $this->getImageData($file, 'bo_' . $this->imageStyleSize($entity), $item->alt);

              if ($cardinality == 1) {
                if (!empty($element)) {
                  $element = array_merge($element, $e);
                }
                else {
                  $element = $e;
                }

              }
              else {
                $element['items'][$parent_key] = $e;
              }
            }
            break;

          // Media.
          case ($settings["handler"] == "default:media"):

            /** @var \Drupal\media\Entity\MediaInterface $media */
            if ($media = $this->entityTypeManager->getStorage("media")->load($item->target_id)) {
              $e = $this->getMediaData($media, 'bo_' . $this->imageStyleSize($entity), $level, $vars);

              if ($cardinality == 1) {
                if (!empty($element)) {
                  $element = array_merge($element, $e);
                }
                else {
                  $element = $e;
                }

              }
              else {
                $element['items'][$parent_key] = $e;
              }
            }
            break;

          default:
            if ($cardinality == 1) {
              $r = &$element;
            }
            else {
              $r = &$element['items'][$parent_key];
            }

            $r["entity_type"] = $settings["target_type"];
            $r["target_id"] = (string) $item->target_id;
        }

      }
    }

    $vars["#cache"]["tags"][] = $entity->getEntityType()->id() . ':' . $entity->id();

    return $element;
  }

  /**
   * @param \Drupal\file\Entity\FileInterface $file_entity
   * @return array
   * @throws \Exception
   */
  public function getFileData(FileInterface $file_entity) {

    $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($file_entity->getFileUri()));
    $basic = [
      '#type' => 'link',
      '#url' => $url,
      '#attributes' => ["target" => "_blank"],
      '#title' => $file_entity->getFileName(),
    ];

    $e["rendered"]["basic"] = $this->renderer->renderPlain($basic);
    $e["raw"]["uri"] = $file_entity->getFileUri();
    $e["raw"]["url"] = $url->toString();
    $e["raw"]["filename"] = $file_entity->getFileName();
    ;
    $e["raw"]["size"] = $file_entity->getSize();
    $e["rendered"]["size"] = $this->boVarsHelper->formatBytes($file_entity->getSize());
    $e["raw"]["type"] = str_replace("/", "-", $file_entity->getMimeType());
    ;
    $e["raw"]["target"] = "_blank";

    return $e;
  }

  /**
   * @param \Drupal\file\Entity\FileInterface $file
   * @param $style_name
   * @param string $alt
   * @return array
   * @throws \Exception
   */
  public function getImageData(FileInterface $file, $style_name, $alt = '') {

    $optimized_url = "";
    $image_style = ImageStyle::load($style_name);
    if ($image_style) {
      $optimized_url = $image_style->buildUrl($file->getFileUri());

      $basic = [
        '#theme' => 'image_style',
        '#style_name' => $style_name,
        '#alt' => $alt,
        '#uri' => $file->getFileUri(),
      ];
      $e["rendered"]["basic"] = $this->renderer->renderPlain($basic);
    }

    $e['raw']['fid'] = $file->id();
    $e["raw"]["uri"] = $file->getFileUri();
    $e["raw"]["original_url"] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    $e["raw"]["optimized_url"] = $optimized_url;
    $e["raw"]["type"] = str_replace("/", "-", $file->getMimeType());
    $e["raw"]["alt"] = $alt;
    $e["raw"]["filename"] = $file->getFileName();
    $e["raw"]["size"] = $file->getSize();
    $e["rendered"]["size"] = $this->boVarsHelper->formatBytes($file->getSize());

    return $e;
  }

  /**
   * @param \Drupal\media\Entity\MediaInterface $media
   * @param $style_name
   * @param $level
   * @param $vars
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaData(MediaInterface $media, $style_name, $level = 0, &$vars = []) {
    $e["media_bundle"] = $media->bundle();

    if ($media->bundle() == "remote_video") {
      $url = Url::fromUri($media->field_media_oembed_video->value);
      $e['name'] = $media->name->value;

      $thumbnail_file = $this->entityTypeManager->getStorage("file")->load($media->thumbnail->target_id);
      $e['thumbnail'] = $this->getImageData($thumbnail_file, $style_name);

      switch (TRUE) {
        case preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url->toString(), $matches):
          $e["type"] = "youtube_url";
          $e['raw']["video_id"] = $matches[1];
          break;

        case preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $url->toString(), $matches);
          $e["type"] = "vimeo_url";
          $e['raw']["video_id"] = $matches[5];
          break;
      }

      if ($e["type"] == "youtube_url" ||
        $e["type"] == "vimeo_url") {
        $vars["#attached"]["library"][] = "bo/bo_bundle_video";
      }
    }

    if ($media->bundle() == "image") {

      if ($media->hasField('field_no_crop')) {
        if ($media->get('field_no_crop')->value == TRUE) {
          $style_name .= '_no_crop';
        }
      }

      $uri = $media->field_media_image->entity->getFileUri();
      $original_url = $this->fileUrlGenerator->generateAbsoluteString($media->field_media_image->entity->getFileUri());

      $alt = $media->field_media_image->alt;
      $type = str_replace("/", "-", $media->field_media_image->entity->getMimeType());
      $filename = $media->field_media_image->entity->getFileName();
      $size = $media->field_media_image->entity->getSize();

      $optimized_url = "";
      $image_style = ImageStyle::load($style_name);
      if ($image_style) {
        $optimized_url = $image_style->buildUrl($uri);

        $basic = [
          '#theme' => 'image_style',
          '#style_name' => $style_name,
          '#alt' => $alt,
          '#uri' => $uri,
        ];
        $e["rendered"]["basic"] = $this->renderer->renderPlain($basic);

        $display_options = [
          'label'    => 'hidden',
          'type'     => 'responsive_image',
          'settings' => [
            'responsive_image_style' => 'wide',
          ],
        ];

        // Get image, apply display options.
        $image = $media->get('field_media_image')->view($display_options);

        // Render.
        $e["rendered"]["responsive"] = $this->renderer->renderPlain($image);
      }

      $e['raw']['fid'] = $media->field_media_image->entity->id();
      $e['raw']['mid'] = $media->id();
      $e["raw"]["uri"] = $uri;
      $e["raw"]["original_url"] = $original_url;
      $e["raw"]["optimized_url"] = $optimized_url;
      $e["raw"]["type"] = $type;
      $e["raw"]["alt"] = $alt;
      $e["raw"]["filename"] = $filename;
      $e["raw"]["size"] = $size;
      $e["rendered"]["size"] = $this->boVarsHelper->formatBytes($size);
    }

    if ($media->bundle() == "file" || $media->bundle() == "document") {
      $field = 'field_media_' . $media->bundle();
      if ($media->{$field}->entity) {
        $file = $this->entityTypeManager->getStorage("file")->load($media->{$field}->entity->id());
        $uri = $file->getFileUri();
        $filename = $file->getFileName();
        $name = $media->get("name")->value;
        $size = $file->getSize();
        $attributes = ["target" => "_blank"];
        $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($uri));
        // $url->setOptions(array("attributes" => $attributes));
        $type = str_replace("/", "-", $file->getMimeType());
        $basic = [
          '#type' => 'link',
          '#url' => $url,
          '#attributes' => $attributes,
          '#title' => $name,
        ];

        $extended = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];

        $e["rendered"]["basic"] = $this->renderer->renderPlain($basic);
        $e["rendered"]["extended"] = $this->renderer->renderPlain($extended);
        $e["raw"]["uri"] = $uri;
        $e["raw"]["name"] = $name;
        $e["raw"]["filename"] = $filename;
        $e["raw"]["size"] = $size;
        $e["rendered"]["size"] = $this->boVarsHelper->formatBytes($size);
        $e["raw"]["type"] = $type;
        $e["raw"]["target"] = "_blank";
      }
    }

    /*
    $e["raw"]["name"] = $name;
    $e["raw"]["url"] = $url->toString();
     */

    $target_media_fields = $media->getFields();
    foreach ($target_media_fields as $target_media_field_name => $field) {
      if (substr($target_media_field_name, 0, 6) == 'field_' && $target_media_field_name != 'field_media_image') {
        $empty_array = [];
        $e[$target_media_field_name] = $this->processField($media, $target_media_field_name, $vars, $level, $empty_array);
      }
    }

    return $e;
  }

  /**
   * @param $content
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $level
   * @param $vars
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function replaceDrupalMedia(&$content, EntityInterface $entity, $level = 0, &$vars = []) {
    if (strpos($content, 'drupal-media')) {
      $doc = new \DOMDocument();
      $doc->loadHTML(mb_convert_encoding($content, "HTML-ENTITIES", "UTF-8"), LIBXML_HTML_NODEFDTD);

      /** @var \DOMNodeList $drupal_media */
      $drupal_media = $doc->getElementsByTagName('drupal-media');
      while ($drupal_media->length) {
        $drupal_media_item = $drupal_media->item(0);

        $entity_type = $drupal_media_item->getAttribute('data-entity-type');
        if ($entity_type == 'media') {
          // Get the link entity by the UUID.
          $align = $drupal_media_item->getAttribute('data-align');
          $media_uuid = $drupal_media_item->getAttribute('data-entity-uuid');
          if ($media_uuid != '') {
            // Search for the link entity by the UUID.
            $media = \Drupal::entityTypeManager()
              ->getStorage('media')
              ->loadByProperties(['uuid' => $media_uuid]);

            /** @var \Drupal\media\Entity\Media $media */
            $media = reset($media);
            if ($media) {
              $media_data = $this->getMediaData($media, 'bo_' . $this->imageStyleSize($entity, $align), $level, $vars);
              $media_data['align'] = $align;

              $renderable = [
                '#theme' => 'bo__' . $entity->bundle() . '__media',
                '#bo' => [
                  'media' => $media_data,
                ],
              ];

              /** @var \Drupal\Core\Render\Renderer $renderer */
              $renderer = \Drupal::service('renderer');
              $rendered = $renderer->renderPlain($renderable);

              $fragment = $doc->createDocumentFragment();
              $fragment->appendXML($this->boVarsHelper->removeHtmlComments($rendered));

              $drupal_media_item->parentNode->replaceChild($fragment, $drupal_media_item);
            }
          }
        }
      }

      $content = strtr($doc->saveHTML(), [
        '<html>' => '',
        '</html>' => '',
        '<body>' => '',
        '</body>' => '',
      ]);

      $content = Markup::create($content);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return int
   */
  private function imageStyleSize(EntityInterface $entity, $align = '') {
    if ($align == 'left' || $align == 'right') {
      return 6;
    }

    $image_style_size = 12;
    if ($entity->getEntityType()->id() == "bo") {
      $image_style_size = $entity->getSize();
    }

    return $image_style_size;
  }

}
