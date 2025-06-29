<?php

namespace Tests\Feature;

use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Actions\SimpleEventAction;
use App\Actions\SimpleManualAction;
use App\Models\User;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class DefaultSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_action_settings_success()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);

        /** @var User $consumer */
        $consumer = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($consumer)->getJson("custom/default-settings/{$defaultSetting->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $defaultSetting->id,
                    'settings' => [
                        'text' => 'simple text',
                    ],
                ],
            ]);
    }

    public function test_get_action_settings_forbidden()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);

        /** @var User $consumer */
        $consumer = User::factory()->create();
        $this->actingAs($consumer)->getJson("custom/default-settings/{$defaultSetting->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerBoolean')]
    public function test_store_action_settings_success($fromEventAction)
    {
        $prefixAction = $fromEventAction ? 'event-actions' : 'manual-actions';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $uniqueProperty = $fromEventAction ? 'id' : 'type';

        $factory = is_subclass_of($actionClass, CallableFromEventInterface::class)
            ? EventAction::factory()
            : ManualAction::factory();

        /** @var Action $action */
        $action = $factory->action($actionClass)->create();

        $inputs = [
            'settings' => [
                'text' => 'stored text',
                ...($fromEventAction ? ['emails' => ['user.email', 'actionEmail']] : []),
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson("custom/$prefixAction/{$action->$uniqueProperty}/default-settings", $inputs)
            ->assertCreated()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertEquals(1, $action->defaultSetting()->count());
        $defaultSetting = $action->defaultSetting()->first();
        $this->assertArraySubset($inputs, $defaultSetting->toArray());
    }

    public function test_store_action_settings_forbbiden_already_exists()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson("custom/manual-actions/{$defaultSetting->action->type}/default-settings", [
            'settings' => ['text' => 'stored text'],
        ])->assertForbidden()
            ->assertJson([
                'message' => 'default settings already exist',
            ]);
    }

    public function test_store_action_settings_empty()
    {
        $action = ManualAction::factory()->action(SimpleManualAction::class)->create();
        $inputs = ['settings' => []];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson("custom/manual-actions/{$action->type}/default-settings", $inputs)
            ->assertCreated()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertEquals(1, $action->defaultSetting()->count());
        $defaultSetting = $action->defaultSetting()->first();
        $this->assertArraySubset($inputs, $defaultSetting->toArray());
    }

    public function test_store_action_settings_forbidden_ability()
    {
        $action = ManualAction::factory()->action(SimpleManualAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/manual-actions/{$action->type}/default-settings")
            ->assertForbidden();
    }

    public function test_update_manual_action_settings()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);
        $inputs = [
            'settings' => ['text' => 'text updated'],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $defaultSetting->refresh()->toArray());
    }

    public function test_update_event_action_settings()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleEventAction::class);
        $inputs = [
            'settings' => ['text' => 'text updated'],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $defaultSetting->refresh()->toArray());
    }

    public function test_update_event_action_settings_with_context_values()
    {
        $defaultSetting = $this->getActionDefaultSetting(ComplexEventAction::class);
        $inputs = [
            'settings' => [
                'text' => 'text updated',
                'emails' => ['user.email', 'actionEmail'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $defaultSetting->refresh()->toArray());
    }

    public function test_update_action_settings_unprocessable()
    {

        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);
        $newSettings = ['text' => ['bar']];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", [
            'settings' => $newSettings,
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'The settings.text field must be a string.',
                'errors' => [
                    'settings.text' => [
                        'The settings.text field must be a string.',
                    ],
                ],
            ]);

    }

    public function test_update_action_settings_forbidden()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id")
            ->assertForbidden();
    }
}
