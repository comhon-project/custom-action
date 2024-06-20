<?php

namespace App\Events;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\TargetableEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tests\Support\Utils;

class CompanyRegistered implements CustomEventInterface, TargetableEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Company $company, public User $user) {}

    public function target(): User
    {
        return $this->user;
    }

    /**
     * Get actions that might be attached to event
     */
    public static function getAllowedActions(): array
    {
        return [
            SendTemplatedMail::class,
            SendCompanyRegistrationMail::class,
        ];
    }

    /**
     * Get event binding schema
     */
    public static function getBindingSchema(): array
    {
        return [
            'company.name' => 'string',
            'logo' => 'file',
        ];
    }

    /**
     * Get event binding values
     */
    public function getBindingValues(): array
    {
        return [
            'company' => $this->company,
            'logo' => Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg'),
        ];
    }
}
