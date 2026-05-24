# Nautilus — Email

`Nautilus\Email` wraps ProcessWire's `wireMail()` with template support, page-field interpolation, and multiple attachments.

## Loading

```php
$email = wire('nautilus')->loadClass('Email');
```

---

## `$email->send(array $params): bool`

Sends an HTML email. Returns `true` on success, `false` on failure (errors are logged via `$this->error()`).

### Required parameters

| Parameter | Type | Description |
|---|---|---|
| `to` | `string` | Recipient email address (validated) |
| `from` | `string` | Sender email address (validated) |
| `subject` | `string` | Email subject line |

### Optional parameters

| Parameter | Type | Description |
|---|---|---|
| `fromName` | `string` | Display name for the sender |
| `replyTo` | `string` | Reply-to address (validated) |
| `body` | `string` | HTML body. Ignored if `email_template` or `email_template_page` is provided |
| `email_template` | `string` | Absolute path to an HTML file whose contents become the body |
| `email_template_page` | `int` | Page ID — uses the page's `body` field as the email body |
| `related_page` | `int` | Page ID — enables `{field}` and `{field.subfield}` replacement in subject and body |
| `data` | `array` | Key/value pairs for `{key}` replacement in subject and body |
| `attachment` | `string` | Single file path to attach |
| `attachments` | `string[]` | Array of file paths to attach |

---

## Examples

### Basic email

```php
$email = wire('nautilus')->loadClass('Email');

$result = $email->send([
    'to'      => 'client@example.com',
    'from'    => 'no-reply@mysite.com',
    'subject' => 'Welcome!',
    'body'    => '<h1>Welcome to our platform</h1>',
]);
```

### Email from an HTML template file

```php
$email->send([
    'to'             => 'client@example.com',
    'from'           => 'no-reply@mysite.com',
    'subject'        => 'Your account is ready',
    'email_template' => wire('config')->paths->templates . 'emails/welcome.html',
]);
```

### Using `{placeholder}` replacement with a page

```php
$client = $pages->get(42);

$email->send([
    'to'           => $client->email,
    'from'         => 'no-reply@mysite.com',
    'subject'      => 'Hello {title}',
    'body'         => '<p>Dear {title},<br>Your phone: {phone}</p>',
    'related_page' => $client->id,
]);
// Placeholders {title} and {phone} are replaced with $client->title and $client->phone
```

### Using nested field placeholders

```php
$email->send([
    'to'           => $project->contact->email,
    'from'         => 'no-reply@mysite.com',
    'subject'      => 'Project {title} Update',
    'body'         => '<p>Client: {client.title}<br>Manager: {manager.name}</p>',
    'related_page' => $project->id,
]);
// {client.title} resolves to $project->client->title
```

### Manual `{key}` replacement via `data` array

```php
$email->send([
    'to'      => 'user@example.com',
    'from'    => 'no-reply@mysite.com',
    'subject' => 'Invoice #{invoice_number}',
    'body'    => '<p>Total: {total}</p>',
    'data'    => [
        'invoice_number' => 'INV-2024-001',
        'total'          => '$1,250.00',
    ],
]);
```

### Email with attachments

```php
$email->send([
    'to'          => 'client@example.com',
    'from'        => 'no-reply@mysite.com',
    'subject'     => 'Your Documents',
    'body'        => 'Please find the attached documents.',
    'attachments' => [
        '/var/www/html/site/assets/files/123/invoice.pdf',
        '/var/www/html/site/assets/files/123/contract.pdf',
    ],
]);
```

---

## Template Placeholder Formats

### `{field}` — Direct field value

Replaced by `$page->{field}`.

### `{field.subfield}` — Sub-field value

Replaced by `$page->{field}->{subfield}` (e.g. a page reference field followed by one of its own fields).

### `{key}` with `data` array

Replaced by `$data['key']`. Takes precedence over page fields when both are provided (data is applied after page string formatting).

---

## Error Handling

The `send()` method logs errors via `$this->error()` and returns `false` in these cases:

- Invalid or missing `to` / `from` email address
- Missing `subject`
- `email_template` file not found
- Attachment file not found (warning, not fatal — email still sends)
- `wireMail()` returns a falsy value
- Exception thrown during send
