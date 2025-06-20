<?php

namespace Database\Factories;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ManualAction>
 */
class ManualActionFactory extends Factory
{
    use ActionFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ManualAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => 'send-manual-company-email',
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withScopedSettings = false, $withAttachement = false): Factory
    {
        return $this->afterCreating(function (ManualAction $manualAction) use ($toOtherUserIds, $withScopedSettings, $withAttachement) {
            DefaultSetting::factory()->for($manualAction, 'action')
                ->sendMailRegistrationCompany($toOtherUserIds, $withAttachement)
                ->create();

            if ($withScopedSettings) {
                $this->sendMailRegistrationCompanyScoped($manualAction, $toOtherUserIds);
            }
        });
    }

    public function withContextTranslations(): Factory
    {
        return $this->afterMaking(function (ManualAction $manualAction) {
            $manualAction->type = 'send-manual-company-email-with-context-translations';
        });
    }

    public function withGroupedRecipients(): Factory
    {
        return $this->afterMaking(function (ManualAction $manualAction) {
            $manualAction->type = 'send-manual-company-grouped-email';
        });
    }

    public function action(string $actionClass): Factory
    {
        return $this->state(function (array $attributes) use ($actionClass) {
            return [
                'type' => CustomActionModelResolver::getUniqueName($actionClass)
                    ?? throw new \Exception("action $actionClass not registered"),
            ];
        });
    }
}
