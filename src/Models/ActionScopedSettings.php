<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionScopedSettings extends ActionSettingsContainer
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_action_scoped_settings';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scope' => 'array',
        'settings' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'settings',
        'scope',
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
    public function actionSettings()
    {
        return $this->belongsTo(ActionSettings::class);
    }
}
