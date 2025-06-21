<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Support\EmailHelper;
use Illuminate\Mail\Mailables\Address;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailHelperTest extends TestCase
{
    #[DataProvider('provider_normalize_address')]
    public function test_normalize_address($value, $address, $name)
    {
        $addressInstance = EmailHelper::normalizeAddress($value);
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

    #[DataProvider('provider_make_recipient_array_list')]
    public function test_make_recipient_array_list($param, $expected)
    {
        $this->assertEquals($expected, EmailHelper::makeRecipientArrayList($param));
    }

    public static function provider_make_recipient_array_list()
    {
        $email = 'foo@bar.com';
        $emailArray = ['email' => 'foo@bar.com'];
        $emailList = ['foo@bar.com'];

        return [
            [null, []],
            [[], []],
            [collect($emailList), $emailList],
            [$email, [$email]],
            [$emailArray, [$emailArray]],
            [$emailList, $emailList],
        ];
    }
}
