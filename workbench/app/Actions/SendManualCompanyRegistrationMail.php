<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\Email\AbstractSendEmail;
use Comhon\CustomAction\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Mail\Mailables\Address;

class SendManualCompanyRegistrationMail extends AbstractSendEmail
{
    use CallableManuallyTrait;

    public function __construct(
        public Company $company,
        public SystemFile $logo,
        public User|UserWithoutPreference|null $to,
    ) {}

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'recipients.to.static.mailables' => 'array',
            'recipients.to.static.mailables.*' => RuleHelper::getRuleName('model_reference').':mailable-entity,recipient',
            'test' => 'required|string',
        ];
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
            'body' => 'required|'.RuleHelper::getRuleName('html_template'),
            'test_localized' => 'string',
        ];
    }

    protected static function getCommonContextSchema(): array
    {
        return [
            'company.name' => 'string',
            'company.status' => 'string',
            'company.languages.*.locale' => 'string',
            'logo' => RuleHelper::getRuleName('is').':stored-file',
        ];
    }

    protected function getFrom(): ?Address
    {
        $from = $this->getExposedContext()['from']
            ?? $this->setting->settings['from']
            ?? null;

        return $from ? $this->normalizeAddress($from) : null;
    }

    protected function getSubject(LocalizedSetting $localizedSetting): string
    {
        return $this->getExposedContext()['subject']
            ?? $localizedSetting->settings['subject']
            ?? throw new SendEmailActionException($this->setting, 'localized settings subject is not defined');
    }

    protected function getBody(LocalizedSetting $localizedSetting): string
    {
        return $this->getExposedContext()['body']
            ?? $localizedSetting->settings['body']
            ?? throw new SendEmailActionException($this->setting, 'localized settings body is not defined');
    }

    protected function getRecipients(): array
    {
        if (! $this->to) {
            return [];
        }
        $tos = [$this->to];
        $mailables = $this->getSetting()->settings['recipients']['to']['static']['mailables'] ?? null;
        if ($mailables) {
            foreach ($mailables as $mailable) {
                $class = CustomActionModelResolver::getClass($mailable['recipient_type']);
                $tos[] = $class::findOrFail($mailable['recipient_id']);
            }
        }

        return ['to' => $tos];
    }

    protected function getAttachments(LocalizedSetting $localizedSetting): ?iterable
    {
        return [$this->logo];
    }
}
