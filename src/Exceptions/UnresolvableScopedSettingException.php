<?php

namespace Comhon\CustomAction\Exceptions;

class UnresolvableScopedSettingException extends RenderableException
{
    public function __construct(public array $scopedSettings, public string $actionClass)
    {
        $ids = collect($scopedSettings)->pluck('id')->implode(', ');
        $this->message = "cannot resolve conflict between several scoped settings ($ids)";
    }
}
