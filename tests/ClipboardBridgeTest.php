<?php

/**
 * Contract tests for the Clipboard PHP class against this plugin's bridge
 * functions, driven through the NativePHP FakeBridge. They pin the PHP →
 * native contract: payload shape out, response decoding back.
 */

use Native\Mobile\Testing\Native;
use NativePHP\Clipboard\Clipboard;

uses(Tests\TestCase::class);

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
