# Nautilus — Request

`Nautilus\Request` provides a unified interface for reading HTTP request metadata, including API key extraction, client IP resolution, and Cloudflare country headers.

## Loading

```php
$request = wire('nautilus')->loadClass('Request');
```

---

## Input Data

### `$request->data(string $key): mixed`

Read a value from `$_REQUEST` (merges `$_GET`, `$_POST`, and `$_COOKIE`). Returns `null` if the key is not present.

```php
$name = $request->data('name');
$page = $request->data('page') ?? 1;
```

---

## API Key

### `$request->getApiKey(): string|null`

Extract the API key from the request. Checked in this order:

1. `$_GET['api_key']`
2. `$_SERVER['HTTP_AUTHORIZATION']` — stripped of any `Bearer ` prefix

```php
$key = $request->getApiKey(); // null if not present
```

---

## Client Information

### `$request->getIPAddress(): string`

Returns the client IP address. Checks the following headers in order (for proxy/load-balancer compatibility):

- `HTTP_CF_CONNECTING_IP` (Cloudflare)
- `HTTP_X_FORWARDED_FOR` (first IP in chain)
- `HTTP_CLIENT_IP`
- `REMOTE_ADDR`

### `$request->getPublicIp(): string|null`

Returns the first public (non-private, non-reserved) IP from `HTTP_X_FORWARDED_FOR`, or the value of `getIPAddress()` if it is public. Returns `null` if no public IP can be determined.

### `$request->getServerIp(): string`

Returns `$_SERVER['SERVER_ADDR']`.

### `$request->getDomainName(): string`

Returns `$_SERVER['HTTP_HOST']`.

### `$request->getUserAgent(): string`

Returns `$_SERVER['HTTP_USER_AGENT']`.

### `$request->getReferer(): string`

Returns `$_SERVER['HTTP_REFERER']`.

---

## Request Properties

### `$request->getRequestMethod(): string`

Returns the HTTP verb in uppercase: `GET`, `POST`, `PUT`, `DELETE`, etc.

### `$request->getContentType(): string`

Returns `$_SERVER['CONTENT_TYPE']` (e.g. `application/json`).

### `$request->getRequestUri(): string`

Returns `$_SERVER['REQUEST_URI']` — the full path including query string.

### `$request->getQueryString(): string`

Returns `$_SERVER['QUERY_STRING']`.

### `$request->getServerPort(): int`

Returns `$_SERVER['SERVER_PORT']` as an integer.

---

## Cloudflare Country

### `$request->getCountry(): string|null`

Returns the ISO 3166-1 alpha-2 country code from `$_SERVER['HTTP_CF_IPCOUNTRY']`. Requires Cloudflare proxying. Returns `null` when the header is absent.

```php
$country = $request->getCountry(); // 'HR', 'US', null
```

---

## Example — Geo-restriction in an API endpoint

```php
$auth    = wire('nautilus')->loadClass('Auth');
$request = wire('nautilus')->loadClass('Request');
$json    = wire('nautilus')->loadClass('JSON');

$auth->CORS();

$country = $request->getCountry();
if ($country && !in_array($country, ['HR', 'BA', 'SI'])) {
    ProcessWire\JSON::notification('forbidden', 'Region not supported');
}

$auth->auth();
// ... proceed with authenticated, region-allowed request
```

---

## Example — Log request metadata

```php
$request = wire('nautilus')->loadClass('Request');

$log->save('api-requests', implode(' | ', [
    $request->getRequestMethod(),
    $request->getRequestUri(),
    $request->getIPAddress(),
    $request->getUserAgent(),
]));
```
