<?php

namespace App\Actions;

class SendManualCompanyRegistrationGroupedMail extends SendManualCompanyRegistrationMail
{
    protected function shouldGroupRecipients(): bool
    {
        return true;
    }
}
