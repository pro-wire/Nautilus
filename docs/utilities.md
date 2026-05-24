# Nautilus — Utilities

Reference for the remaining Nautilus utility classes: `Strings`, `Selector`, `Fields`, `CSV`, `CurrencyConverter`, `OpenRouter`, `PageToArrayConverter`, `PageArrayData`, and `Valitron`.

---

## Strings

`Nautilus\Strings` provides string interpolation for static data arrays and ProcessWire page objects.

```php
wire('nautilus')->includeClass('Strings');
// Class available as Nautilus\Strings
```

### `Strings::replace(string $string, array $data): string`

Replace `{key}` tokens in a string with values from an associative array.

```php
$result = Nautilus\Strings::replace(
    'Hello {name}, your invoice is {invoice_number}.',
    ['name' => 'John', 'invoice_number' => 'INV-001']
);
// Hello John, your invoice is INV-001.
```

### `Strings::formatPageString(string $string, Page $page): string`

Replace `{field}` and `{relation.field}` tokens using a ProcessWire Page object.

```php
$result = Nautilus\Strings::formatPageString(
    'Project: {title}, Client: {client.title}, Manager: {client.manager.name}',
    $projectPage
);
```

- `{field}` — calls `$page->{field}`
- `{relation.field}` — calls `$page->{relation}->{field}` (one level of relation traversal)

---

## Selector

`Nautilus\Selector` (class name `Selector` — note: singular) provides static helper methods that return ProcessWire selector fragments. Combine them with `implode(', ', [...])` or just concatenate.

```php
wire('nautilus')->includeClass('Selectors'); // file is Selectors.php
use Nautilus\Selector;
```

### Status selectors

| Method | Selector fragment |
|---|---|
| `Selector::all()` | `status>0` |
| `Selector::published()` | `status=1` |
| `Selector::unpublished()` | `status!=1` |
| `Selector::hidden()` | `status=hidden` |

### Field empty/not-empty

```php
Selector::isEmpty('email')    // email=''
Selector::isNotEmpty('email') // email!=''
```

### User

```php
Selector::user($user)         // created_users_id={$user->id}
```

### Date range

```php
// Period between two dates (any field)
Selector::period('2024-01-01', '2024-03-31')          // date>=2024-01-01, date<=2024-03-31
Selector::period('2024-01-01', '2024-03-31', 'created') // created>=..., created<=...

// Month (defaults to current month)
Selector::month()
Selector::month(['month' => '2024-06', 'key' => 'invoice_date'])

// Year
Selector::year()
Selector::year(['year' => '2023', 'key' => 'created'])

// Single date
Selector::date('2024-06-15')
Selector::date('2024-06-15', 'due_date')

// Relative dates
Selector::startDate()    // date>=today 00:00
Selector::endDate()      // date<=today 23:59
Selector::beforeDate()   // date<today
Selector::afterDate()    // date>today
Selector::lastMonth()    // previous calendar month
Selector::thisMonth()    // current calendar month
Selector::lastWeek()     // previous Mon–Sun
Selector::thisWeek()     // current Mon–Sun
```

### Example — finding pages

```php
wire('nautilus')->includeClass('Selectors');

$selectors = implode(', ', [
    'template=invoice',
    Nautilus\Selector::isNotEmpty('paid_date'),
    Nautilus\Selector::thisMonth(),
    'sort=-created',
]);

$invoices = $pages->find($selectors);
```

---

## Fields

`Nautilus\Fields` provides helpers for reading field metadata.

```php
$fields = wire('nautilus')->loadClass('Fields');
```

### `$fields->get_options(string $fieldName): array`

Return all selectable options for a `Select`, `SelectMultiple`, or `Checkboxes` field as an associative array of `id => label`.

```php
$statuses = $fields->get_options('project_status');
// [1 => 'Active', 2 => 'On Hold', 3 => 'Closed']
```

### `$fields->get_fieldset_fields(Template $template, string $fieldsetName, array $exclude = []): array`

Return all field names inside a named `FieldsetOpen`/`FieldsetClose` block on a template.

```php
$template = wire('templates')->get('project');
$billingFields = $fields->get_fieldset_fields($template, 'billing_info', ['internal_notes']);
```

---

## CSV

`Nautilus\CSV` handles CSV file validation, parsing, export, and statistics.

```php
$csv = wire('nautilus')->loadClass('CSV');
```

### Validation

```php
// Validate file path
$valid = $csv->is_csv_valid('/path/to/file.csv');
// Returns true if: file exists, extension is .csv, MIME type is text/plain or text/csv, size <= 10 MB
```

### Parsing

```php
// Parse a file
$rows = $csv->parse_csv_file('/path/to/import.csv');
// Returns array of associative arrays keyed by header row

// Parse a string
$rows = $csv->parse_csv_string($csvString);
// Returns same structure
```

The parser:
- Handles UTF-8 including BOM
- Auto-detects delimiter via `detectDelimiter()` (checks `,`, `;`, `\t`, `|`)
- Uses the first row as column headers
- Skips empty lines

### Export

```php
// Convert array to CSV string
$csvString = $csv->array_to_csv($rows);

// Write array to a CSV file
$csv->array_to_csv_file($rows, '/var/www/html/site/assets/files/export.csv');
```

### Statistics

```php
$stats = $csv->get_csv_stats('/path/to/file.csv');
// Returns: ['rows' => 150, 'columns' => 8, 'size' => 12345, 'headers' => [...]]
```

---

## CurrencyConverter

`Nautilus\CurrencyConverter` uses the free [Frankfurter API](https://www.frankfurter.app/) for real-time exchange rates (no API key required).

```php
$cc = wire('nautilus')->loadClass('CurrencyConverter');
```

### `$cc->convert(array $params): float`

```php
$amount = $cc->convert([
    'amount' => 100,
    'from'   => 'USD',
    'to'     => 'EUR',
]);
```

### `$cc->getSupportedCurrencies(): array`

Returns the list of currencies supported by Frankfurter.

```php
$currencies = $cc->getSupportedCurrencies();
// ['AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'GBP', ...]
```

### `$cc->format(float $amount, string $currency, int $decimals = 2): string`

Format a numeric amount as a currency string.

```php
echo $cc->format(1250.5, 'USD'); // $1,250.50
echo $cc->format(1250.5, 'EUR'); // €1,250.50
```

---

## OpenRouter

`Nautilus\OpenRouter` is an API client for [OpenRouter](https://openrouter.ai/), which provides a unified interface to many AI models (GPT-4o, Claude, Llama, etc.).

```php
wire('nautilus')->includeClass('OpenRouter');
$or = new Nautilus\OpenRouter($apiKey, 'openai/gpt-4o');
```

### Constructor

```php
new Nautilus\OpenRouter(string $apiKey, string $model = 'openai/gpt-4o')
```

### `$or->request(array $messages): array`

Send a chat-completion request. Returns the parsed response array.

```php
$response = $or->request([
    ['role' => 'user', 'content' => 'Summarize this contract in 3 bullet points.'],
]);
$text = $response['choices'][0]['message']['content'];
```

### `$or->req(array $messages): array`

Alias for `request()`.

### System instructions

```php
$or->setInstructions('You are a legal assistant specialised in Croatian law. Always answer in Croatian.');
// Automatically prepended as a 'system' role message on every request
```

### Model management

```php
$or->setModel('anthropic/claude-3-5-sonnet');

// Set multiple models for fallback or parallel use
$or->setModels(['openai/gpt-4o', 'anthropic/claude-3-5-sonnet']);

$or->setApiKey($newKey);
```

### Example — AI text extraction

```php
wire('nautilus')->includeClass('OpenRouter');

$or = new Nautilus\OpenRouter(wire('config')->openrouterApiKey, 'openai/gpt-4o');
$or->setInstructions('Extract structured data from documents. Reply only with valid JSON.');

$response = $or->request([
    ['role' => 'user', 'content' => 'Extract the client name, date, and total from: ' . $documentText],
]);

$data = json_decode($response['choices'][0]['message']['content'], true);
```

---

## PageToArrayConverter

`Nautilus\PageToArrayConverter` recursively converts a ProcessWire `Page` object to a plain PHP array.

```php
$converter = wire('nautilus')->loadClass('PageToArrayConverter');
$array = $converter->convert($page);
```

### Field type handling

| Field type | Output |
|---|---|
| Text, Integer, Float | Scalar value |
| Page reference (single) | Recursive `convert()` |
| PageArray | Array of converted pages |
| Repeater | Array of converted items |
| FieldsetPage | Converted field group |
| Files / Images | Array with `url`, `filename`, `description`, etc. |
| Other | Scalar cast or `(string)` |

### Circular reference protection

The converter tracks visited page IDs per-call and stops recursion after depth 10. Call `$converter->reset()` between calls if reusing the same instance.

### `$converter->reset()`

Clear the internal visited-page registry. Always call before reusing the converter for a different root page.

---

## PageArrayData

`Nautilus\PageArrayData` converts a `PageArray` to a structured data array, useful for passing to JSON responses or front-end templates.

```php
$pageArrayData = wire('nautilus')->loadClass('PageArrayData');
$data = $pageArrayData->convert($pages->find('template=client'));
```

---

## Valitron

`Nautilus\Valitron` is a wrapper around the [vlucas/valitron](https://github.com/vlucas/valitron) library (bundled at `lib/valitron/`). It handles library initialisation, language detection from the ProcessWire user language, and maps rules using a declarative array format.

```php
$v = wire('nautilus')->loadClass('Valitron');
```

> **Tip:** When AdminHelper is installed, use `$adminHelper->valitron()` instead — it wraps this class and requires no manual loading.

### `$v->validate($data, $rules, $labels, $lang): array`

| Parameter | Type | Description |
|---|---|---|
| `$data` | `array` | Field name → value pairs |
| `$rules` | `array` | Validation rules per field (see format below) |
| `$labels` | `array` | Optional human-readable field labels for error messages |
| `$lang` | `string\|null` | Language code; auto-detected from PW user language if `null` |

Returns an array with three keys:

| Key | Type | Description |
|---|---|---|
| `valid` | `bool` | `true` if all rules passed |
| `errors` | `array` | Field errors keyed by field name |
| `validator` | `\Valitron\Validator` | Raw Valitron instance for advanced use |

### Rules format

```php
$rules = [
    // Indexed rules (no parameter)
    'email'    => ['required', 'email'],

    // Named rules with a scalar parameter
    'age'      => ['required', 'integer', 'min' => 18, 'max' => 120],

    // Named rules with an array parameter
    'username' => ['required', 'alphaNum', 'lengthBetween' => [3, 20]],

    // Equals another field
    'confirm_password' => ['required', 'equals' => 'password'],
];
```

### Example

```php
$v = wire('nautilus')->loadClass('Valitron');

$result = $v->validate(
    ['email' => 'test@test.com', 'age' => 17],
    [
        'email' => ['required', 'email'],
        'age'   => ['required', 'integer', 'min' => 18],
    ],
    ['email' => 'Email Address', 'age' => 'Your Age']
);

if ($result['valid']) {
    // All rules passed
} else {
    foreach ($result['errors'] as $field => $messages) {
        echo $field . ': ' . implode(', ', $messages) . "\n";
    }
}
```

### Language support

Language is resolved in this order:

1. `$lang` parameter (if provided)
2. ProcessWire `$user->language->name`
3. Falls back to `en` if language is `default` or not detectable

Language files are loaded from `lib/valitron/lang/`.
