<?php

/**
 * Auth Class - API Authentication Handler
 * 
 * This class provides authentication functionality for API requests using ProcessWire.
 * It supports authentication via API keys stored in user fields.
 * 
 * Features:
 * - API key authentication
 * - CORS handling
 * - IP and domain validation support
 * - Superuser bypass
 * 
 * Requirements:
 * - ProcessWire CMS
 * - User fields: api_key, ip (optional), website (optional)
 * 
 * Apache Configuration:
 * Add to .htaccess to pass authorization header:
 * RewriteEngine On
 * RewriteCond %{HTTP:Authorization} ^(.*)
 * RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
 * 
 * Usage:
 * $auth = new Auth();
 * $auth->auth(); // Will exit with error if unauthorized
 * 
 * @package AdminHelper - Auth
 * @author Ivan Milincic
 * @version 1.0
 */

namespace Nautilus;

require_once(__DIR__ . '/JSON.php'); // Ensure JSON class is loaded

use \ProcessWire\WireData;

class Auth extends WireData {

  public $nautilus;
  public $request;

  /**
   * Constructor - Initialize request handler
   */
  public function __construct() {
    $this->nautilus = wire('modules')->get('Nautilus');
    $this->request = $this->nautilus->loadClass('Request');
  }

  /**
   * Set CORS headers to allow cross-origin requests
   * 
   * @return void
   */
  public function CORS() {
    header("Access-Control-Allow-Origin: *");  // Replace * with your actual domain for better security
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    // Allow iframes from any origin
    header("Content-Security-Policy: frame-ancestors *");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(200);
      exit();
    }
  }

  /**
   * Authenticate request and exit with error if unauthorized
   * 
   * @param bool $debug Enable debug information in response
   * @param bool $skip Skip authentication (for testing)
   * @return void
   */
  public function auth($debug = false, $skip = false) {
    if ($skip) return;

    if ($this->isAuth()) return;

    // Return JSON error response and exit
    JSON::response([
      'status' => 'error',
      'message' => 'Unauthorized',
      'api_key' => $this->request->data('api_key'),
      'debug' => $debug ? $this->request->data() : false,
    ]);
    exit();
  }

  /**
   * Check if current request is authenticated
   * 
   * Authentication hierarchy:
   * 1. Superuser is always authenticated
   * 2. Login route is always allowed
   * 3. Valid API key holder
   * 
   * @return bool True if authenticated, false otherwise
   */
  public function isAuth() {
    // Get request data
    $req = $this->request->data();

    // Superuser bypass
    if ($this->user->isSuperuser()) return true;

    // Login route bypass
    if ($this->input->urlSegment1 === 'login') return true;

    // Check for valid API user
    $apiUser = $this->apiUser($req);
    if ($apiUser) return true;

    return false;
  }

  /**
   * Get user based on API key
   * 
   * @param array $req Request data containing api_key
   * @return \ProcessWire\User|false User object if found, false otherwise
   */
  public function apiUser($req) {
    // Validate API key exists and is not empty
    $api_key = $req['api_key'] ?? '';
    if (empty($api_key)) return false;

    // Find user with matching API key
    $api_user = $this->users->get("api_key=" . $this->sanitizer->text($api_key));

    // Check if user exists and has valid ID
    if (!$api_user || !$api_user->id) return false;

    return $api_user;
  }
}
