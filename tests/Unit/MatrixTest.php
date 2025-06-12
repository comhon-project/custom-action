<?php

namespace Tests\Unit;

use Comhon\CustomAction\Services\ActionService;
use Tests\TestCase;

class MatrixTest extends TestCase
{
    public function test_get_flattened_states()
    {
        $actual = app(ActionService::class)->getFlattenedStates([
            'a',
            ['b' => 1],
            [
                'a',
                ['b', ['c' => 1]],
            ],
            [
                ['a', 'b', 'c'],
                ['d' => 1],
            ],
            [
                ['a', 'b'],
                [['c' => 1], ['c' => 100]],
                ['d', 'e'],
            ],
        ]);
        $expected = [
            ['a'],
            [['b' => 1]],
            ['a', 'b'],
            ['a', ['c' => 1]],
            ['a', ['d' => 1]],
            ['b', ['d' => 1]],
            ['c', ['d' => 1]],
            ['a', ['c' => 1], 'd'],
            ['a', ['c' => 1], 'e'],
            ['a', ['c' => 100], 'd'],
            ['a', ['c' => 100], 'e'],
            ['b', ['c' => 1], 'd'],
            ['b', ['c' => 1], 'e'],
            ['b', ['c' => 100], 'd'],
            ['b', ['c' => 100], 'e'],
        ];

        $this->assertEquals($expected, $actual);
    }
}
