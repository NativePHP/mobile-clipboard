<?php

namespace NativePHP\Clipboard;

use InvalidArgumentException;

class Clipboard
{
    /**
     * Write plain text to the system clipboard.
     *
     * For one-time codes, passwords and other secrets:
     *  - $sensitive marks the copy as sensitive. Android 13+ hides it in the
     *    copy preview and keyboards don't suggest it; iOS keeps it off
     *    Universal Clipboard (the user's other Apple devices).
     *  - $expiresIn clears the copy after that many seconds, but only while
     *    the clipboard still holds it: anything copied since is left alone.
     *
     * @throws InvalidArgumentException when $expiresIn is less than 1
     */
    public function writeText(string $text, bool $sensitive = false, ?int $expiresIn = null): bool
    {
        if ($expiresIn !== null && $expiresIn < 1) {
            throw new InvalidArgumentException('expiresIn must be at least 1 second.');
        }

        $payload = ['text' => $text];

        if ($sensitive) {
            $payload['sensitive'] = true;
        }

        if ($expiresIn !== null) {
            $payload['expires_in'] = $expiresIn;
        }

        if (function_exists('nativephp_call')) {
            $result = nativephp_call('Clipboard.WriteText', json_encode($payload));

            if ($result) {
                $decoded = json_decode($result, true);

                return (bool) ($decoded['success'] ?? false);
            }
        }

        return false;
    }

    /**
     * Read plain text from the system clipboard.
     * Returns null when the clipboard holds no text (or off-device).
     */
    public function readText(): ?string
    {
        if (function_exists('nativephp_call')) {
            $result = nativephp_call('Clipboard.ReadText', json_encode([]));

            if ($result) {
                $decoded = json_decode($result, true);
                $text = $decoded['text'] ?? null;

                return ($text === '' || $text === null) ? null : $text;
            }
        }

        return null;
    }
}
