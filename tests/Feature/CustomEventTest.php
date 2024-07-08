<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

class CustomEventTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistration;

    public function testGetEventsSuccess()
    {
        config(['custom-action.events' => [CompanyRegistered::class]]);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertJson([
            'data' => [
                [
                    'type' => 'company-registered',
                    'name' => 'company registered',
                ],
            ],
        ]);
    }

    public function testGetEventsForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events')
            ->assertForbidden();
    }

    public function testEventShemaSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [
                        'company.name' => 'string',
                        'logo' => 'is:stored-file',
                        'user' => 'is:email-receiver',
                        'user.name' => 'string',
                        'user.email' => 'email',
                    ],
                    'allowed_actions' => [
                        [
                            'type' => 'send-email',
                            'name' => 'send email',
                        ],
                        [
                            'type' => 'send-company-email',
                            'name' => 'send company email',
                        ],
                    ],
                ],
            ]);
    }

    public function testEventShemaNotFound()
    {
        CustomActionModelResolver::register([], true);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/schema');
        $response->assertNotFound();
    }

    public function testEventShemaForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertForbidden();
    }
}
