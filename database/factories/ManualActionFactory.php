<?php

namespace Database\Factories;

use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ManualAction>
 */
class ManualActionFactory extends Factory
{
    use ActionFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ManualAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => 'simple-manual-action',
        ];
    }

    public function action(string $actionClass): Factory
    {
        if (is_subclass_of($actionClass, CallableFromEventInterface::class)) {
            throw new \Exception('given action is an event action, must be a manual action');
        }

        return $this->state(function (array $attributes) use ($actionClass) {
            return [
                'type' => CustomActionModelResolver::getUniqueName($actionClass)
                    ?? throw new \Exception("action $actionClass not registered"),
            ];
        });
    }
}
