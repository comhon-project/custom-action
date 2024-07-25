<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RuleModelReferenceTest extends TestCase
{
    public function testValidSimple()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => [
                'model_type' => 'user',
                'model_id' => User::factory()->create()->id,
            ],
        ];
        $validated = Validator::validate($data, ['foo' => 'model_reference:user']);
        $this->assertEquals($data, $validated);
    }

    public function testValidSubclass()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => [
                'model_type' => 'user',
                'model_id' => User::factory()->create()->id,
            ],
        ];
        $validated = Validator::validate($data, ['foo' => 'model_reference:email-receiver']);
        $this->assertEquals($data, $validated);
    }

    public function testValidPrefix()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => [
                'receiver_type' => 'user',
                'receiver_id' => User::factory()->create()->id,
            ],
        ];
        $validated = Validator::validate($data, ['foo' => 'model_reference:email-receiver,receiver']);
        $this->assertEquals($data, $validated);
    }

    public function testInvalidNoparams()
    {
        $data = ['foo' => []];
        $this->expectExceptionMessage('must have one parameter');
        Validator::validate($data, ['foo' => 'model_reference']);
    }

    public function testInvalidParamModel()
    {
        $data = ['foo' => []];
        $this->expectExceptionMessage('invalid model bar');
        Validator::validate($data, ['foo' => 'model_reference:bar']);
    }

    public function testInvalidNotEloquentModel()
    {
        $data = [
            'foo' => [
                'model_type' => 'foo',
                'model_id' => 1,
            ],
        ];
        $this->assertEquals(
            ['foo' => ['The model_type is not instance of eloquent model.']],
            Validator::make($data, ['foo' => 'model_reference:email-receiver'])->errors()->toArray()
        );
    }

    public function testInvalidNotRuleModel()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
            'company' => Company::class,
        ]);
        $data = [
            'foo' => [
                'model_type' => 'company',
                'model_id' => Company::factory()->create()->id,
            ],
        ];
        $this->assertEquals(
            ['foo' => ['The model_type is not instance of user.']],
            Validator::make($data, ['foo' => 'model_reference:user'])->errors()->toArray()
        );
    }

    public function testInvalidDoesntExist()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => [
                'model_type' => 'user',
                'model_id' => 12,
            ],
        ];
        $this->assertEquals(
            ['foo' => ['model doesn\'t exist.']],
            Validator::make($data, ['foo' => 'model_reference:user'])->errors()->toArray()
        );
    }

    public function testInvalidNotArray()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => 'bar',
        ];
        $this->assertEquals(
            ['foo' => ['foo must be an array.']],
            Validator::make($data, ['foo' => 'model_reference:user'])->errors()->toArray()
        );
    }
}
