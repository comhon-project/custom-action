<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionSettings extends ActionSettingsContainer
{
    use HasFactory;
    use SoftDeletes;

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
        static::deleting(function (ActionSettings $action) {
            $scopedSettingss = $action->scopedSettings()->get([
                $action->scopedSettings()->getRelated()->getTable().'.id',
            ]);
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

    public function scopedSettings(): HasMany
    {
        return $this->hasMany(ActionScopedSettings::class, 'action_settings_id');
    }

    public function eventAction(): HasOne
    {
        return $this->hasOne(EventAction::class, 'action_settings_id');
    }

    public function manualAction(): HasOne
    {
        return $this->hasOne(ManualAction::class, 'action_settings_id');
    }

    public function getAction(): ManualAction|EventAction
    {
        return $this->eventAction ?? $this->manualAction;
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