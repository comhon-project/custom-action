<?php

namespace Tests\Feature;

use App\Actions\ComplexManualAction;
use App\Models\Output;
use App\Models\User;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ManualActionHandleTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_action_without_settings()
    {
        $this->expectExceptionMessage('manual action complex-manual-action not found');
        ComplexManualAction::dispatch(User::factory()->create());
    }

    public function test_action_with_scoped_settings_success()
    {
        $scopedSetting = $this->getActionScopedSetting(ComplexManualAction::class, ['user.status' => 'foo']);
        $scopedSetting->replicate()->forceFill(['name' => 'other', 'scope' => ['user.status' => 'bar']])->save();

        $user = User::factory(['status' => 'foo'])->create();
        ComplexManualAction::dispatch($user);

        $output = Output::firstOrFail();
        $this->assertEquals($scopedSetting->id, $output->setting_id);
        $this->assertEquals(get_class($scopedSetting), $output->setting_class);
    }

    public function test_action_with_scoped_settings_conflicts()
    {
        $user = User::factory(['status' => 'foo'])->create();
        $scopedSetting = $this->getActionScopedSetting(
            ComplexManualAction::class,
            ['user.status' => 'foo']
        );

        $scopedSetting->replicate()->forceFill(['name' => 'replicate'])->save();

        $this->expectExceptionMessage('cannot resolve conflict between several scoped settings');
        ComplexManualAction::dispatch($user);
    }

    public function test_action_with_invalid_context()
    {
        $user = User::factory(['email' => 'foo'])->create();
        ManualAction::factory()->action(ComplexManualAction::class)->withDefaultSettings()->create();

        $this->expectExceptionMessage('The user.email field must be a valid email address.');
        ComplexManualAction::dispatch($user);
    }

    #[DataProvider('providerBoolean')]
    public function test_action_with_translatable_context($isEn)
    {
        $locale = $isEn ? 'en' : 'fr';
        $user = User::factory(['preferred_locale' => $locale])->create();
        $localizedSetting = ManualAction::factory()
            ->action(ComplexManualAction::class)
            ->withDefaultSettings()
            ->create()
            ->defaultSetting
            ->localizedSettings
            ->first();

        $localizedSetting->replicate()->forceFill(['locale' => 'fr'])->save();

        ComplexManualAction::dispatch($user);
        $output = Output::firstOrFail();
        $translated = $isEn ? 'english status : foo' : 'statut francais : foo';
        $this->assertEquals($translated, $output->output['user_translation']);
    }

    public function test_handle_manual_action_invalid_context()
    {
        $user = User::factory(['email' => 'foo'])->create();
        ManualAction::factory()
            ->action(ComplexManualAction::class)
            ->withDefaultSettings()
            ->create();

        $this->expectExceptionMessage('The user.email field must be a valid email address');
        ComplexManualAction::dispatch($user);
    }
}
