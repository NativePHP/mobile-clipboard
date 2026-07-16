# Clipboard Plugin for NativePHP Mobile

System clipboard access (copy/paste plain text) for NativePHP Mobile applications.

## Overview

The Clipboard API reads and writes plain text on the system clipboard. Two methods, no permissions, no events.

## Installation

```bash
composer require nativephp/mobile-clipboard
php artisan native:plugin:register nativephp/mobile-clipboard
```

## Usage

```php
use NativePHP\Clipboard\Facades\Clipboard;

// Copy text to the clipboard
$copied = Clipboard::writeText('Hello from NativePHP!');   // true on success

// Read text from the clipboard
$text = Clipboard::readText();   // string, or null if the clipboard has no text

if ($text !== null) {
    // Use the pasted text
}
```

## Methods

### `writeText(string $text): bool`

Writes plain text to the system clipboard. Returns `true` once the text is on the clipboard, `false` on failure.

### `readText(): ?string`

Reads plain text from the system clipboard. Returns `null` when the clipboard is empty or holds no text.

Both methods degrade gracefully when the native bridge isn't available (running tests, CI): `writeText()` returns `false` and `readText()` returns `null` — no exception is thrown.
