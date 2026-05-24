<?php

/**
 * Request Class
 * 
 * Handles HTTP request data extraction and processing from $_SERVER superglobal.
 * Provides methods to safely retrieve client information, IP addresses, and server details.
 * 
 * Features:
 * - API key extraction from headers or query parameters
 * - IP address validation and retrieval with proxy support
 * - Request metadata collection (method, URI, user agent, etc.)
 * - Cloudflare integration for country detection
 *
 * @package Nautilus - Request
 * @author Ivan Milincic
 * @version 1.0
 */

namespace Nautilus;

class Request {

  /**
   * Get array of request data or specific key value
   * 
   * @param string $key Optional key to retrieve specific data
   * @return array|string|null Complete data array or specific value
   */
  public function data(string $key = "") {
    $array = [
      'api_key' => $this->getApiKey(),
      'server' => $_SERVER,
      'method' => $this->getRequestMethod(),
      'ip' => $this->getIPAddress(),
      'public_ip' => $this->getPublicIp(),
      'server_ip' => $this->getServerIp(),
      'domain' => $this->getDomainName(),
      'agent' => $this->getUserAgent(),
      'referer' => $this->getReferer(),
      'uri' => $this->getRequestUri(),
      'query' => $this->getQueryString(),
      'port' => $this->getServerPort(),
      'country' => $this->getCountry(),
    ];
    return $key !== "" ? ($array[$key] ?? null) : $array;
  }

  /**
   * Extract API key from authorization header or query parameter
   * 
   * Priority: GET parameter > Authorization header
   * 
   * Apache configuration required for authorization header:
   * RewriteEngine On
   * RewriteCond %{HTTP:Authorization} ^(.*)
   * RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
   * 
   * @return string API key or empty string if not found
   */
  public function getApiKey(): string {
    if (isset($_GET['api_key']) && !empty($_GET['api_key'])) {
      return sanitize_text_field($_GET['api_key']);
    }
    return $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  }

  /**
   * Get public IP address without validation
   * 
   * Checks multiple headers in order of preference:
   * 1. HTTP_CLIENT_IP (shared internet/ISP IP)
   * 2. HTTP_X_FORWARDED_FOR (proxy forwarded IP)
   * 3. REMOTE_ADDR (direct connection IP)
   * 
   * @return string IP address (may be invalid)
   */
  public function getPublicIp(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // Handle comma-separated list of IPs
      $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      return trim($ips[0]);
    } else {
      return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
  }

  /**
   * Get validated IP address
   * 
   * Returns the first valid IP address found, checking multiple sources
   * with proper validation using FILTER_VALIDATE_IP
   * 
   * @return string Valid IP address or '0.0.0.0' as fallback
   */
  public function getIPAddress(): string {
    // Check for remote IP address first (most reliable)
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
      return $_SERVER['REMOTE_ADDR'];
    }

    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
      return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IP address passed by proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      foreach ($ips as $ip) {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
          return $ip;
        }
      }
    }

    return '0.0.0.0';
  }

  /**
   * Get the server IP address from which request is sent
   * 
   * @return string Server IP address or '0.0.0.0' as fallback
   */
  public function getServerIp(): string {
    return $_SERVER['HTTP_X_SERVER_ADDR'] ?? $_SERVER['SERVER_ADDR'] ?? '0.0.0.0';
  }

  /**
   * Get domain name to which request is sent
   * 
   * @return string Domain name or 'unknown' as fallback
   */
  public function getDomainName(): string {
    return $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'unknown';
  }

  /**
   * Get user agent string
   * 
   * @return string User agent or 'unknown' as fallback
   */
  public function getUserAgent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
  }

  /**
   * Get referer URL (domain from which request originated)
   * 
   * @return string Referer URL or 'unknown' as fallback
   */
  public function getReferer(): string {
    return $_SERVER['HTTP_REFERER'] ?? 'unknown';
  }

  /**
   * Get HTTP request method
   * 
   * @return string Request method (GET, POST, etc.) or 'unknown' as fallback
   */
  public function getRequestMethod(): string {
    return $_SERVER['REQUEST_METHOD'] ?? 'unknown';
  }

  /**
   * Get content type of the request
   * 
   * Used for JSON requests ("application/json") and form data
   * 
   * @return string Content type or 'unknown' as fallback
   */
  public function getContentType(): string {
    return $_SERVER['CONTENT_TYPE'] ?? 'unknown';
  }

  /**
   * Get request URI
   * 
   * @return string Request URI or 'unknown' as fallback
   */
  public function getRequestUri(): string {
    return $_SERVER['REQUEST_URI'] ?? 'unknown';
  }

  /**
   * Get query string parameters
   * 
   * @return string Query string or empty string if none
   */
  public function getQueryString(): string {
    return $_SERVER['QUERY_STRING'] ?? '';
  }

  /**
   * Get server port number
   * 
   * @return string Server port or 'unknown' as fallback
   */
  public function getServerPort(): string {
    return $_SERVER['SERVER_PORT'] ?? 'unknown';
  }

  /**
   * Get country code from Cloudflare
   * 
   * Requires Cloudflare proxy to be enabled
   * 
   * @return string Two-letter country code or 'unknown' as fallback
   */
  public function getCountry(): string {
    return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'unknown';
  }
}
