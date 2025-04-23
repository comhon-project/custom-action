<?php

namespace Comhon\CustomAction\Actions\Email;

use Illuminate\Contracts\Queue\ShouldQueue;

class QueueAutomaticEmail extends SendAutomaticEmail implements ShouldQueue {}
