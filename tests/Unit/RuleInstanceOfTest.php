<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RuleInstanceOfTest extends TestCase
{
    public function testValidSameClassInstance()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => new User];
        $validated = Validator::validate($data, ['foo' => 'is:user']);
        $this->assertEquals($data, $validated);
    }

    public function testValidSubClassInstance()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => new User];
        $validated = Validator::validate($data, ['foo' => 'is:mailable-entity']);
        $this->assertEquals($data, $validated);
    }

    public function testValidSameClassString()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => 'user'];
        $validated = Validator::validate($data, ['foo' => 'is:user,true,true']);
        $this->assertEquals($data, $validated);
    }

    public function testValidSubClassString()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => 'user'];
        $validated = Validator::validate($data, ['foo' => 'is:mailable-entity,true,true']);
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
            ['foo' => ['The foo is not instance of mailable-entity.']],
            Validator::make($data, ['foo' => 'is:mailable-entity'])->errors()->toArray()
        );
    }

    public function testInvalidSameClassStringOnlySubClass()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = ['foo' => 'user'];

        $this->assertEquals(
            ['foo' => ['The foo is not subclass of user.']],
            Validator::make($data, ['foo' => 'is:user,false,true'])->errors()->toArray()
        );
    }
}
