<?php

/**
 * The clipboard test vocabulary this plugin registers on the FakeBridge
 * (withClipboard / assertCopied / assertNothingCopied) — the sugar app
 * developers use instead of raw bridge method strings.
 *
 * Skipped on cores whose FakeBridge predates macro support.
 */

use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;
use NativePHP\Clipboard\Clipboard;
use PHPUnit\Framework\AssertionFailedError;

uses(Tests\TestCase::class);

beforeEach(function () {
    if (! method_exists(FakeBridge::class, 'macro')) {
        $this->markTestSkipped('This core\'s FakeBridge does not support macros.');
    }

    $this->bridge = Native::fakeBridge();
});

describe('withClipboard()', function () {
    it('preloads paste content', function () {
        $this->bridge->withClipboard('preset content');

        expect((new Clipboard)->readText())->toBe('preset content');
    });

    it('defaults to an empty clipboard', function () {
        $this->bridge->withClipboard();

        expect((new Clipboard)->readText())->toBeNull();
    });

    it('accepts writes and reflects them back on read', function () {
        $this->bridge->withClipboard();

        expect((new Clipboard)->writeText('copied later'))->toBeTrue()
            ->and((new Clipboard)->readText())->toBe('copied later');
    });
});

describe('assertCopied()', function () {
    it('passes when any text was copied', function () {
        $this->bridge->withClipboard();

        (new Clipboard)->writeText('anything');

        $this->bridge->assertCopied();
    });

    it('matches the exact copied text', function () {
        $this->bridge->withClipboard();

        (new Clipboard)->writeText('first');
        (new Clipboard)->writeText('second');

        $this->bridge->assertCopied('second');
    });

    it('fails when nothing was copied', function () {
        expect(fn () => $this->bridge->assertCopied())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails when different text was copied, naming what was', function () {
        $this->bridge->withClipboard();

        (new Clipboard)->writeText('actual');

        expect(fn () => $this->bridge->assertCopied('expected'))
            ->toThrow(AssertionFailedError::class, 'actual');
    });
});

describe('assertNothingCopied()', function () {
    it('passes when no write happened', function () {
        (new Clipboard)->readText();

        $this->bridge->assertNothingCopied();
    });

    it('fails after a write', function () {
        $this->bridge->withClipboard();

        (new Clipboard)->writeText('oops');

        expect(fn () => $this->bridge->assertNothingCopied())
            ->toThrow(AssertionFailedError::class);
    });
});
