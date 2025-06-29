<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\Email\AbstractSendManualEmail;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Rules\RuleHelper;

class SendAttachedEmail extends AbstractSendManualEmail implements HasTranslatableContextInterface
{
    use CallableManuallyTrait;

    public array $responsibles = [
        ['email' => 'responsible_one@gmail.com'],
        ['email' => 'responsible_two@gmail.com'],
    ];

    public function __construct(
        public SystemFile $logo,
        public User|UserWithoutPreference|null $user = null,
        public ?User $otherUser = null,
        protected ?bool $grouped = null,
    ) {}

    protected static function getCommonContextSchema(): array
    {
        return [
            'user.first_name' => 'string',
            'user.translation' => 'string',
            'otherUser.first_name' => 'string',
            'otherUser.translation' => 'string',
            'logo' => RuleHelper::getRuleName('is').':stored-file',
        ];
    }

    public static function getTranslatableContext(): array
    {
        return [
            'user.translation' => fn ($value, $locale) => match ($locale) {
                'en' => "$value en",
                'fr' => "$value fr",
            },
        ];
    }
}
