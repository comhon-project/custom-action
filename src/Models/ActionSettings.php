<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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

    public function action(): MorphTo
    {
        return $this->morphTo();
    }
}
