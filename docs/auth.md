# Nautilus — Auth

`Nautilus\Auth` provides API key authentication for ProcessWire API endpoints, with optional CORS handling.

## Loading

```php
$auth = wire('nautilus')->loadClass('Auth');
```

`Auth` automatically instantiates a `Request` object internally.

---

## CORS

### `$auth->CORS()`

Sets permissive CORS headers and handles `OPTIONS` preflight requests by responding with `200` and exiting.

```php
$auth->CORS();
```

**Headers set:**

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Content-Security-Policy: frame-ancestors *
```

> For production, replace `*` with your specific domain in the `Access-Control-Allow-Origin` header.

---

## Authentication

### `$auth->auth(bool $debug = false, bool $skip = false)`

Authenticate the current request. If authentication fails, outputs a JSON error response and calls `exit()`.

```php
// Typical usage at the top of an API endpoint
$auth = wire('nautilus')->loadClass('Auth');
$auth->CORS();
$auth->auth();

// With debug info in the error response (development only)
$auth->auth(true);

// Skip auth entirely (for testing)
$auth->auth(false, true);
```

**Error response format:**

```json
{
    "status": "error",
    "message": "Unauthorized",
    "api_key": "the-received-key",
    "debug": false
}
```

### `$auth->isAuth(): bool`

Check authentication without exiting. Returns `true` if the request is authorised.

```php
if (!$auth->isAuth()) {
    // Handle unauthorised access gracefully
}
```

**Authentication hierarchy:**

1. Superuser is always authenticated.
2. URL segment `login` is always allowed (login route bypass).
3. A valid `api_key` matching a user's `api_key` field is authenticated.

---

## API Key Resolution

The API key is extracted from the request by the `Request` class:

1. `$_GET['api_key']` (query string)
2. `$_SERVER['HTTP_AUTHORIZATION']` header

### Apache `.htaccess` requirement

To pass the `Authorization` header through Apache to PHP:

```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

---

## User Field Requirements

Authentication looks up a ProcessWire user by the `api_key` field:

```php
$users->get("api_key=" . $sanitizer->text($api_key));
```

Your users must have an `api_key` field. Create it in the ProcessWire field editor as a `Text` field and add it to the `user` template.

---

## Getting the Authenticated User

```php
$auth = wire('nautilus')->loadClass('Auth');
$auth->CORS();
$auth->auth();

// After auth() passes, find the user
$request = wire('nautilus')->loadClass('Request');
$apiKey  = $request->getApiKey();
$apiUser = wire('users')->get("api_key=" . wire('sanitizer')->text($apiKey));

echo $apiUser->name; // authenticated user
```

---

## Full Endpoint Example

```php
<?php
// site/templates/api.php

$auth = wire('nautilus')->loadClass('Auth');
$auth->CORS();
$auth->auth();

// Request is authenticated from here
$segment = wire('input')->urlSegment1;

switch ($segment) {
    case 'clients':
        $clients = wire('pages')->find('template=client');
        $json    = wire('nautilus')->loadClass('JSON');
        // Return JSON array ...
        break;
    default:
        Nautilus\JSON::response(['status' => 'error', 'message' => 'Unknown endpoint']);
}
```
