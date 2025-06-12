<?php

namespace Comhon\CustomAction\Support;

use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Illuminate\Mail\Mailables\Address;

class AddressNormalizer
{
    public static function normalize($value): Address
    {
        return match (true) {
            is_string($value) => new Address($value),
            is_array($value) => new Address($value['email'], $value['name'] ?? null),
            $value instanceof MailableEntityInterface => new Address($value->getEmail(), $value->getEmailName()),
            $value instanceof Address => $value,
            is_object($value) => new Address($value->email, $value->name ?? null),
        };
    }
}
