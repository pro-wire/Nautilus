<?php

/**
 * Valitron Wrapper Class for ProcessWire
 * 
 * This class provides a ProcessWire-integrated wrapper for the Valitron validation library.
 * It handles data validation with support for custom rules, labels, and multilingual error messages.
 * 
 * Basic Usage:
 * ```php
 * $validator = $nautilus->loadClass('Valitron');
 * 
 * $data = ['email' => 'test@test.com', 'age' => 25];
 * 
 * $rules = [
 *   'email' => ['required', 'email'],
 *   'age' => ['required', 'integer', 'min' => 18]
 * ];
 * 
 * $labels = ['email' => 'Email Address', 'age' => 'Your Age'];
 * 
 * $result = $validator->validate($data, $rules, $labels);
 * 
 * if ($result['valid']) {
 *   echo "Validation passed!";
 * } else {
 *   foreach ($result['errors'] as $field => $errors) {
 *     echo implode(', ', $errors);
 *   }
 * }
 * ```
 * 
 * Advanced Usage with Custom Rules:
 * ```php
 * $rules = [
 *   'username' => ['required', 'alphaNum', 'lengthBetween' => [3, 20]],
 *   'password' => ['required', 'lengthMin' => 8],
 *   'confirm_password' => ['required', 'equals' => 'password']
 * ];
 * ```
 * 
 * Available Validation Rules:
 * - required, optional, accepted
 * - alpha, alphaNum, ascii, slug
 * - email, emailDNS, url, urlActive, ip
 * - numeric, integer, boolean
 * - min, max, between, length, lengthBetween, lengthMin, lengthMax
 * - in, notIn, equals, different
 * - date, dateFormat, dateBefore, dateAfter
 * - And many more...
 * 
 * Language Support:
 * - Automatically detects ProcessWire user language
 * - Falls back to English if no language detected
 * - Supports Valitron's built-in language files
 * - Library is automatically loaded when validate() is called
 * 
 * @package Nautilus - Valitron
 * @author Ivan Milincic
 * @version 1.0
 * @link https://github.com/vlucas/valitron Valitron Documentation
 */

namespace Nautilus;

use \ProcessWire\WireData;

class Valitron extends WireData {

  /**
   * Flag to track if Valitron library has been initialized
   * @var bool
   */
  private $initialized = false;

  /**
   * Helper instance for loading classes
   * @var \Nautilus\Nautilus
   */
  private $nautilus;

  public function __construct() {
    parent::__construct();
    $this->nautilus = \ProcessWire\wire('modules')->get('Nautilus');
  }

  /**
   * Ensure Valitron library is loaded
   * 
   * @throws \ProcessWire\WireException If Valitron library is not found
   * @return void
   */
  private function ensureInitialized() {
    if ($this->initialized) {
      return;
    }

    $validatorPath = $this->nautilus->path() . 'lib/valitron/src/Valitron/Validator.php';
    if (!file_exists($validatorPath)) {
      throw new WireException("Valitron library not found at: {$validatorPath}");
    }
    require_once $validatorPath;
    $this->initialized = true;
  }

  /**
   * Main validation method - validates data with rules, labels, and language support
   * 
   * @param array $data The data to validate
   * @param array $rules Array of validation rules
   * @param array $labels Optional custom field labels
   * @param string $lang Optional language code (auto-detected if not provided)
   * @return array Returns array with 'valid' (boolean) and 'errors' (array) keys
   */
  public function validate($data, $rules = array(), $labels = array(), $lang = null) {
    // Ensure Valitron library is loaded
    $this->ensureInitialized();

    // Auto-detect language from ProcessWire if not provided
    if ($lang === null) {
      if ($this->wire('user') && $this->wire('user')->language) {
        $lang = $this->wire('user')->language->name;
      } else {
        $lang = 'en';
      }
    }

    // If lang is "default", set it to "en"
    $lang = $lang == "default" ? "en" : $lang;

    // Set the language directory to use Valitron's built-in language files
    $langDir = $this->nautilus->path() . 'lib/valitron/lang';

    // Check if Valitron class exists
    if (!class_exists('\Valitron\Validator')) {
      throw new WireException("Valitron\\Validator class not found. Make sure the library is properly loaded.");
    }

    // Create validator with language support
    $validator = new \Valitron\Validator($data, array(), $lang, $langDir);

    // Apply custom labels
    if (!empty($labels) && is_array($labels)) {
      $validator->labels($labels);
    }

    // Apply rules
    foreach ($rules as $field => $fieldRules) {
      if (is_array($fieldRules)) {
        foreach ($fieldRules as $rule => $ruleValue) {
          if (is_numeric($rule)) {
            // Rule without parameters: ['required', 'email']
            $validator->rule($ruleValue, $field);
          } else {
            // Rule with parameters: ['min' => 5, 'max' => 10]
            $validator->rule($rule, $field, $ruleValue);
          }
        }
      } else {
        // Single rule: 'required'
        $validator->rule($fieldRules, $field);
      }
    }

    $isValid = $validator->validate();

    return array(
      'valid' => $isValid,
      'errors' => $validator->errors(),
      'validator' => $validator
    );
  }
}
