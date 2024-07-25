<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RuleInstanceOfTest extends TestCase
{
    public function testValidSameClass()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => 'user'];
        $validated = Validator::validate($data, ['foo' => 'is:user']);
        $this->assertEquals($data, $validated);
    }

    public function testValidSubClass()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => 'user'];
        $validated = Validator::validate($data, ['foo' => 'is:email-receiver']);
        $this->assertEquals($data, $validated);
    }

    public function testInvalidNoparams()
    {
        $data = ['foo' => 'bar'];
        $this->expectExceptionMessage('must have one parameter');
        Validator::validate($data, ['foo' => 'is']);
    }

    public function testInvalidNotInstanceOf()
    {
        $data = [
            'foo' => 'bar',
        ];
        $this->assertEquals(
            ['foo' => ['The foo is not instance of email-receiver.']],
            Validator::make($data, ['foo' => 'is:email-receiver'])->errors()->toArray()
        );
    }

    public function testInvalidSameOnlySubclass()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => 'email-receiver',
        ];
        $this->assertEquals(
            ['foo' => ['The foo is not subclass of email-receiver.']],
            Validator::make($data, ['foo' => 'is:email-receiver,false'])->errors()->toArray()
        );
    }
}
