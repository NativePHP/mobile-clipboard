<?php

namespace NativePHP\Clipboard;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Testing\FakeBridge;
use NativePHP\Clipboard\Testing\ClipboardMacros;

class ClipboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clipboard::class, fn () => new Clipboard);

        // Test sugar (assertCopied() etc.) — only under a test runner, and
        // only on a core whose FakeBridge is macroable (the method_exists
        // guard keeps older v4 and v3 cores fatal-free).
        if ($this->app->runningUnitTests()
            && class_exists(FakeBridge::class)
            && method_exists(FakeBridge::class, 'macro')) {
            ClipboardMacros::register();
        }
    }
}
