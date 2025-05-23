<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class MyCallableFromEvent implements CallableFromEventInterface, CustomActionInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;
}
