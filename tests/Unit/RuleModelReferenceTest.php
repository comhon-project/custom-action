<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RuleModelReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_simple()
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

    public function test_valid_subclass()
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
        $validated = Validator::validate($data, ['foo' => 'model_reference:mailable-entity']);
        $this->assertEquals($data, $validated);
    }

    public function test_valid_prefix()
    {
        CustomActionModelResolver::register([
            'user' => User::class,
        ]);
        $data = [
            'foo' => [
                'recipient_type' => 'user',
                'recipient_id' => User::factory()->create()->id,
            ],
        ];
        $validated = Validator::validate($data, ['foo' => 'model_reference:mailable-entity,recipient']);
        $this->assertEquals($data, $validated);
    }

    public function test_invalid_noparams()
    {
        $data = ['foo' => []];
        $this->expectExceptionMessage('must have one parameter');
        Validator::validate($data, ['foo' => 'model_reference']);
    }

    public function test_invalid_param_model()
    {
        $data = ['foo' => []];
        $this->expectExceptionMessage('invalid model bar');
        Validator::validate($data, ['foo' => 'model_reference:bar']);
    }

    public function test_invalid_not_eloquent_model()
    {
        $data = [
            'foo' => [
                'model_type' => 'foo',
                'model_id' => 1,
            ],
        ];
        $this->assertEquals(
            ['foo' => ['The model_type is not instance of eloquent model.']],
            Validator::make($data, ['foo' => 'model_reference:mailable-entity'])->errors()->toArray()
        );
    }

    public function test_invalid_not_rule_model()
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

    public function test_invalid_doesnt_exist()
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

    public function test_invalid_not_array()
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
