<?php

namespace App\Events;

use App\Actions\SendAutomaticCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FormatContextInterface;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tests\Support\Utils;

class CompanyRegistered implements CustomEventInterface, ExposeContextInterface, FormatContextInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Company $company, public User $user) {}

    public static function getAllowedActions(): array
    {
        return [
            SendAutomaticEmail::class,
            SendAutomaticCompanyRegistrationMail::class,
        ];
    }

    public static function getContextSchema(): array
    {
        return [
            'company.name' => 'string',
            'company.status' => 'string',
            'company.languages.*.locale' => 'string',
            'logo' => RuleHelper::getRuleName('is').':stored-file',
            'user' => RuleHelper::getRuleName('is').':mailable-entity',
            'user.name' => 'string',
            'user.email' => 'email',
            'responsibles' => 'array',
            'responsibles.*' => 'array',
            'responsibles.*.email' => 'email',
            'localized' => 'string',
        ];
    }

    public function formatContext(): array
    {
        return [
            'company' => $this->company,
            'logo' => new SystemFile(Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg')),
            'user' => $this->user,
            'responsibles' => [
                [
                    'email' => 'responsible_one@gmail.com',
                ],
                [
                    'email' => 'responsible_two@gmail.com',
                ],
            ],
            'localized' => $locale ?? 'undefined',
        ];
    }
}
