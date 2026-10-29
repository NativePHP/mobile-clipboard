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

### Copying secrets

For one-time codes, passwords and recovery keys, mark the copy sensitive and/or let it expire:

```php
Clipboard::writeText($code, sensitive: true, expiresIn: 60);
```

- `sensitive: true`: on Android the clip carries `ClipDescription.EXTRA_IS_SENSITIVE`, so Android 13+ hides it in the copy preview and keyboards don't suggest it. On iOS the copy stays on this device (not shared over Universal Clipboard).
- `expiresIn: 60`: the copy is removed after 60 seconds, **only if the clipboard still holds it**. Anything the user copied in the meantime is left alone.
  - iOS: handled by the system (`UIPasteboard` expiration date), even if the app is suspended or closed.
  - Android: the app clears it while its process is alive. If the user copies anything else first, or the app can't tell (it's in the background when the clipboard changes), nothing is cleared. Android 13+ also clears old clipboard contents on its own.

## Methods

### `writeText(string $text, bool $sensitive = false, ?int $expiresIn = null): bool`

Writes plain text to the system clipboard. Returns `true` once the text is on the clipboard, `false` on failure. `$sensitive` and `$expiresIn` (seconds, at least 1) are optional; see "Copying secrets" above. An `$expiresIn` below 1 throws `InvalidArgumentException`.

### `readText(): ?string`

Reads plain text from the system clipboard. Returns `null` when the clipboard is empty or holds no text.

Both methods degrade gracefully when the native bridge isn't available (running tests, CI): `writeText()` returns `false` and `readText()` returns `null` — no exception is thrown.

## Testing

The plugin extends the NativePHP testing suite with clipboard-specific helpers, so your app tests can fake and assert clipboard activity without knowing any bridge internals:

```php
use Native\Mobile\Testing\Native;

it('copies the invite link', function () {
    Native::fakeBridge()->withClipboard();

    Native::test(ShareSheet::class)
        ->tap('Copy link')
        ->assertCopied('https://example.com/invite/abc');
});

it('pastes into the form', function () {
    Native::fakeBridge()->withClipboard('+1 555 0100');

    Native::test(SignupScreen::class)
        ->tap('Paste number')
        ->assertSet('phone', '+1 555 0100');
});
```

### Helpers

- `withClipboard(string $text = '')` — fake the clipboard's contents. Reads return the current text; writes succeed and update it, so copy-then-paste flows behave like a real clipboard.
- `assertCopied(?string $text = null)` — assert something was copied, or exactly `$text` when given.
- `assertNothingCopied()` — assert no write happened.

The helpers are available on `Native::fakeBridge()` and chain directly off `Native::test(...)`. They register automatically while running tests (requires a core with a macroable FakeBridge; on older cores they simply don't register).
