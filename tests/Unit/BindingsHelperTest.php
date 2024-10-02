<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use Comhon\CustomAction\Bindings\BindingsHelper;
use Tests\TestCase;

class BindingsHelperTest extends TestCase
{
    public function testGetBindingsString()
    {
        $rules = BindingsHelper::getEventBindingRules(CompanyRegistered::class, ['my.key' => 'string']);

        $this->assertEquals([
            'my.key' => 'string|in:company.name,user.name,localized',
        ], $rules);
    }

    public function testGetBindingsEmailReceiver()
    {
        $rules = BindingsHelper::getEventBindingRules(CompanyRegistered::class, ['receivers' => 'mailable-entity']);

        $this->assertEquals([
            'receivers' => 'string|in:user',
        ], $rules);
    }

    public function testGetBindingsArray()
    {
        $rules = BindingsHelper::getEventBindingRules(CompanyRegistered::class, ['my.key' => 'array:string']);

        $this->assertEquals([
            'my.key' => 'array',
            'my.key.*' => 'string|in:company.name,user.name,localized',
        ], $rules);
    }

    public function testGetBindingsSeveralTypes()
    {
        $rules = BindingsHelper::getEventBindingRules(CompanyRegistered::class, [
            'receivers' => 'mailable-entity',
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
        BindingsHelper::getEventBindingRules('foo', ['my.key' => 'string']);

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
            BindingsHelper::getBindingValues($bindings, 'my.sub.value')
        );
    }

    public function testGetBindingsValuesSimpleNotExist()
    {
        $bindings = [
            'my' => [],
        ];
        $this->assertEquals(
            [null],
            BindingsHelper::getBindingValues($bindings, 'my.sub.value')
        );
    }

    public function testGetBindingsValuesSimpleNotAccessible()
    {
        $bindings = [
            'my' => 'foo',
        ];
        $this->assertEquals(
            [],
            BindingsHelper::getBindingValues($bindings, 'my.*.value')
        );
    }

    public function testGetBindingsValuesWildCardKey()
    {
        $bindings = [
            12, 13,
        ];
        $this->assertEquals(
            [12, 13],
            BindingsHelper::getBindingValues($bindings, '*')
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
            BindingsHelper::getBindingValues($bindings, 'my.*.subs.*.value')
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
            BindingsHelper::getBindingValues($bindings, 'my.*.values.*')
        );
    }
}
