<?php

namespace Comhon\CustomAction\Rules;

use Closure;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;

class ModelReference
{
    public function validate(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        if (! isset($parameters[0])) {
            throw new \Exception('must have one parameter');
        }
        $uniqueName = $parameters[0];
        $prefix = $parameters[1] ?? 'model';

        $baseClass = CustomActionModelResolver::getClass($uniqueName);
        if (! $baseClass) {
            throw new \Exception("invalid model $uniqueName");
        }
        if (! is_array($value)) {
            $exploded = explode('.', $attribute);
            $lastAttributePart = $exploded[array_key_last($exploded)];
            $validator->setFallbackMessages([
                RuleHelper::getRuleName('model_reference') => "$lastAttributePart must be an array",
            ]);

            return false;
        }
        $messageBag = FacadesValidator::make($value, [
            "{$prefix}_type" => [
                'required',
                'string',
                function (string $attribute, mixed $valueType, Closure $fail) use ($baseClass, $uniqueName, $prefix, $value) {
                    $valueClass = CustomActionModelResolver::getClass($valueType);
                    if (! is_subclass_of($valueClass, $baseClass)) {
                        $fail("The {$prefix}_type is not instance of {$uniqueName}.");
                    }
                    if (! is_subclass_of($valueClass, Model::class)) {
                        $fail("The {$prefix}_type is not instance of eloquent model.");
                    }
                    $id = $value["{$prefix}_id"] ?? null;

                    /** @var \Illuminate\Database\Eloquent\Model $model */
                    $model = new $valueClass();
                    if (
                        (! is_string($id) && ! is_numeric($id))
                        || ! $valueClass::where($model->getKeyName(), $id)->exists()
                    ) {
                        $fail("$prefix doesn't exist.");
                    }
                },
            ],
            "{$prefix}_id" => 'required',
        ])->messages();

        $valid = true;
        if ($messageBag->isNotEmpty()) {
            $valid = false;
            foreach ($messageBag->messages() as $messages) {
                foreach ($messages as $message) {
                    $validator->setFallbackMessages([RuleHelper::getRuleName('model_reference') => $message]);
                }
            }
        }

        return $valid;
    }
}
