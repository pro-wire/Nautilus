<?php

namespace Nautilus;

use ProcessWire\WireData;

class Fields extends WireData {

  /**
   * Options Field
   * Get options from a field
   * @param string $field_name
   * @return array
   */
  public function get_options($field_name) {
    $field = $this->fields->get($field_name);
    $options = $field->type->getOptions($field);
    $array = [];
    foreach ($options as $option) $array[$option->id] = $option->title;
    return $array;
  }

  /**
   * Fieldset
   * Get all fields in a fieldset or tab
   * @param string $template
   * @param string $SET - name of the fielset or tab field
   * @param array $exclude - fields to exclude
   */
  public function get_fieldset_fields($template = "", $SET = "", $exclude = []) {
    if (empty($SET)) return;
    $tmpl = $this->templates->get($template);
    if (empty($template)) return;
    $SET_start = false;
    $fields_arr = [];
    foreach ($tmpl->fields as $field) {
      if ($field->name == $SET) {
        $SET_start = true;
      } elseif ($field->name == "{$SET}_END") {
        break;
      } elseif ($SET_start == 'true') {
        if (!in_array($field->name, $exclude) && !in_array($field->type, $exclude)) {
          $fields_arr[] = $field;
        }
      }
    }
    return $fields_arr;
  }
}
