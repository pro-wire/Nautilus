<?php

/**
 *  Nautilus
 *  @author Ivan Milincic <hello@kreativan.dev>
 *  @link https://kreativan.dev
 */

class Nautilus extends WireData implements Module {

  public static function getModuleInfo() {
    return array(
      'title' => 'Nautilus',
      'version' => 100,
      'summary' => 'Nautilus tools and utilities for ProcessWire applications.',
      'icon' => 'anchor',
      'author' => "Ivan Milincic",
      "href" => "https://kreativan.dev",
      'singular' => true,
      'autoload' => false,
      'requires' => ['ProcessWire>=3.0.0'],
    );
  }

  public function __construct() {
  }

  public function init() {
    //
  }

  public function path() {
    return $this->config->paths->siteModules . $this->className() . "/";
  }

  public function url() {
    return $this->config->urls->siteModules . $this->className() . "/";
  }

  /**
   * Load class with Nautilus namespace
   * @param string $className - class name to load
   * @param string $namespace - namespace to use, default is 'Nautilus'
   * @return object - instance of the class
   */
  public function loadClass($className, $namespace = 'Nautilus') {
    $file = $this->path() . "classes/{$className}.php";
    if (file_exists($file)) {
      include_once($file);
      $fullClassName = "{$namespace}\\{$className}";
      return new $fullClassName();
    } else {
      throw new WireException("Class file not found: {$file}");
    }
  }

  /**
   * Include class file
   * This method will include a class file based on the class name
   * @param string $className - class name to include
   * @return bool - true if file was included, false if not found
   * @throws WireException - if file not found
   */
  public function includeClass($className) {
    $file = $this->path() . "classes/{$className}.php";
    if (file_exists($file)) {
      include_once($file);
      return true;
    } else {
      throw new WireException("Class file not found: {$file}");
    }
  }
}
