<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\CustomActionSettings>
 */
class CustomActionSettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomActionSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => 'send-email',
            'settings' => [],
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany($to = null, $withScopedSettings = false, $type = 'send-email', $withAttachement = false): Factory
    {
        return $this->state(function (array $attributes) use ($to, $type, $withAttachement) {
            return [
                'type' => $type,
                'settings' => [
                    'to' => $to,
                    'attachments' => $withAttachement ? ['logo'] : null,
                ],
            ];
        })->afterCreating(function (CustomActionSettings $action) use ($withScopedSettings) {
            $localizedSettings = new ActionLocalizedSettings();
            $localizedSettings->localizable()->associate($action);
            $localizedSettings->locale = 'en';
            $localizedSettings->settings = [
                'subject' => 'Dear {{ to.first_name }}, company {{ company.name }} (last login: '
                    .'{{ to.last_login_at|format_datetime(\'long\', \'short\') }} ({{default_timezone}}) '
                    .'{{ to.last_login_at|format_datetime(\'long\', \'short\', timezone=preferred_timezone) }} ({{preferred_timezone}}))',
                'body' => 'Dear {{ to.name }}, company <strong>{{ company.name }}</strong> has been registered !',
            ];
            $localizedSettings->save();

            $localizedSettings = new ActionLocalizedSettings();
            $localizedSettings->localizable()->associate($action);
            $localizedSettings->locale = 'fr';
            $localizedSettings->settings = [
                'subject' => 'Cher·ère {{ to.first_name }}, la société {{ company.name }} (dernier login: '
                .'{{ to.last_login_at|format_datetime(\'long\', \'short\') }} ({{default_timezone}}) '
                .'{{ to.last_login_at|format_datetime(\'long\', \'short\', timezone=preferred_timezone) }} ({{preferred_timezone}}))',
                'body' => 'Cher·ère {{ to.name }}, la société <strong>{{ company.name }}</strong> à été inscrite !',
            ];
            $localizedSettings->save();

            if ($withScopedSettings) {
                $scopedSettings = new ActionScopedSettings();
                $scopedSettings->customActionSettings()->associate($action);
                $scopedSettings->scope = ['company' => ['name' => 'My VIP company']];
                $scopedSettings->settings = [];
                $scopedSettings->save();

                $localizedSettings = new ActionLocalizedSettings();
                $localizedSettings->localizable()->associate($scopedSettings);
                $localizedSettings->locale = 'en';
                $localizedSettings->settings = [
                    'subject' => 'Dear {{ to.first_name }}, VIP company {{ company.name }} (verified at: {{ to.verified_at|format_datetime(\'long\', \'none\') }} ({{preferred_timezone}}))',
                    'body' => 'the VIP company <strong>{{ company.name }}</strong> has been registered !!!',
                ];
                $localizedSettings->save();

                $localizedSettings = new ActionLocalizedSettings();
                $localizedSettings->localizable()->associate($scopedSettings);
                $localizedSettings->locale = 'fr';
                $localizedSettings->settings = [
                    'subject' => 'Cher·ère {{ to.first_name }}, société VIP {{ company.name }} (vérifié à: {{ to.verified_at|format_datetime(\'long\', \'none\') }} ({{preferred_timezone}}))',
                    'body' => 'la société VIP <strong>{{ company.name }}</strong> à été inscrite !!!',
                ];
                $localizedSettings->save();
            }
        });
    }
}
