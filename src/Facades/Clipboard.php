<?php

namespace NativePHP\Clipboard\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool writeText(string $text)
 * @method static ?string readText()
 *
 * @see \NativePHP\Clipboard\Clipboard
 */
class Clipboard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NativePHP\Clipboard\Clipboard::class;
    }
}
