<?php

namespace NativePHP\Clipboard;

class Clipboard
{
    /**
     * Write plain text to the system clipboard.
     */
    public function writeText(string $text): bool
    {
        if (function_exists('nativephp_call')) {
            $result = nativephp_call('Clipboard.WriteText', json_encode([
                'text' => $text,
            ]));

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
