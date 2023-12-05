<?php

namespace Tests\Unit;

use Comhon\CustomAction\CustomActionRegistrar;
use Comhon\CustomAction\CustomActionServiceProvider;
use Comhon\CustomAction\Tests\TestCase;
use Mockery\MockInterface;

class CustomActionRegistrarTest extends TestCase
{
    public function registrar(): CustomActionRegistrar
    {
        return app(CustomActionRegistrar::class);
    }

    public function testGetTargetBindingsWithClosure()
    {
        $expected = ['name'];
        config(['custom-action.target_bindings' => fn () => ['name', 'validated' => 'boolean']]);
        $bindings = $this->registrar()->getTargetBindings();
        $this->assertEquals(['to.name' => 'string', 'to.validated' => 'boolean'], $bindings);
    }

    public function testGetTargetBindingsWithNotArray()
    {
        $this->expectExceptionMessage('invalid config target_bindings, must be an array or a closure that return an array');
        config(['custom-action.target_bindings' => 'value']);
        $this->registrar()->getTargetBindings();
    }

    public function testSubscribeException()
    {
        $mock = $this->partialMock(CustomActionRegistrar::class, function (MockInterface $mock) {
            $mock->shouldReceive('subscribeListeners')->andThrow(
                new \Illuminate\Database\QueryException('test', 'test', [], new \Exception('test'))
            )->once();
        });
        (new CustomActionServiceProvider(app()))->packageBooted();
    }
}
