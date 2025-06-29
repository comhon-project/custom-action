<?php

namespace Comhon\CustomAction\Actions\Email\Support;

use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Collection;

class EmailHelper
{
    public static function normalizeAddress($value): Address
    {
        return match (true) {
            is_string($value) => new Address($value),
            is_array($value) => new Address($value['email'], $value['name'] ?? null),
            $value instanceof MailableEntityInterface => new Address($value->getEmail(), $value->getEmailName()),
            $value instanceof Address => $value,
            is_object($value) => new Address($value->email, $value->name ?? null),
        };
    }

    public static function normalizeAddresses(iterable $values): array
    {
        $addresses = [];
        foreach ($values as $value) {
            $addresses[] = static::normalizeAddress($value);
        }

        return $addresses;
    }

    /**
     * if given value is a unique recipient it is wrapped in an array.
     * if given value is considered as a list of recipients, the list is just returned back.
     * it permit to always manipulate lists.
     */
    public static function makeRecipientArrayList($value): array
    {
        if ($value === null) {
            return [];
        }
        if ($value instanceof Collection) {
            return $value->all();
        }

        return ! is_array($value) || array_key_exists('email', $value)
            ? [$value]
            : $value;
    }
}
