# Nautilus — PDF

`Nautilus\PDF` generates PDF documents from HTML using the [mPDF](https://mpdf.github.io/) library bundled at `lib/mpdf/`.

## Loading

```php
$pdf = wire('nautilus')->loadClass('PDF');
```

---

## Output Modes

| Mode | Description |
|---|---|
| `INLINE` | Stream PDF to the browser (default) |
| `DOWNLOAD` | Force browser download |
| `FILE` | Save to disk at the specified path |

---

## Methods

### `$pdf->html2pdf(string $html, array $options): void`

Convert an HTML string to a PDF document.

```php
$pdf->html2pdf('<h1>Invoice</h1><p>Amount: $500</p>', [
    'output'    => 'INLINE',
    'file_name' => 'invoice-001',
]);
```

### `$pdf->file2pdf(string $filePath, array $vars, array $options): void`

Include a PHP template file, capture its output, and convert to PDF.

```php
$pdf->file2pdf(
    wire('config')->paths->templates . 'pdf/invoice.php',
    ['page' => $invoicePage],
    ['output' => 'DOWNLOAD', 'file_name' => 'invoice-001']
);
```

The `$vars` array is extracted into local scope before the file is included.

### `$pdf->options(array $array): array`

Merge custom options with defaults. Useful to inspect or extend defaults before passing to `html2pdf()`.

---

## Options Reference

| Option | Type | Default | Description |
|---|---|---|---|
| `mode` | `string` | `utf-8` | Document encoding |
| `format` | `array` | `[210, 297]` | Page dimensions in mm |
| `orientation` | `string` | `P` | `P` (portrait) or `L` (landscape) |
| `margin_top` | `int` | `20` | Top margin in mm |
| `margin_bottom` | `int` | `20` | Bottom margin in mm |
| `margin_left` | `int` | `20` | Left margin in mm |
| `margin_right` | `int` | `20` | Right margin in mm |
| `margin_header` | `int` | `10` | Header area height in mm |
| `margin_footer` | `int` | `10` | Footer area height in mm |
| `output` | `string` | `INLINE` | `INLINE`, `DOWNLOAD`, or `FILE` |
| `dest` | `string` | `""` | Directory path for `FILE` output |
| `file_name` | `string` | `time()` | File name (without `.pdf`) |
| `header` | `string` | `""` | HTML header content |
| `footer` | `string` | `""` | HTML footer content |
| `stylesheet` | `string` | `""` | Path to a custom CSS file |
| `debug` | `int` | `0` | Show image errors (also auto-enabled by `$config->debug`) |

---

## Examples

### Inline PDF with header and footer

```php
$pdf->html2pdf('<h1>Report</h1><p>Content here</p>', [
    'output' => 'INLINE',
    'header' => '<div style="text-align:right">My Company</div>',
    'footer' => '<div style="text-align:center">{PAGENO} / {nbpg}</div>',
]);
```

### Download PDF from a template file

```php
$pdf->file2pdf(
    wire('config')->paths->templates . 'pdf/contract.php',
    [
        'client'   => $pages->get(42),
        'contract' => $pages->get(100),
    ],
    [
        'output'      => 'DOWNLOAD',
        'file_name'   => 'contract-' . date('Y-m-d'),
        'orientation' => 'P',
    ]
);
```

### Save PDF to disk

```php
$pdf->file2pdf(
    wire('config')->paths->templates . 'pdf/invoice.php',
    ['invoice' => $invoicePage],
    [
        'output'    => 'FILE',
        'dest'      => wire('config')->paths->assets . 'invoices/',
        'file_name' => 'invoice-' . $invoicePage->id,
    ]
);
```

### Multi-page PDF

```php
$pdf->generatePDF(
    [
        'cover'   => ['tmpl' => $tplPath . 'cover.php',   'title' => 'Annual Report 2024'],
        'chapter' => ['tmpl' => $tplPath . 'chapter.php', 'data'  => $chapterPage],
    ],
    [],   // shared variables
    ['output' => 'DOWNLOAD', 'file_name' => 'annual-report-2024']
);
```

Each entry in the pages array must contain `tmpl` (the file path). All other keys in that array are available as variables inside the included template.

---

## Custom Stylesheet

A default stylesheet is loaded from `lib/css/mpdf.css`. To add project-specific styles:

```php
$pdf->html2pdf($html, [
    'stylesheet' => wire('config')->paths->templates . 'css/pdf.css',
]);
```

---

## mPDF Licence

mPDF is licenced under the GNU GPL v2. See `lib/mpdf/` for the full licence text.
