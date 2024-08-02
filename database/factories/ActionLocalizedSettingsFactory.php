<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ActionLocalizedSettings>
 */
class ActionLocalizedSettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ActionLocalizedSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'localizable_id' => null,
            'localizable_type' => null,
            'locale' => 'en',
            'settings' => [],
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ActionLocalizedSettings $actionLocalizedSettings) {
            if (! $actionLocalizedSettings->localizable) {
                $actionLocalizedSettings->localizable()->associate(ActionSettings::factory()->create());
            }
        });
    }

    /**
     * Indicate that the user is suspended.
     */
    public function emailSettings($locale, $withLastLogin = false): Factory
    {
        return $this->state(function (array $attributes) use ($locale, $withLastLogin) {
            $settings = $locale == 'fr'
                ? [
                    'subject' => 'Cher·ère {{ to.first_name }}, la société {{ company.name }} {{ localized }}',
                    'body' => 'Cher·ère {{ to.name }}, la société <strong>{{ company.name }}</strong> à été inscrite !',
                ]
                : [
                    'subject' => 'Dear {{ to.first_name }}, company {{ company.name }} {{ localized }}',
                    'body' => 'Dear {{ to.name }}, company <strong>{{ company.name }}</strong> has been registered !',
                ];
            if ($withLastLogin) {
                $settings['subject'] .= ' (login:'
                .' {{ to.last_login_at|format_datetime(\'long\', \'short\') }} ({{default_timezone}})'
                .' {{ to.last_login_at|format_datetime(\'long\', \'short\', timezone=preferred_timezone) }} ({{preferred_timezone}}))';
            }

            return [
                'locale' => $locale,
                'settings' => $settings,
            ];
        });
    }
}
