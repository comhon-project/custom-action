<?php

namespace Comhon\CustomAction\Rules;

use Comhon\TemplateRenderer\Rules\Template;
use Illuminate\Validation\Validator;

class TextTemplate
{
    public function validate(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $valid = true;
        $validation = new Template;
        $validation->validate($attribute, $value, function ($message) use (&$valid, $validator) {
            $valid = false;
            $validator->setFallbackMessages([RuleHelper::getRuleName('text_template') => $message]);
        });

        return $valid;
    }
}
