<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use Comhon\CustomAction\Context\ContextHelper;
use Tests\TestCase;

class ContextHelperTest extends TestCase
{
    public function test_get_context_string()
    {
        $rules = ContextHelper::getEventContextRules(CompanyRegistered::class, ['my.key' => 'string']);

        $this->assertEquals([
            'my.key' => 'string|in:company.name,company.status,company.languages.*.locale,user.name,localized',
        ], $rules);
    }

    public function test_get_context_email_receiver()
    {
        $rules = ContextHelper::getEventContextRules(CompanyRegistered::class, ['receivers' => 'mailable-entity']);

        $this->assertEquals([
            'receivers' => 'string|in:user',
        ], $rules);
    }

    public function test_get_context_array()
    {
        $rules = ContextHelper::getEventContextRules(CompanyRegistered::class, ['my.key' => 'array:string']);

        $this->assertEquals([
            'my.key' => 'array',
            'my.key.*' => 'string|in:company.name,company.status,company.languages.*.locale,user.name,localized',
        ], $rules);
    }

    public function test_get_context_several_types()
    {
        $rules = ContextHelper::getEventContextRules(CompanyRegistered::class, [
            'receivers' => 'mailable-entity',
            'my.key' => 'string',
        ]);

        $this->assertEquals([
            'my.key' => 'string|in:company.name,company.status,company.languages.*.locale,user.name,localized',
            'receivers' => 'string|in:user',
        ], $rules);
    }

    public function test_get_context_invalid_event_class()
    {
        $this->expectExceptionMessage('first argument must be a subclass of HasContextInterface');
        ContextHelper::getEventContextRules('foo', ['my.key' => 'string']);

    }

    public function test_get_context_values_simple()
    {
        $context = [
            'my' => [
                'sub' => [
                    'value' => 12,
                ],
            ],
        ];
        $this->assertEquals(
            [12],
            ContextHelper::getValues($context, 'my.sub.value')
        );
    }

    public function test_get_context_values_simple_not_exist()
    {
        $context = [
            'my' => [],
        ];
        $this->assertEquals(
            [null],
            ContextHelper::getValues($context, 'my.sub.value')
        );
    }

    public function test_get_context_values_simple_not_accessible()
    {
        $context = [
            'my' => 'foo',
        ];
        $this->assertEquals(
            [],
            ContextHelper::getValues($context, 'my.*.value')
        );
    }

    public function test_get_context_values_wild_card_key()
    {
        $context = [
            12, 13,
        ];
        $this->assertEquals(
            [12, 13],
            ContextHelper::getValues($context, '*')
        );
    }

    public function test_get_context_values_nested_wild_card_key()
    {
        $context = [
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
            ContextHelper::getValues($context, 'my.*.subs.*.value')
        );
    }

    public function test_get_context_values_wild_card_key_ending()
    {
        $context = [
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
            ContextHelper::getValues($context, 'my.*.values.*')
        );
    }
}
