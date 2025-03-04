<?php

namespace Tests\Unit;

use App\Actions\SendAutomaticCompanyRegistrationMail;
use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\SendAutomaticEmail;
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
                'company' => Company::class,
                'send-automatic-email' => SendAutomaticEmail::class,
                'send-automatic-company-email' => SendAutomaticCompanyRegistrationMail::class,
                'company-registered' => CompanyRegistered::class,
            ]
        );
        $resolver->bind('user', User::class);

        $this->assertInstanceOf(ModelResolverInterface::class, $resolver->getResolver());
        $this->assertEquals(Company::class, $resolver->getClass('company'));
        $this->assertEquals(User::class, $resolver->getClass('user'));
        $this->assertEquals('send-automatic-email', $resolver->getUniqueName(SendAutomaticEmail::class));
        $this->assertNull($resolver->getClass('foo'));
        $this->assertNull($resolver->getUniqueName('bar'));

        $this->assertTrue($resolver->isAllowedAction('send-automatic-company-email'));
        $this->assertTrue($resolver->isAllowedAction('send-automatic-email'));
        $this->assertFalse($resolver->isAllowedAction('company-registered'));

        $this->assertTrue($resolver->isAllowedEvent('company-registered'));
        $this->assertFalse($resolver->isAllowedEvent('send-automatic-email'));
    }
}
