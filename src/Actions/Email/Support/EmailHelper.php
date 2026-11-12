<?php

namespace Comhon\CustomAction\Actions\Email\Support;

use Comhon\CustomAction\Actions\Email\Exceptions\CustomMailableException;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Exception\RfcComplianceException;

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

    /**
     * Ensure the given addresses are valid, as they are validated when a mail is sent.
     *
     * @param  iterable<Address>  $addresses
     *
     * @throws RfcComplianceException
     */
    public static function validateAddresses(iterable $addresses): void
    {
        foreach ($addresses as $address) {
            new SymfonyAddress($address->address, (string) $address->name);
        }
    }

    /**
     * Resolve the attachments now instead of when the mail is sent, so a failure happens now.
     *
     * Local files are only checked (they are read when the mail is sent),
     * other contents (storage disk, data...) are loaded.
     *
     * @param  iterable<string|Attachable|Attachment>  $attachments
     * @return Attachment[]
     */
    public static function resolveAttachments(iterable $attachments): array
    {
        $resolved = [];
        foreach ($attachments as $attachment) {
            $attachment = match (true) {
                $attachment instanceof Attachment => $attachment,
                $attachment instanceof Attachable => $attachment->toMailAttachment(),
                default => Attachment::fromPath($attachment),
            };

            $resolved[] = $attachment->attachWith(
                function (string $path, Attachment $attachment) {
                    // remote files (url...) can't be checked without fetching them
                    if (! str_contains($path, '://') && ! (is_file($path) && is_readable($path))) {
                        throw new CustomMailableException("attachment file '$path' doesn't exist or isn't readable");
                    }

                    return $attachment;
                },
                function (\Closure $data, Attachment $attachment) {
                    $content = $data();
                    if ($content === null) {
                        throw new CustomMailableException("attachment '{$attachment->as}' content can't be retrieved");
                    }

                    return Attachment::fromData(fn () => $content, $attachment->as)->withMime($attachment->mime);
                },
            );
        }

        return $resolved;
    }
}
