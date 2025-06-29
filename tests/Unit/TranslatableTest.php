<?php

namespace Tests\Unit;

use Comhon\CustomAction\Context\Translatable;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslatableTest extends TestCase
{
    public function test_translatable()
    {
        Lang::addLines(['messages.foo' => 'Hello !'], 'en');

        $this->assertEquals('foo', (new Translatable('foo', null))->__toString());

        $this->assertEquals('Hello !', (new Translatable('foo', 'messages.'))->translate());

        $this->assertEquals('bar-en', (new Translatable('bar', fn ($value, $locale) => "$value-$locale"))->translate());
    }
}
