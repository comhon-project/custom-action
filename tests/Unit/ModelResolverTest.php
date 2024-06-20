<?php

namespace Tests\Unit;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\ModelResolverContract\ModelResolverInterface;
use Tests\TestCase;

class ModelResolverTest extends TestCase
{
    public function testModelResolver()
    {
        /** @var ModelResolverContainer $resolver */
        $resolver = app(ModelResolverContainer::class);
        $resolver->register(
            [
                'user' => User::class,
                'company' => Company::class,
                'send-email' => SendTemplatedMail::class,
                'send-company-email' => SendCompanyRegistrationMail::class,
                'my-unique-action' => '\MyApp\MyUniqueAction',
            ],
            [
                'custom-unique-action' => [
                    'send-company-email', 'my-unique-action',
                ],
                'custom-generic-action' => [
                    'send-email',
                ],
            ]
        );
        $this->assertInstanceOf(ModelResolverInterface::class, $resolver->getResolver());
        $this->assertEquals(Company::class, $resolver->getClass('company'));
        $this->assertEquals('send-email', $resolver->getUniqueName(SendTemplatedMail::class));
        $this->assertNull($resolver->getClass('foo'));
        $this->assertNull($resolver->getUniqueName('bar'));
        $this->assertTrue($resolver->isAllowed('my-unique-action', 'custom-unique-action'));
        $this->assertTrue($resolver->isAllowed('send-company-email', 'custom-unique-action'));
        $this->assertFalse($resolver->isAllowed('send-email', 'custom-unique-action'));
        $this->assertFalse($resolver->isAllowed('user', 'custom-unique-action'));
        $this->assertEquals(
            ['send-company-email', 'my-unique-action'],
            $resolver->getUniqueNames('custom-unique-action')
        );
        $this->assertEquals([
            SendCompanyRegistrationMail::class,
            '\MyApp\MyUniqueAction',
        ], $resolver->getClasses('custom-unique-action'));
        $this->assertEquals(
            [],
            $resolver->getUniqueNames('foo')
        );
        $this->assertEquals(
            [],
            $resolver->getClasses('bar')
        );
    }
}
