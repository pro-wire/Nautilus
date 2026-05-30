# Nautilus

Utility class library for ProcessWire applications. Provides on-demand loading of specialised helper classes via `loadClass()` and `includeClass()`. Not autoloaded — load it explicitly when needed.

## API variable

Not registered as a PW API variable. Access via:

```php
$nautilus = wire('modules')->get('Nautilus');
```

## Loading classes

```php
$nautilus = wire('modules')->get('Nautilus');

// Load and instantiate a class (namespace: Nautilus by default)
$csv = $nautilus->loadClass('CSV');          // returns new Nautilus\CSV()
$pdf = $nautilus->loadClass('PDF');
$ai  = $nautilus->loadClass('OpenRouter');

// Include the file only (for classes that need custom instantiation)
$nautilus->includeClass('Email');
$email = new Nautilus\Email();
```

## Available classes

All classes live in `site/modules/Nautilus/classes/` under the `Nautilus` namespace.

---

### `CSV`

CSV import and export.

```php
$csv = $nautilus->loadClass('CSV');

$rows  = $csv->parseFile('/path/to/file.csv');
$rows  = $csv->parseString($csvString);
$csv->exportToFile($rows, '/path/to/output.csv');
$stats = $csv->getFileStats('/path/to/file.csv'); // delimiter, row count, encoding
```

---

### `Email`

HTML email sending with template files.

```php
$email = $nautilus->loadClass('Email');
$email->send([
    'to'          => 'user@example.com',
    'subject'     => 'Hello',
    'template'    => '/path/to/email-template.html',
    'vars'        => ['name' => 'Ivan', 'link' => 'https://...'],
    'attachments' => ['/path/to/file.pdf'],
]);
// Template placeholders: {name}, {link}, {page.title} etc.
```

---

### `Fields`

ProcessWire field utilities.

```php
$fields = $nautilus->loadClass('Fields');
$options  = $fields->getOptions('my_select_field'); // returns select options array
$children = $fields->getFieldsetChildren('my_fieldset'); // fields inside a fieldset
```

---

### `OpenRouter`

AI inference via the OpenRouter API (supports all major models).

```php
$ai = $nautilus->loadClass('OpenRouter');
$ai->setApiKey('sk-...');
$ai->setModel('anthropic/claude-3-5-sonnet');
$ai->setSystemInstruction('You are a helpful assistant.');

$response = $ai->chat('Summarise this text: ' . $page->body);
// Returns string response or throws on error
```

---

### `PDF`

PDF generation using mPDF.

```php
$pdf = $nautilus->loadClass('PDF');

// From HTML string
$pdf->fromHtml('<h1>Hello</h1>', '/output/path.pdf');

// From a template file
$pdf->fromFile('/path/to/template.html', '/output/path.pdf', [
    'title' => $page->title,
    'body'  => $page->body,
]);
```

Requires mPDF to be installed via Composer: `composer require mpdf/mpdf`.

---

### `PageArrayData`

Convert PageArrays to structured arrays for JSON output.

```php
$pad = $nautilus->loadClass('PageArrayData');
$items = $pages->find('template=product');
$data  = $pad->toArray($items, ['id', 'title', 'price', 'url']);
$json  = $pad->toJson($items, ['id', 'title', 'price']);
```

---

### `PageToArrayConverter`

Recursive page-to-array conversion handling all field types including Repeaters, Page references, and Fieldsets.

```php
$converter = $nautilus->loadClass('PageToArrayConverter');
$data = $converter->convert($page, depth: 2);
// Returns nested array with all field values resolved
```

---

### `Request`

HTTP request inspection.

```php
$req = $nautilus->loadClass('Request');

$req->getApiKey();           // Bearer token or ?api_key query param
$req->getIp();               // Real IP (proxy-aware, Cloudflare-aware)
$req->getUserAgent(): string;
$req->getReferer(): string;
$req->getCountry(): string;  // ISO country code from CF-IPCountry header
```

---

### `Selectors`

Pre-built ProcessWire selector string fragments.

```php
$sel = $nautilus->loadClass('Selectors');

$sel->dateRange('created', '2024-01-01', '2024-12-31');  // "created>=1704067200, created<=1735689599"
$sel->thisMonth('created');
$sel->thisYear('modified');
$sel->published();           // "status=1"
$sel->unpublished();
$sel->fieldEmpty('body');    // "body=''"
$sel->fieldNotEmpty('images');
```

Compose with standard selectors:
```php
$pages->find("template=product, {$sel->published()}, {$sel->thisYear('created')}, sort=-created");
```

---

### `Strings`

Template string interpolation.

```php
$str = $nautilus->loadClass('Strings');

// Replace {var} placeholders
$result = $str->render('Hello {name}!', ['name' => 'Ivan']);

// Replace {page.field} placeholders from a Page object
$result = $str->renderPage('Title: {page.title} — Body: {page.body}', $page);

// Nested: {page.category.title}
$result = $str->renderPage('{page.category.title}', $page);
```

---

### `Valitron`

Form validation wrapper around the Valitron library.

```php
$v = $nautilus->loadClass('Valitron');

$result = $v->validate(
    ['email' => 'test@example.com', 'name' => 'Ivan'],
    ['email' => ['required', 'email'], 'name' => ['required', ['lengthMin', 2]]],
    ['email' => 'Email Address', 'name' => 'Your Name']
);

if ($result['valid']) {
    // proceed
} else {
    // $result['errors'] is an array of field => [messages]
}
```

Also accessible via `wire('adminHelper')->valitron($data, $rules, $labels)`.

---

### `CurrencyConverter`

Currency conversion via the Frankfurter API.

```php
$cc = $nautilus->loadClass('CurrencyConverter');
$rate   = $cc->getRate('USD', 'EUR');
$amount = $cc->convert(100, 'USD', 'EUR');
$list   = $cc->getCurrencyList();
```

---

### `Auth`

API key authentication helper (standalone, not the Auth module).

```php
$auth = $nautilus->loadClass('Auth');
$auth->validateApiKey();   // Checks header or ?api_key param against config keys
$auth->sendCorsHeaders();  // Send basic CORS headers
```

This class is a lightweight alternative to the Auth module for custom endpoints that do not use ApiRouter.

## Notes

- `autoload = false` — the module is not loaded unless explicitly requested.
- All classes are instantiated fresh on each `loadClass()` call (no singleton caching).
- Classes that depend on external libraries (PDF → mPDF, Valitron) will throw if the library is not installed.
