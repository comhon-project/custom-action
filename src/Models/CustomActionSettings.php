<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomActionSettings extends ActionSettingsContainer
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_action_settings';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted()
    {
        static::deleting(function (CustomActionSettings $action) {
            $scopedSettingss = $action->scopedSettings()->get(['action_scoped_settings.id']);
            foreach ($scopedSettingss as $scopedSettings) {
                $scopedSettings->delete();
            }
            ActionLocalizedSettings::whereHasMorph(
                'localizable',
                [static::class],
                function ($query) use ($action) {
                    $query->where('id', $action->id);
                }
            )->delete();
        });
    }

    public function scopedSettings()
    {
        return $this->hasMany(ActionScopedSettings::class);
    }

    /**
     * get settings container according scope and given values
     */
    public function getSettingsContainer(array $values): ActionSettingsContainer
    {
        $model = $this;
        foreach ($this->scopedSettings as $scopedSettings) {
            $match = true;
            foreach ($scopedSettings->scope as $modelName => $filter) {
                foreach ($filter as $property => $value) {
                    if (($values[$modelName][$property] ?? null) != $value) {
                        $match = false;

                        break 2;
                    }
                }
            }
            if ($match) {
                $model = $scopedSettings;
                break;
            }
        }

        return $model;
    }
}
