<?php

namespace Nautilus;

use ProcessWire\WireData;

class Strings extends WireData {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Replace {text} in a provided string 
   * with the key from a $data array
   * @param string $string eg: "Welcome to {title}"
   * @param array $data eg: ['title' => 'My Website']
   * @example 
   * Strings::replace("Welcome to {title}", ['title' => 'My Website'])
   * will return "Welcome to My Website"
   * @return string
   */
  public static function replace($string, $data) {
    $regex = preg_match_all('#\{(.*?)\}#', $string, $matches);
    $arr = $matches[0];
    foreach ($arr as $item) {
      $key = str_replace("{", "", $item);
      $key = str_replace("}", "", $key);
      $replace = !empty($data[$key]) ? $data[$key] : "";
      $string = str_replace($item, $replace, $string);
    }
    return $string;
  }

  /**
   *  Format page strings
   *  extract page variables
   *  @param string $string  eg: {title} or {select_page.url}
   *  @example Strings::pageStringReplace("{select_page.url}") will get $page->select_page->url
   *  @return string
   */
  public static function formatPageString($string, $p = "") {
    $page = $p != "" ? $p : wire("page");
    $string = ltrim($string);
    $string = preg_replace('/\s\s+/', ' ', $string);
    $text = preg_match_all('#\{(.*?)\}#', $string, $matches);
    $arr = $matches[0];
    $i = 0;
    foreach ($arr as $item) {
      $n = $i++;
      $str = $matches[1][$n];
      $str = explode(".", $str);
      $sl1 = $str[0];
      $sl2 = isset($str[1]) ? $str[1] : "";
      $selector = !empty($sl2) ? $page->{$sl1}->{$sl2} : $page->{$sl1};
      $replace = !empty($selector) ? $selector : "";
      $string = str_replace($item, $replace, $string);
    }
    return $string;
  }
}
