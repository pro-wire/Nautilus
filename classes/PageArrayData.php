<?php

/**
 * PageArrayData
 * 
 * Utility wrapper for ProcessWire PageArray.
 * It will return PageArray or plain Array
 *
 * Usage:
 *   $aop = new \Nautilus\ArrayOrPages($pageArray);
 *   $pages = $aop->pages(); // returns PageArray
 *   $array = $aop->array(); // returns [id => title]
 *   $array = $aop->array("{first_name} {last_name}"); // returns [id => "John Doe"]
 */

namespace Nautilus;

use ProcessWire\PageArray;
use Nautilus\PageToArrayConverter;

class PageArrayData {

  protected PageArray $pages;

  public function __construct(PageArray $pages) {
    $this->pages = $pages;
  }

  /**
   * Return Pages object
   */
  public function pages(): PageArray {
    return $this->pages;
  }


  /**
   * Return array of pages with all possible data
   * @see PageToArrayConverter
   */
  public function array(): array {
    $result = [];
    $converter = new PageToArrayConverter();
    foreach ($this->pages as $p) {
      $result[$p->id] = $converter->convert($p);
    }
    return $result;
  }

  /**
   * Return JSON representation of pages
   * @see array()
   */
  public function json(): string {
    return json_encode($this->array());
  }

  /**
   * Return array [id => value]
   * 
   * If $template contains {field}, replaces with $p->field.
   * Example: $aop->array("{first_name} {last_name}") returns [id => "John Doe"]
   * 
   * @param string $template - template string, e.g. "{first_name} {last_name}"
   * @return array
   */
  public function customArray($template = "title", $key = "id"): array {
    $arr = [];
    foreach ($this->pages as $p) {
      if (strpos($template, '{') !== false) {
        $value = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use ($p) {
          $field = $matches[1];
          return isset($p->$field) ? $p->$field : '';
        }, $template);
        $arr[$p->$key] = trim($value);
      } else {
        $arr[$p->$key] = $p->$template;
      }
    }
    return $arr;
  }
}
