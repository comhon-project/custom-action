<?php

namespace Comhon\CustomAction;

class CustomActionRegistrar
{
    private $targetBindings = null;

    /**
     * get target bindings
     */
    public function getTargetBindings()
    {
        if (! $this->targetBindings) {
            $bindings = config('custom-action.target_bindings', []);
            if ($bindings instanceof \Closure) {
                $bindings = $bindings();
            }
            if (! is_array($bindings)) {
                throw new \Exception('invalid config target_bindings, must be an array or a closure that return an array');
            }
            $this->targetBindings = [];
            foreach ($bindings as $key => $value) {
                if (is_numeric($key)) {
                    $key = $value;
                    $value = 'string';
                }
                $this->targetBindings['to.'.$key] = $value;
            }
        }

        return $this->targetBindings;
    }
}
