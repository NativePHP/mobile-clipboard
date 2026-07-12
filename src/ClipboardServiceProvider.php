<?php

namespace NativePHP\Clipboard;

use Illuminate\Support\ServiceProvider;

class ClipboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clipboard::class, fn () => new Clipboard);
    }
}
