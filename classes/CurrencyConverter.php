<?php

namespace Nautilus;

use ProcessWire\WireData;

/**
 * Currency Converter Class
 * 
 * A utility class for converting currencies using the Frankfurter API (free exchange rates API).
 * Provides real-time currency conversion, supported currencies list, and currency formatting.
 *
 * Usage Examples:
 * 
 * Basic currency conversion:
 * $converter = new CurrencyConverter();
 * $result = $converter->convert([
 *   'amount' => 100,
 *   'from' => 'USD',
 *   'to' => 'EUR'
 * ]);
 * 
 * Get supported currencies:
 * $currencies = $converter->getSupportedCurrencies();
 * 
 * Format currency:
 * $formatted = $converter->format(123.45, 'EUR', 2); // €123.45
 * 
 * Requirements:
 * - Internet connection for API access
 * - Valid currency codes (ISO 4217)
 * - Frankfurter API availability
 * 
 * API Source: https://api.frankfurter.dev
 */
class CurrencyConverter extends WireData {

  /**
   * Convert currency using Frankfurter API
   * 
   * @param array $params
   * @param float $params['amount'] - amount to convert
   * @param string $params['from'] - currency to convert from eg: USD
   * @param string $params['to'] - currency to convert to eg: EUR
   * 
   * @return float|false Returns converted amount or false on error
   */
  public function convert($params = []) {
    // Validate required parameters
    if (!isset($params['amount']) || !isset($params['from']) || !isset($params['to'])) {
      $this->error("Missing required parameters: amount, from, to");
      return false;
    }

    $amount = (float) $params['amount'];
    $from = strtoupper($params['from']);
    $to = strtoupper($params['to']);

    // If same currency, return original amount
    if ($from === $to) {
      return $amount;
    }

    try {
      $url = "https://api.frankfurter.dev/v1/latest?amount=$amount&from=$from&to=$to";
      $response = file_get_contents($url);

      if ($response === false) {
        $this->error("Failed to fetch currency data from API");
        return false;
      }

      $data = json_decode($response, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->error("Invalid JSON response from API");
        return false;
      }

      if (!isset($data['rates'][$to])) {
        $this->error("Currency conversion rate not found for $to");
        return false;
      }

      return (float) $data['rates'][$to];
    } catch (Exception $e) {
      $this->error("Currency conversion error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get list of supported currencies
   * 
   * @return array|false Array of currencies or false on error
   */
  public function getSupportedCurrencies() {
    try {
      $url = "https://api.frankfurter.dev/v1/currencies";
      $response = file_get_contents($url);

      if ($response === false) {
        $this->error("Failed to fetch currencies from API");
        return false;
      }

      $data = json_decode($response, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->error("Invalid JSON response from API");
        return false;
      }

      return $data;
    } catch (Exception $e) {
      $this->error("Error fetching currencies: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Format currency value
   * 
   * @param float $amount
   * @param string $currency
   * @param int $decimals
   * 
   * @return string
   */
  public function format($amount, $currency = 'EUR', $decimals = 2) {
    $symbols = [
      'USD' => '$',
      'EUR' => '€',
      'GBP' => '£',
      'JPY' => '¥',
      'CHF' => 'CHF',
      'CAD' => 'C$',
      'AUD' => 'A$',
    ];

    $symbol = isset($symbols[$currency]) ? $symbols[$currency] : $currency;

    return $symbol . number_format($amount, $decimals);
  }
}
