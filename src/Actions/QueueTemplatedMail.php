<?php

namespace Comhon\CustomAction\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;

class QueueTemplatedMail extends SendTemplatedMail implements ShouldQueue {}
