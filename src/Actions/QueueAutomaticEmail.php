<?php

namespace Comhon\CustomAction\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;

class QueueAutomaticEmail extends SendAutomaticEmail implements ShouldQueue {}
