<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;

class SendAutomaticEmailAfterCommit extends SendAutomaticEmail
{
    protected $sendAfterCommit = true;
}
