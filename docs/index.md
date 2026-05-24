# Nautilus

Nautilus is a ProcessWire utility module that provides a collection of helper classes for common application tasks: PDF generation, email sending, JSON responses, API authentication, HTTP request inspection, CSV handling, currency conversion, AI integration, data formatting, and more.

## Requirements

- ProcessWire >= 3.0.0

## Module Info

| Property | Value |
|---|---|
| Version | 100 |
| Autoload | `false` (loaded on demand) |
| Singular | yes |

## Global Variable

```php
wire('nautilus')         // anywhere
$this->wire('nautilus')  // inside module methods
$nautilus                // when injected via ProcessWire fuel
```

## Available Classes

| Class | Description |
|---|---|
| `Auth` | API key authentication and CORS handling |
| `CSV` | CSV file parsing, validation, and export |
| `CurrencyConverter` | Real-time currency conversion via Frankfurter API |
| `Email` | HTML email sending with template and attachment support |
| `Fields` | Field options and fieldset introspection helpers |
| `OpenRouter` | OpenRouter AI API client |
| `PageArrayData` | PageArray to structured data converter |
| `PageToArrayConverter` | Recursive Page-to-array converter |
| `PDF` | HTML to PDF generation via mPDF |
| `Request` | HTTP request metadata and API key extraction |
| `Selector` | ProcessWire selector fragment helpers |
| `Strings` | String replacement and page-field interpolation |
| `Valitron` | Input validation (via Valitron library) |

## Documentation Index

- [Loading Classes](loading.md) — `loadClass()` and `includeClass()`
- [Auth](auth.md) — API authentication
- [Email](email.md) — sending emails
- [PDF](pdf.md) — generating PDF documents
- [Request](request.md) — HTTP request data
- [Utilities](utilities.md) — Strings, Fields, Selector, CSV, CurrencyConverter, OpenRouter, PageToArrayConverter, Valitron

## AdminHelper integration

When AdminHelper is installed, the `Valitron` class is accessible via a convenience wrapper that handles class loading and language detection automatically:

```php
// Use $adminHelper->valitron() instead of loading the class manually
$errors = $adminHelper->valitron($data, $rules, $labels);
// Returns true on success, or an array of field errors
```

See [AdminHelper — Validation](../../AdminHelper/docs/validation.md) for the full reference.
