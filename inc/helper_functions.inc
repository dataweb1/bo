<?php
/**
 * @param string $content
 * @return array|string|string[]|null
 */
function remove_html_comments($content = '') {
  return preg_replace('/<!--(.|\s)*?-->/', '', $content);
}

/**
 * @param $size
 * @param int $precision
 * @return string
 */
function format_bytes($size, $precision = 2) {
  $base = log($size, 1024);
  $suffixes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

  return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

/**
 * @param $text
 * @return string
 */
function slugify($text) {
  // Replace non letter or digits by -.
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);

  // Transliterate.
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // Remove unwanted characters.
  $text = preg_replace('~[^-\w]+~', '', $text);

  // Trim.
  $text = trim($text, '-');

  // Remove duplicate -.
  $text = preg_replace('~-+~', '-', $text);

  // Lowercase.
  $text = strtolower($text);

  return $text;
}
