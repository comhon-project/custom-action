<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use Comhon\CustomAction\Support\Bindings;
use Tests\TestCase;

class BindingsTest extends TestCase
{
    public function testGetBindingsString()
    {
        $rules = Bindings::getEventBindingRules(CompanyRegistered::class, ['my.key' => 'string']);

        $this->assertEquals([
            'my.key' => 'string|in:company.name,user.name,localized',
        ], $rules);
    }

    public function testGetBindingsEmailReceiver()
    {
        $rules = Bindings::getEventBindingRules(CompanyRegistered::class, ['receivers' => 'email-receiver']);

        $this->assertEquals([
            'receivers' => 'string|in:user',
        ], $rules);
    }

    public function testGetBindingsSeveralTypes()
    {
        $rules = Bindings::getEventBindingRules(CompanyRegistered::class, [
            'receivers' => 'email-receiver',
            'my.key' => 'string',
        ]);

        $this->assertEquals([
            'my.key' => 'string|in:company.name,user.name,localized',
            'receivers' => 'string|in:user',
        ], $rules);
    }

    public function testGetBindingsInvalidEventClass()
    {
        $this->expectExceptionMessage('first argument must be a subclass of HasBindingsInterface');
        Bindings::getEventBindingRules('foo', ['my.key' => 'string']);

    }

    public function testGetBindingsValuesSimple()
    {
        $bindings = [
            'my' => [
                'sub' => [
                    'value' => 12,
                ],
            ],
        ];
        $this->assertEquals(
            [12],
            Bindings::getBindingValues($bindings, 'my.sub.value')
        );
    }

    public function testGetBindingsValuesSimpleNotExist()
    {
        $bindings = [
            'my' => [],
        ];
        $this->assertEquals(
            [null],
            Bindings::getBindingValues($bindings, 'my.sub.value')
        );
    }

    public function testGetBindingsValuesSimpleNotAccessible()
    {
        $bindings = [
            'my' => 'foo',
        ];
        $this->assertEquals(
            [],
            Bindings::getBindingValues($bindings, 'my.*.value')
        );
    }

    public function testGetBindingsValuesWildCardKey()
    {
        $bindings = [
            12, 13,
        ];
        $this->assertEquals(
            [12, 13],
            Bindings::getBindingValues($bindings, '*')
        );
    }

    public function testGetBindingsValuesNestedWildCardKey()
    {
        $bindings = [
            'my' => [
                [
                    'subs' => [
                        ['value' => 14],
                        ['value' => 15],
                    ],
                ],
                [
                    'subs' => [
                        ['value' => 16],
                    ],
                ],
            ],
        ];
        $this->assertEquals(
            [14, 15, 16],
            Bindings::getBindingValues($bindings, 'my.*.subs.*.value')
        );
    }

    public function testGetBindingsValuesWildCardKeyEnding()
    {
        $bindings = [
            'my' => [
                [
                    'values' => [20, 21],
                ],
                [
                    'values' => [22],
                ],
            ],
        ];
        $this->assertEquals(
            [20, 21, 22],
            Bindings::getBindingValues($bindings, 'my.*.values.*')
        );
    }
}
