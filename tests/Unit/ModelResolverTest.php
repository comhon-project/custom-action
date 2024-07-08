<?php

namespace Tests\Unit;

use App\Actions\SendCompanyRegistrationMail;
use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Comhon\ModelResolverContract\ModelResolverInterface;
use Tests\TestCase;

class ModelResolverTest extends TestCase
{
    public function testModelResolver()
    {
        /** @var CustomActionModelResolver $resolver */
        $resolver = app(CustomActionModelResolver::class);
        $resolver->register(
            [
                'company' => Company::class,
                'send-email' => SendTemplatedMail::class,
                'send-company-email' => SendCompanyRegistrationMail::class,
                'company-registered' => CompanyRegistered::class,
            ]
        );
        $resolver->bind('user', User::class);

        $this->assertInstanceOf(ModelResolverInterface::class, $resolver->getResolver());
        $this->assertEquals(Company::class, $resolver->getClass('company'));
        $this->assertEquals(User::class, $resolver->getClass('user'));
        $this->assertEquals('send-email', $resolver->getUniqueName(SendTemplatedMail::class));
        $this->assertNull($resolver->getClass('foo'));
        $this->assertNull($resolver->getUniqueName('bar'));

        $this->assertTrue($resolver->isAllowedAction('send-company-email'));
        $this->assertTrue($resolver->isAllowedAction('send-email'));
        $this->assertFalse($resolver->isAllowedAction('company-registered'));

        $this->assertTrue($resolver->isAllowedEvent('company-registered'));
        $this->assertFalse($resolver->isAllowedEvent('send-email'));
    }
}
