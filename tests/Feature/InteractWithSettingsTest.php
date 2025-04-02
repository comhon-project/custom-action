<?php

namespace Tests\Feature;

use App\Actions\MyManualActionWithoutContext;
use App\Actions\SendManualCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Exceptions\LocalizedSettingNotFoundException;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class InteractWithSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    #[DataProvider('provider_get_localized_setting')]
    public function test_get_localized_setting($paramLocale, $paramFallback, $appLocale, $appFallback, $expected)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($appFallback);

        DefaultSetting::factory()
            ->for(ManualAction::factory(), 'action')
            ->has(LocalizedSetting::factory(['locale' => 'en']))
            ->has(LocalizedSetting::factory(['locale' => 'fr']))
            ->create();

        $action = new SendManualCompanyRegistrationMail(
            Company::factory()->create(),
            new SystemFile($this->getAssetPath()),
            User::factory()->create(),
        );

        $localizedSetting = $action->getLocalizedSetting($paramLocale, $paramFallback);
        if ($expected) {
            $this->assertNotNull($localizedSetting);
            $this->assertEquals($expected, $localizedSetting->locale);
        } else {
            $this->assertNull($localizedSetting);
        }

        if (! $expected) {
            $this->expectException(LocalizedSettingNotFoundException::class);
        }
        $localizedSetting = $action->getLocalizedSettingOrFail($paramLocale, $paramFallback);
        $this->assertNotNull($localizedSetting);
        $this->assertEquals($expected, $localizedSetting->locale);
    }

    public static function provider_get_localized_setting()
    {
        return [
            [null, null, 'it', 'ch', null],
            ['en', null, 'it', 'ch', 'en'],
            ['it', null, 'es', 'ch', null],
            ['it', null, 'en', 'ch', 'en'],
            ['it', null, 'ch', 'fr', 'fr'],
            ['en', 'fr', 'ch', 'es', 'en'],
            ['it', 'fr', 'ch', 'es', 'fr'],
            ['it', 'es', 'en', 'fr', null], // if param fallback given, app locales must not be used
        ];
    }

    #[DataProvider('provider_get_locale_string')]
    public function test_get_locale_string($param, $expected)
    {
        ManualAction::factory()->create();

        $action = new SendManualCompanyRegistrationMail(
            Company::factory()->create(),
            new SystemFile($this->getAssetPath()),
            User::factory()->create(),
        );

        $this->assertEquals($expected, $action->getLocaleString($param));
    }

    public static function provider_get_locale_string()
    {
        return [
            [null, null],
            ['it', 'it'],
            [['foo' => 'it'], null],
            [['locale' => 'it'], 'it'],
            [['preferred_locale' => 'it'], 'it'],
            [(new User)->forceFill(['preferred_locale' => 'it']), 'it'],
        ];
    }

    public function test_get_setting_no_context()
    {
        DefaultSetting::factory()
            ->for(ManualAction::factory(['type' => 'my-manual-action-without-context']), 'action')
            ->create();

        $action = new MyManualActionWithoutContext;
        $setting = $action->getSetting();
        $this->assertInstanceOf(DefaultSetting::class, $setting);
        $this->assertSame($setting, $action->getSetting());
    }

    public function test_missing_settings()
    {
        $action = ManualAction::factory(['type' => 'my-manual-action-without-context'])->create();

        $this->expectExceptionMessage("missing default setting on action Comhon\CustomAction\Models\ManualAction with id '{$action->id}'");
        $action = new MyManualActionWithoutContext;
        $action->getSetting();
    }
}
