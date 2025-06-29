<?php

namespace Tests\Feature;

use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Models\User;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ScopedSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerBoolean')]
    public function test_list_scoped_settings_with_filter($fromEventAction)
    {
        $prefixAction = $fromEventAction ? 'event-actions' : 'manual-actions';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $uniqueProperty = $fromEventAction ? 'id' : 'type';

        $scopedSetting = $this->getActionScopedSetting($actionClass);
        $scopedSetting->name = 'the one';
        $scopedSetting->save();
        $action = $scopedSetting->action;

        // doesn't match with filter
        $scopedSetting->replicate()->forceFill(['name' => 'foo'])->save();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/{$prefixAction}/{$action->$uniqueProperty}/scoped-settings?$params")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $scopedSetting->id,
                        'name' => 'the one',
                    ],
                ],
            ])->assertJsonMissingPath('data.0.default_setting');
    }

    public function test_get_action_scoped_settings_success()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        /** @var User $consumer */
        $consumer = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($consumer)->getJson("custom/scoped-settings/{$scopedSetting->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $scopedSetting->id,
                    'settings' => [
                        'text' => 'text to user {{ user.id }} {{ user.translation.translate() }}',
                    ],
                ],
            ]);
    }

    public function test_get_action_scoped_settings_forbidden()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        /** @var User $consumer */
        $consumer = User::factory()->create();
        $this->actingAs($consumer)->getJson("custom/scoped-settings/{$scopedSetting->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerBoolean')]
    public function test_store_action_scoped_settings_success($fromEventAction)
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
            'name' => 'scoped name',
            'scope' => ['user.status' => 'foo'],
            'settings' => [
                'text' => 'stored text',
                ...($fromEventAction ? ['emails' => ['user.email', 'actionEmail']] : []),
            ],
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson("custom/$prefixAction/{$action->$uniqueProperty}/scoped-settings", $inputs)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id']])
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertEquals(1, $action->scopedSettings()->count());
        $this->assertArraySubset($inputs, $action->scopedSettings()->first()->toArray());
    }

    public function test_store_action_scoped_settings_forbidden_ability()
    {
        $action = ManualAction::factory()->action(ComplexManualAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/manual-actions/{$action->type}/scoped-settings")
            ->assertForbidden();
    }

    public function test_update_manual_action_scoped_settings()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        $inputs = [
            'name' => 'scoped name',
            'scope' => ['user.status' => 'foo'],
            'settings' => [
                'text' => 'updated text',
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/$scopedSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $scopedSetting->refresh()->toArray());
    }

    public function test_update_event_action_scoped_settings()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexEventAction::class);

        $inputs = [
            'name' => 'scoped name',
            'scope' => ['user.status' => 'foo'],
            'settings' => [
                'text' => 'updated text',
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/$scopedSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $scopedSetting->refresh()->toArray());
    }

    public function test_update_event_action_scoped_settings_with_context_values()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexEventAction::class);

        $inputs = [
            'name' => 'scoped name',
            'scope' => ['user.status' => 'foo'],
            'settings' => [
                'text' => 'updated text',
                'emails' => ['user.email', 'actionEmail'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/$scopedSetting->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => $inputs,
            ]);

        $this->assertArraySubset($inputs, $scopedSetting->refresh()->toArray());
    }

    public function test_update_action_scoped_settings_unprocessable()
    {

        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        $inputs = [
            'name' => 'scoped name',
            'scope' => ['user.status' => 'foo'],
            'settings' => [
                'text' => ['bar'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/$scopedSetting->id", $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The value settings.text must be a string',
                'errors' => [
                    'settings.text' => [
                        'The value settings.text must be a string',
                    ],
                ],
            ]);

    }

    public function test_update_action_scoped_settings_forbidden()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/$scopedSetting->id")
            ->assertForbidden();
    }

    public function test_delete_action_scoped_settings()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);
        $action = $scopedSetting->action;

        $this->assertEquals(1, $action->scopedSettings()->count());
        $this->assertEquals(1, ScopedSetting::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSetting->id")
            ->assertNoContent();

        $this->assertEquals(0, $action->scopedSettings()->count());
        $this->assertEquals(0, ScopedSetting::count());
    }

    public function test_delete_action_scoped_settings_forbidden()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSetting->id")
            ->assertForbidden();
    }
}
