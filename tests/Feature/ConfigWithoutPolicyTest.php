<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ConfigWithoutPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_events_config_without_ability()
    {
        config(['custom-action.use_policies' => false]);
        /** @var User $user */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertOk();
    }
}
