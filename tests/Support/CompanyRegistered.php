<?php

namespace Comhon\CustomAction\Tests\Support;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\TargetableEventInterface;
use Comhon\CustomAction\Tests\Support\Models\Company;
use Comhon\CustomAction\Tests\Support\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyRegistered implements CustomEventInterface, TargetableEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Company $company, public User $user)
    {
    }

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
        $sep = DIRECTORY_SEPARATOR;

        return [
            'company' => $this->company,
            'logo' => __DIR__."{$sep}..{$sep}Data{$sep}jc.jpeg",
        ];
    }
}
