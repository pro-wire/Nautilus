# Nautilus — Loading Classes

All Nautilus utility classes live in `site/modules/Nautilus/classes/`. They are not auto-included; you load them on demand.

---

## `$nautilus->loadClass(string $className, string $namespace = 'Nautilus'): object`

Include the class file and return a new instance.

```php
$pdf   = $nautilus->loadClass('PDF');
$email = $nautilus->loadClass('Email');
$auth  = $nautilus->loadClass('Auth');
```

The method:
1. Resolves the file at `site/modules/Nautilus/classes/{$className}.php`.
2. Includes it with `include_once`.
3. Returns `new {$namespace}\\{$className}()`.

A `WireException` is thrown if the file does not exist.

### Using a custom namespace

If you have extended a Nautilus class under a different namespace:

```php
$myPdf = $nautilus->loadClass('PDF', 'MyModule');
// returns new MyModule\PDF()
```

---

## `$nautilus->includeClass(string $className): bool`

Include the file without instantiating the class. Useful when you want to instantiate with custom constructor arguments.

```php
$nautilus->includeClass('OpenRouter');
$or = new Nautilus\OpenRouter($apiKey, 'openai/gpt-4o');
```

Returns `true` on success; throws `WireException` if the file is not found.

---

## Helper Methods

### `$nautilus->path(): string`

Absolute filesystem path to the Nautilus module directory.

```php
echo $nautilus->path();
// /var/www/html/site/modules/Nautilus/
```

### `$nautilus->url(): string`

URL to the Nautilus module directory.

```php
echo $nautilus->url();
// /site/modules/Nautilus/
```
