<?php

namespace Comhon\CustomAction\Contracts;

interface MailableEntityInterface
{
    /**
     * Get entity email
     */
    public function getEmail(): string;

    /**
     * Get entity name
     */
    public function getEmailName(): ?string;

    /**
     * Get values that can be exposed in email
     */
    public function getExposableValues(): array;
}
