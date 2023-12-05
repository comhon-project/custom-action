<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActionScopedSettings extends ActionSettingsContainer
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'action_scoped_settings';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scope' => 'array',
        'settings' => 'array',
    ];

    protected static function booted()
    {
        static::deleting(function (ActionScopedSettings $actionScopedSettings) {
            ActionLocalizedSettings::whereHasMorph(
                'localizable',
                [static::class],
                function ($query) use ($actionScopedSettings) {
                    $query->where('id', $actionScopedSettings->id);
                }
            )->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customActionSettings()
    {
        return $this->belongsTo(CustomActionSettings::class);
    }
}
