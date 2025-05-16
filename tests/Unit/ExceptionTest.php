<?php

namespace Tests\Unit;

use Comhon\CustomAction\Exceptions\LocalizedSettingNotFoundException;
use Comhon\CustomAction\Exceptions\MissingSettingException;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_missing_setting()
    {
        $exception = new MissingSettingException(ManualAction::factory()->make(), true);
        $this->assertInstanceOf(JsonResponse::class, $exception->render(request()));
    }

    public function test__localized_setting_not_found()
    {
        $exception = new LocalizedSettingNotFoundException(DefaultSetting::factory()->make(), null, null);
        $this->assertInstanceOf(JsonResponse::class, $exception->render(request()));
    }
}
