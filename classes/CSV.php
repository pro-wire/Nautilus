<?php

namespace Nautilus;

use ProcessWire\WireData;

/**
 * CSV Helper Class
 * 
 * A comprehensive utility class for handling CSV file operations including validation,
 * parsing, creation, and statistical analysis.
 * 
 * Features:
 * - CSV file validation (existence, readability, encoding, size, delimiter)
 * - Parse CSV files and strings into associative arrays
 * - Export arrays to CSV format (string or file)
 * - Automatic delimiter detection
 * - File statistics and analysis
 * - UTF-8 encoding validation
 * - Configurable field name sanitization
 * 
 * Usage Examples:
 * 
 * Basic CSV parsing:
 * $csv = new CSV();
 * $data = $csv->parse_csv_file('/path/to/file.csv');
 * 
 * Export array to CSV:
 * $csv_string = $csv->array_to_csv($data);
 * $csv->array_to_csv_file($data, '/path/to/output.csv');
 * 
 * Get file statistics:
 * $stats = $csv->get_csv_stats('/path/to/file.csv');
 * 
 * Parse CSV string:
 * $data = $csv->parse_csv_string($csv_content);
 * 
 * Requirements:
 * - CSV files must use UTF-8 encoding
 * - Maximum file size: 10MB
 * - Supports comma, semicolon, tab, and pipe delimiters
 * - Comma delimiter is preferred and enforced in validation
 */
class CSV extends WireData {

  /**
   * Check if csv file is valid
   * - file exists and is readable
   * - delimiter is comma
   * - file is UTF-8
   * - file size is reasonable
   * @param string $file_path
   * @return bool
   */
  public function is_csv_valid($file_path) {

    // we will store errors here
    $errors = [];

    // check if file exists and is readable
    if (!file_exists($file_path)) {
      $errors[] = __('CSV file does not exist');
    } elseif (!is_readable($file_path)) {
      $errors[] = __('CSV file is not readable');
    } elseif (!is_file($file_path)) {
      $errors[] = __('Path is not a file');
    } else {
      // check file size (max 10MB)
      $maxSize = 10 * 1024 * 1024; // 10MB
      if (filesize($file_path) > $maxSize) {
        $errors[] = __('CSV file is too large (max 10MB)');
      } else {
        // get file delimiter
        $delimiter = $this->detectDelimiter($file_path);

        // check delimiter
        if ($delimiter != ',') {
          $errors[] = __('CSV file not valid. Please use a comma (,) as delimiter in your CSV file');
        }

        // check if file is UTF-8 (read first 1KB for performance)
        $sample = file_get_contents($file_path, false, null, 0, 1024);
        if (!mb_check_encoding($sample, 'UTF-8')) {
          $errors[] = __('CSV file not valid, not UTF-8');
        }
      }
    }

    // if there is an error, return false and set error notices
    if (count($errors) > 0) {
      foreach ($errors as $error) {
        $this->error($error, Notice::allowMarkup);
      }
      return false;
    }

    return true;
  }

  /**
   * Parse CSV file
   * as $key => $value array
   * @param string $csv_file - full file path
   * @param bool $sanitize - sanitize keys
   * @return array|false - returns false on error
   */
  public function parse_csv_file($csv_file, $sanitize = true) {

    // validate file first
    if (!$this->is_csv_valid($csv_file)) {
      return false;
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) {
      $this->error(__('Cannot open CSV file for reading'), Notice::allowMarkup);
      return false;
    }

    // get header row
    $header = fgetcsv($handle);
    if (!$header) {
      fclose($handle);
      $this->error(__('CSV file appears to be empty or invalid'), Notice::allowMarkup);
      return false;
    }

    $csv = [];

    if ($sanitize) {
      foreach ($header as $key => $value) {
        $val = preg_replace('/\s+/', ' ', trim($value)); // remove extra whitespace
        $val = $this->sanitizer->fieldName($val); // field name sanitizer
        $val = strtolower($val); // lowercase
        $header[$key] = $val;
      }
    }

    // read data rows
    while (($row = fgetcsv($handle)) !== false) {
      // skip empty rows
      if (count(array_filter($row, function ($value) {
        return trim($value) !== '';
      })) > 0) {
        // pad row if it has fewer columns than header
        $row = array_pad($row, count($header), '');
        $csv[] = array_combine($header, $row);
      }
    }

    fclose($handle);
    return $csv;
  }

  /**
   * Detect CSV delimiter
   * @param string $csvFile Path to the CSV file
   * @return string|false Delimiter or false on error
   */
  public function detectDelimiter($csvFile) {
    $delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

    $handle = fopen($csvFile, "r");
    if (!$handle) {
      return false;
    }

    $firstLine = fgets($handle);
    fclose($handle);

    if (!$firstLine) {
      return false;
    }

    foreach ($delimiters as $delimiter => &$count) {
      $count = count(str_getcsv($firstLine, $delimiter));
    }

    return array_search(max($delimiters), $delimiters);
  }

  /**
   * Parse CSV string
   * Parse CSV content from a string instead of file
   * @param string $csv_string - CSV content as string
   * @param bool $sanitize - sanitize keys
   * @return array|false - returns false on error
   */
  public function parse_csv_string($csv_string, $sanitize = true) {
    $lines = explode("\n", $csv_string);
    if (empty($lines)) {
      $this->error(__('CSV string is empty'), Notice::allowMarkup);
      return false;
    }

    // get header row
    $header = str_getcsv($lines[0]);
    if (!$header) {
      $this->error(__('CSV header appears to be invalid'), Notice::allowMarkup);
      return false;
    }

    $csv = array();

    if ($sanitize) {
      foreach ($header as $key => $value) {
        $val = preg_replace('/\s+/', ' ', trim($value)); // remove extra whitespace
        $val = $this->sanitizer->fieldName($val); // field name sanitizer
        $val = strtolower($val); // lowercase
        $header[$key] = $val;
      }
    }

    // read data rows
    for ($i = 1; $i < count($lines); $i++) {
      $row = str_getcsv($lines[$i]);
      if (!empty($row)) {
        // skip empty rows
        if (count(array_filter($row, function ($value) {
          return trim($value) !== '';
        })) > 0) {
          // pad row if it has fewer columns than header
          $row = array_pad($row, count($header), '');
          $csv[] = array_combine($header, $row);
        }
      }
    }

    return $csv;
  }

  /**
   * Export array to CSV string
   * @param array $data - array of associative arrays
   * @param array $headers - optional custom headers
   * @return string|false - CSV string or false on error
   */
  public function array_to_csv($data, $headers = null) {
    if (empty($data) || !is_array($data)) {
      $this->error(__('Data must be a non-empty array'), Notice::allowMarkup);
      return false;
    }

    $output = '';
    $first_row = reset($data);

    // Use provided headers or keys from first row
    if ($headers === null) {
      $headers = array_keys($first_row);
    }

    // Add header row
    $output .= '"' . implode('","', $headers) . '"' . "\n";

    // Add data rows
    foreach ($data as $row) {
      $csv_row = array();
      foreach ($headers as $header) {
        $value = isset($row[$header]) ? $row[$header] : '';
        // Escape quotes and wrap in quotes
        $csv_row[] = '"' . str_replace('"', '""', $value) . '"';
      }
      $output .= implode(',', $csv_row) . "\n";
    }

    return $output;
  }

  /**
   * Export array to CSV file
   * @param array $data - array of associative arrays
   * @param string $file_path - full file path to save
   * @param array $headers - optional custom headers
   * @return bool - true on success, false on error
   */
  public function array_to_csv_file($data, $file_path, $headers = null) {
    $csv_string = $this->array_to_csv($data, $headers);
    if ($csv_string === false) {
      return false;
    }

    $result = file_put_contents($file_path, $csv_string);
    if ($result === false) {
      $this->error(__('Cannot write to CSV file'), Notice::allowMarkup);
      return false;
    }

    return true;
  }

  /**
   * Get CSV file statistics
   * @param string $csv_file - full file path
   * @return array|false - statistics array or false on error
   */
  public function get_csv_stats($csv_file) {
    if (!$this->is_csv_valid($csv_file)) {
      return false;
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) {
      return false;
    }

    $stats = array(
      'total_rows' => 0,
      'data_rows' => 0,
      'columns' => 0,
      'file_size' => filesize($csv_file),
      'delimiter' => $this->detectDelimiter($csv_file)
    );

    // get header row
    $header = fgetcsv($handle);
    if ($header) {
      $stats['columns'] = count($header);
      $stats['total_rows'] = 1;
    }

    // count data rows
    while (($row = fgetcsv($handle)) !== false) {
      $stats['total_rows']++;
      // count non-empty rows
      if (count(array_filter($row, function ($value) {
        return trim($value) !== '';
      })) > 0) {
        $stats['data_rows']++;
      }
    }

    fclose($handle);
    return $stats;
  }
}
