<?php

namespace Nautilus;

/**
 * PageToArrayConverter
 * 
 * Converts ProcessWire Page objects to arrays recursively,
 * handling all field types including repeaters, page references, and fieldsets.
 *
 * @example $converter = new PageToArrayConverter();
 * @example $array = $converter->convert($page);
 */
class PageToArrayConverter {

  protected int $maxDepth;
  protected array $processedPages = [];

  public function __construct(int $maxDepth = 10) {
    $this->maxDepth = $maxDepth;
  }

  /**
   * Convert a Page to array
   * 
   * @param \ProcessWire\Page|null $page
   * @param int $currentDepth
   * @return array|null
   */
  public function convert($page, int $currentDepth = 0) {
    if (!$page || !$page->id || $currentDepth >= $this->maxDepth) {
      return null;
    }

    // Prevent infinite recursion for circular references
    if (isset($this->processedPages[$page->id])) {
      return ['id' => $page->id, 'title' => $page->title, '_circular_ref' => true];
    }

    $this->processedPages[$page->id] = true;
    $data = [];

    foreach ($page->getFields() as $field) {
      $value = $page->get($field->name);
      $data[$field->name] = $this->convertFieldValue($value, $field, $currentDepth + 1);
    }

    unset($this->processedPages[$page->id]);
    return $data;
  }

  /**
   * Convert field value based on its type
   */
  protected function convertFieldValue($value, $field, int $currentDepth) {
    // Handle Repeater fields (including RepeaterMatrix)
    if (
      $value instanceof \ProcessWire\PageArray &&
      ($field->type instanceof \ProcessWire\FieldtypeRepeater ||
        $field->type instanceof \ProcessWire\FieldtypeRepeaterMatrix)
    ) {
      return $this->convertPageArray($value, $currentDepth);
    }

    // Handle regular PageArray (multiple page references)
    if ($value instanceof \ProcessWire\PageArray) {
      return $this->convertPageArray($value, $currentDepth);
    }

    // Handle FieldsetPage
    if (
      $value instanceof \ProcessWire\Page &&
      $field->type instanceof \ProcessWire\FieldtypeFieldsetPage
    ) {
      return $this->convertFieldsetPage($value, $currentDepth);
    }

    // Handle single Page Reference
    if ($value instanceof \ProcessWire\Page) {
      return $this->convert($value, $currentDepth);
    }

    // Handle other field types (text, images, files, etc.)
    return $this->convertOtherValue($value);
  }

  /**
   * Convert PageArray to array
   */
  protected function convertPageArray(\ProcessWire\PageArray $pageArray, int $currentDepth): array {
    $result = [];
    foreach ($pageArray as $page) {
      $converted = $this->convert($page, $currentDepth);
      if ($converted !== null) {
        $result[$page->id] = $converted;
      }
    }
    return $result;
  }

  /**
   * Convert FieldsetPage to array
   */
  protected function convertFieldsetPage(\ProcessWire\Page $fieldsetPage, int $currentDepth): array {
    $result = [];
    foreach ($fieldsetPage->getFields() as $fsField) {
      $fsValue = $fieldsetPage->get($fsField->name);
      $result[$fsField->name] = $this->convertFieldValue($fsValue, $fsField, $currentDepth);
    }
    return $result;
  }

  /**
   * Convert other value types (files, images, etc.)
   */
  protected function convertOtherValue($value) {
    // Handle WireArray objects (like file/image fields)
    if ($value instanceof \ProcessWire\WireArray) {
      $result = [];
      foreach ($value as $item) {
        if (method_exists($item, 'url')) {
          $result[] = [
            'url' => $item->url,
            'filename' => $item->basename ?? '',
            'description' => $item->description ?? ''
          ];
        } else {
          $result[] = (string) $item;
        }
      }
      return $result;
    }

    return $value;
  }

  /**
   * Reset processed pages tracker (useful for multiple conversions)
   */
  public function reset(): void {
    $this->processedPages = [];
  }
}
