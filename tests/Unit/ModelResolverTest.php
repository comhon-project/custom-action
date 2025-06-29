<?php

namespace Tests\Unit;

use App\Actions\ComplexEventAction;
use App\Actions\SimpleEventAction;
use App\Events\MySimpleEvent;
use App\Models\User;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Comhon\ModelResolverContract\ModelResolverInterface;
use Tests\TestCase;

class ModelResolverTest extends TestCase
{
    public function test_model_resolver()
    {
        /** @var CustomActionModelResolver $resolver */
        $resolver = app(CustomActionModelResolver::class);
        $resolver->register(
            [
                'user' => User::class,
                'simple-event-action' => SimpleEventAction::class,
                'complex-event-action' => ComplexEventAction::class,
                'my-simple-event' => MySimpleEvent::class,
            ]
        );
        $resolver->bind('user', User::class);

        $this->assertInstanceOf(ModelResolverInterface::class, $resolver->getResolver());
        $this->assertEquals(User::class, $resolver->getClass('user'));
        $this->assertEquals(User::class, $resolver->getClass('user'));
        $this->assertEquals('simple-event-action', $resolver->getUniqueName(SimpleEventAction::class));
        $this->assertNull($resolver->getClass('foo'));
        $this->assertNull($resolver->getUniqueName('bar'));

        $this->assertTrue($resolver->isAllowedAction('complex-event-action'));
        $this->assertTrue($resolver->isAllowedAction('simple-event-action'));
        $this->assertFalse($resolver->isAllowedAction('my-simple-event'));

        $this->assertTrue($resolver->isAllowedEvent('my-simple-event'));
        $this->assertFalse($resolver->isAllowedEvent('simple-event-action'));
    }
}
