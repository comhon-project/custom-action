<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Context\Translatable;
use Tests\TestCase;

class ContextTest extends TestCase
{
    public function test_get_validated_context_without_cache()
    {
        $action = new TestActionCache;
        $res = $action->getExposedValidatedContext(false, false);
        $this->assertEquals(1, $res['index']);

        $res = $action->getExposedValidatedContext(false, false);
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_context_with_cache()
    {
        $action = new TestActionCache;
        $res = $action->getExposedValidatedContext(false, true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getExposedValidatedContext(false, true);
        $this->assertEquals(1, $res['index']);
    }

    public function test_find_class_value()
    {
        $this->assertEquals(
            ['value'],
            ContextHelper::find('stored-file', [
                'value' => 'required|is:stored-file',
            ])
        );
    }

    public function test_set_translations_values()
    {
        $values = [
            'translatable' => 'first',
            'level_1' => [
                [
                    'level_2' => [
                        [
                            'translatable' => 'foo',
                        ],
                        [
                            'translatable' => 'bar',
                        ],
                    ],
                ],
                [
                    'level_2' => [
                        [
                            'translatable' => 'baz',
                        ],
                        [
                            'translatable' => 'dummy',
                        ],
                    ],
                ],
            ],
        ];
        ContextHelper::setTranslationValues($values, [
            'translatable' => '',
            'level_1.*.level_2.*.translatable' => '',
        ]);

        $this->assertInstanceOf(Translatable::class, $values['translatable']);
        $this->assertInstanceOf(Translatable::class, $values['level_1'][0]['level_2'][0]['translatable']);
        $this->assertInstanceOf(Translatable::class, $values['level_1'][0]['level_2'][1]['translatable']);
        $this->assertInstanceOf(Translatable::class, $values['level_1'][1]['level_2'][0]['translatable']);
        $this->assertInstanceOf(Translatable::class, $values['level_1'][1]['level_2'][1]['translatable']);
    }
}
