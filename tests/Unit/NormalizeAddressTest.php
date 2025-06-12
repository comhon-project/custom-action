<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Support\AddressNormalizer;
use Illuminate\Mail\Mailables\Address;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NormalizeAddressTest extends TestCase
{
    #[DataProvider('provider_normalize_address')]
    public function test_normalize_address($value, $address, $name)
    {
        $addressInstance = AddressNormalizer::normalize($value);
        $this->assertEquals($address, $addressInstance->address);
        $this->assertEquals($name, $addressInstance->name);
    }

    public static function provider_normalize_address()
    {
        return [
            ['foo@bar.com', 'foo@bar.com', null],
            [['email' => 'foo@bar.com'], 'foo@bar.com', null],
            [['email' => 'foo@bar.com', 'name' => 'foo'], 'foo@bar.com', 'foo'],
            [new User(['email' => 'foo@bar.com', 'name' => 'foo']), 'foo@bar.com', 'foo'],
            [new Address('foo@bar.com', 'foo'), 'foo@bar.com', 'foo'],
            [(object) ['email' => 'foo@bar.com', 'name' => 'foo'], 'foo@bar.com', 'foo'],
        ];
    }
}
