<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;

trait ActionFactoryTrait
{
    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompanyScoped(Action $action, ?array $toOtherUserIds = null)
    {
        $keyReceivers = $toOtherUserIds === null ? 'bindings' : 'static';
        $valueReceivers = $toOtherUserIds === null
            ? ['user']
            : collect($toOtherUserIds)->map(fn ($id) => ['recipient_type' => 'user', 'recipient_id' => $id])->all();

        $scopedSettings = new ScopedSetting;
        $scopedSettings->action()->associate($action);
        $scopedSettings->name = 'my scoped settings';
        $scopedSettings->scope = ['company' => ['name' => 'My VIP company']];
        $scopedSettings->settings = [
            'recipients' => ['to' => [$keyReceivers => ['mailables' => $valueReceivers]]],
        ];
        $scopedSettings->save();

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->localizable()->associate($scopedSettings);
        $localizedSetting->locale = 'en';
        $localizedSetting->settings = [
            'subject' => 'Dear {{ to.first_name }}, VIP company {{ company.name }} {{ localized }} (verified at: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
            'body' => 'the VIP company <strong>{{ company.name }}</strong> has been registered !!!',
        ];
        $localizedSetting->save();

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->localizable()->associate($scopedSettings);
        $localizedSetting->locale = 'fr';
        $localizedSetting->settings = [
            'subject' => 'Cher·ère {{ to.first_name }}, société VIP {{ company.name }} {{ localized }} (vérifié à: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
            'body' => 'la société VIP <strong>{{ company.name }}</strong> à été inscrite !!!',
        ];
        $localizedSetting->save();
    }
}
