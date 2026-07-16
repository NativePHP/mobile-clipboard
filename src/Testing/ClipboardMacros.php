<?php

namespace NativePHP\Clipboard\Testing;

use Native\Mobile\Testing\FakeBridge;
use PHPUnit\Framework\Assert;

/**
 * Clipboard test vocabulary for the NativePHP testing suite, registered as
 * FakeBridge macros so app tests read in clipboard terms instead of raw
 * bridge method strings:
 *
 *     Native::fakeBridge()->withClipboard('preset paste content');
 *
 *     Native::test(ShareSheet::class)
 *         ->tap('copyLink')
 *         ->assertCopied('https://example.com/invite/abc');
 *
 * Registered by ClipboardServiceProvider when the app is running unit
 * tests on a core whose FakeBridge supports macros.
 */
class ClipboardMacros
{
    public static function register(): void
    {
        /**
         * Fake the clipboard's contents. Reads return the current text;
         * writes succeed and update it — so a copy-then-paste flow behaves
         * like a real clipboard.
         */
        FakeBridge::macro('withClipboard', function (string $text = '') {
            $clipboard = (object) ['text' => $text];

            $this->respondTo('Clipboard.WriteText', function (array $params) use ($clipboard) {
                $clipboard->text = $params['text'] ?? '';

                return ['success' => true];
            });

            return $this->respondTo('Clipboard.ReadText', fn () => ['text' => $clipboard->text]);
        });

        /** Assert text was copied — any text, or exactly $text when given. */
        FakeBridge::macro('assertCopied', function (?string $text = null) {
            if ($text === null) {
                return $this->assertCalled('Clipboard.WriteText');
            }

            $copied = array_map(
                fn (array $call) => $call['params']['text'] ?? '',
                $this->callsTo('Clipboard.WriteText')
            );

            Assert::assertContains(
                $text,
                $copied,
                "Expected [{$text}] to be copied to the clipboard. Copied: "
                    .($copied === [] ? '(nothing)' : '['.implode('], [', $copied).']')
            );

            return $this;
        });

        FakeBridge::macro('assertNothingCopied', function () {
            return $this->assertNotCalled('Clipboard.WriteText');
        });
    }
}
