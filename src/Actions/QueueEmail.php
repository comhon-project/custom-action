<?php

namespace Comhon\CustomAction\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;

class QueueEmail extends SendEmail implements ShouldQueue {}
