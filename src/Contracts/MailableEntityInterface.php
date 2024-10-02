<?php

namespace Comhon\CustomAction\Contracts;

interface MailableEntityInterface
{
    /**
     * Get receiver email
     */
    public function getEmail(): string;

    /**
     * Get receiver name
     */
    public function getEmailName(): ?string;

    /**
     * Get values that can be exposed in email
     */
    public function getExposableValues(): array;
}
