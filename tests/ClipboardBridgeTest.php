<?php

/**
 * Contract tests for the Clipboard PHP class against this plugin's bridge
 * functions, driven through the NativePHP FakeBridge. They pin the PHP →
 * native contract: payload shape out, response decoding back.
 */

use Native\Mobile\Testing\Native;
use NativePHP\Clipboard\Clipboard;
use NativePHP\Clipboard\Facades\Clipboard as ClipboardFacade;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->bridge = Native::fakeBridge();
});

describe('writeText()', function () {
    it('writes via Clipboard.WriteText with the text payload', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        $result = (new Clipboard)->writeText('composer require nativephp/mobile');

        expect($result)->toBeTrue();

        $this->bridge->assertCalled('Clipboard.WriteText', function (array $p) {
            expect($p['text'])->toBe('composer require nativephp/mobile');

            return true;
        });
    });

    it('preserves multiline text with quotes verbatim', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        $snippet = "<native:text class=\"font-bold\">\n    Hello 'world'\n</native:text>";
        (new Clipboard)->writeText($snippet);

        $this->bridge->assertCalled('Clipboard.WriteText', function (array $p) use ($snippet) {
            expect($p['text'])->toBe($snippet);

            return true;
        });
    });

    it('sends only the text for a plain copy', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        (new Clipboard)->writeText('hello');

        $this->bridge->assertCalled('Clipboard.WriteText', fn (array $p) => $p === ['text' => 'hello']);
    });

    it('marks a sensitive copy', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        expect((new Clipboard)->writeText('123456', sensitive: true))->toBeTrue();

        $this->bridge->assertCalled('Clipboard.WriteText', fn (array $p) => $p === [
            'text' => '123456',
            'sensitive' => true,
        ]);
    });

    it('sends an expiry in seconds', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        (new Clipboard)->writeText('123456', expiresIn: 60);

        $this->bridge->assertCalled('Clipboard.WriteText', fn (array $p) => $p === [
            'text' => '123456',
            'expires_in' => 60,
        ]);
    });

    it('sends both options together', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        (new Clipboard)->writeText('hunter2', sensitive: true, expiresIn: 30);

        $this->bridge->assertCalled('Clipboard.WriteText', fn (array $p) => $p === [
            'text' => 'hunter2',
            'sensitive' => true,
            'expires_in' => 30,
        ]);
    });

    it('rejects an expiry under one second without calling the bridge', function (int $seconds) {
        expect(fn () => (new Clipboard)->writeText('x', expiresIn: $seconds))
            ->toThrow(InvalidArgumentException::class);

        $this->bridge->assertNotCalled('Clipboard.WriteText');
    })->with([0, -5]);

    it('works through the facade with named options', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['success' => true]);

        expect(ClipboardFacade::writeText('code', sensitive: true, expiresIn: 45))->toBeTrue();

        $this->bridge->assertCalled('Clipboard.WriteText', fn (array $p) => ($p['expires_in'] ?? null) === 45);
    });

    it('returns false when the bridge reports an error', function () {
        $this->bridge->respondTo('Clipboard.WriteText', ['error' => 'clipboard write failed']);

        expect((new Clipboard)->writeText('x'))->toBeFalse();
    });
});

describe('readText()', function () {
    it('reads via Clipboard.ReadText and returns the text', function () {
        $this->bridge->respondTo('Clipboard.ReadText', ['text' => 'pasted content']);

        expect((new Clipboard)->readText())->toBe('pasted content');

        $this->bridge->assertCalled('Clipboard.ReadText');
    });

    it('returns null when the clipboard has no text', function () {
        $this->bridge->respondTo('Clipboard.ReadText', ['text' => '']);

        expect((new Clipboard)->readText())->toBeNull();
    });
});
