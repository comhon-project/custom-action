<?php

namespace Comhon\CustomAction\Services;

use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Exceptions\SimulateActionException;
use Comhon\CustomAction\Exceptions\SimulateMethodDoestExistException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Models\Setting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ActionService
{
    public function storeDefaultSetting(Action $action, array $input): DefaultSetting
    {
        if ($action->defaultSetting()->exists()) {
            throw new AccessDeniedHttpException('default settings already exist');
        }
        $validated = Validator::validate($input, $this->getSettingsRules($action, false));
        $validated['settings'] ??= [];
        $defaultSetting = new DefaultSetting($validated);
        $defaultSetting->action()->associate($action);
        $defaultSetting->save();
        $defaultSetting->unsetRelation('action');

        return $defaultSetting;
    }

    public function storeScopedSetting(Action $action, array $input): ScopedSetting
    {
        $validated = Validator::validate($input, $this->getSettingsRules($action, true));
        $validated['settings'] ??= [];
        $scopedSettings = new ScopedSetting($validated);
        $scopedSettings->action()->associate($action);
        $scopedSettings->save();
        $scopedSettings->unsetRelation('action');

        return $scopedSettings;
    }

    public function getSettingsRules(Action $action, bool $scoped): array
    {
        $actionClass = $action->getActionClass();
        $eventContext = $action instanceof EventAction
            ? $action->eventListener->getEventClass()
            : null;

        $rules = RuleHelper::getSettingsRules($actionClass::getSettingsSchema($eventContext));
        if ($scoped) {
            $rules['scope'] = 'required|array';
            $rules['name'] = 'required|string|max:63';
        }

        return $rules;
    }

    /**
     * Store localized settings.
     */
    public function storeLocalizedSetting(Setting $setting, array $inputs): LocalizedSetting
    {
        $rules = $this->getLocalizedSettingsRules($setting->action);
        $validated = Validator::validate($inputs, $rules);

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = $validated['settings'] ?? [];
        $localizedSetting->locale = $validated['locale'];
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

        return $localizedSetting->unsetRelations();
    }

    /**
     * Store localized settings.
     */
    public function getLocalizedSettingsRules(Action $action, $prefix = 'settings'): array
    {
        $actionClass = $action->getActionClass();
        $eventContext = $action instanceof EventAction
            ? $action->eventListener->getEventClass()
            : null;

        $actionClass = $action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext), $prefix);
        $rules['locale'] = 'required|string';

        return $rules;
    }

    public function simulate(Action $action, array $inputs)
    {
        $validated = $this->validateSimulationInputs($action, $inputs);
        $validated['settings'] ??= is_array($inputs['settings'] ?? null) ? [] : null;
        $validated['localized_settings'] ??= is_array($inputs['localized_settings'] ?? null) ? [] : null;

        $setting = $validated['settings'] !== null ? new DefaultSetting(['settings' => $validated['settings']]) : null;
        $localizedSetting = $validated['localized_settings'] !== null ?
            (new LocalizedSetting)->forceFill([
                'settings' => $validated['localized_settings'],
                'locale' => $validated['locale'] ?? App::getLocale(),
            ])
            : null;

        $results = [];
        $hasMatrix = isset($validated['states']) && count($validated['states']);
        $states = $hasMatrix
            ? $this->getFlattenedStates($validated['states'])
            : [null];

        $customActionClass = CustomActionModelResolver::getClass($action->type);

        foreach ($states as $state) {
            // Wrap fake instantiation and action simulation in a non-committed database transaction
            // to prevent any changes from being applied to the database.
            try {
                DB::beginTransaction();

                $customAction = $customActionClass::buildFakeInstance($action, $setting, $localizedSetting, $state);

                if (! $customAction instanceof SimulatableInterface) {
                    throw new SimulateActionException("cannot simulate action {$action->type}");
                }
                if (! method_exists($customAction, 'simulate')) {
                    throw new SimulateMethodDoestExistException(get_class($customAction));
                }

                $results[] = [
                    'success' => true,
                    'result' => app()->call([$customAction, 'simulate']),
                    ...($state !== null ? ['state' => $state] : []),
                ];
            } catch (SimulateActionException|SimulateMethodDoestExistException $th) {
                throw $th;
            } catch (\Throwable $th) {
                $results[] = $this->reportError($th, $state);
            } finally {
                DB::rollBack();
            }
        }

        return $hasMatrix ? $results : $results[0];
    }

    private function reportError(\Throwable $th, ?array $state)
    {
        $result = [
            'success' => false,
            ...($state !== null ? ['state' => $state] : []),
        ];
        if (config('app.debug')) {
            $result['message'] = $th->getMessage();
            $result['trace'] = $th->getTrace();
        }
        if (
            method_exists($th, 'render') &&
            ($response = app()->call([$th, 'render'])) &&
            $response instanceof JsonResponse
        ) {
            $result = [...$result, ...$response->getData(true)];
        }

        return $result;
    }

    private function validateSimulationInputs(Action $action, array $inputs)
    {
        $contextClass = $action->getContextClass();
        $hasFakeState = is_subclass_of($contextClass, HasFakeStateInterface::class);
        if (isset($inputs['states']) && ! $hasFakeState) {
            $uniqueName = CustomActionModelResolver::getUniqueName($contextClass);
            throw new UnprocessableEntityHttpException("$uniqueName has no state to simulate action");
        }

        $stateSchema = $hasFakeState ? $this->getFakeStateSchema($contextClass) : null;

        return Validator::validate($inputs, [
            ...(isset($inputs['settings']) ? $this->getSettingsRules($action, false) : []),
            ...(isset($inputs['localized_settings']) ? $this->getLocalizedSettingsRules($action, 'localized_settings') : []),

            // override 'required' rules
            'locale' => ['nullable', 'string'],

            'states' => [
                'nullable',
                'array',
                function (string $attribute, mixed $collectionStates, \Closure $fail) use ($stateSchema) {
                    if (! is_array($collectionStates)) {
                        return;
                    }
                    foreach ($collectionStates as $i => $matrixStates) {
                        $level1 = "$attribute.$i";
                        if ($this->validateCurrentState($stateSchema, $level1, $matrixStates, $fail, true)) {
                            foreach ($matrixStates as $j => $states) {
                                $level2 = "$level1.$j";
                                if ($this->validateCurrentState($stateSchema, $level2, $states, $fail, true)) {
                                    foreach ($states as $k => $state) {
                                        $level3 = "$level2.$k";
                                        $this->validateCurrentState($stateSchema, $level3, $state, $fail, false);
                                    }
                                }
                            }
                        }
                    }
                },
            ],
        ]);
    }

    private function getFakeStateSchema(string $hasFakeStateClass): array
    {
        $computed = [
            'without_value' => [],
            'with_value' => [],
        ];

        foreach ($hasFakeStateClass::getFakeStateSchema() as $key => $value) {
            if (is_numeric($key)) {
                $computed['without_value'][] = $value;
            } else {
                $computed['with_value'][$key] = $value;
            }
        }

        return $computed;
    }

    /**
     * @return bool true if current state is a list and can go deeper
     */
    private function validateCurrentState(array $stateSchema, $attribute, $value, \Closure $fail, bool $allowList): bool
    {
        if (is_string($value)) {
            if (! in_array($value, $stateSchema['without_value'])) {
                $fail("The {$attribute} is invalid.");
            }

            return false;
        }
        if (! is_array($value)) {
            $fail("The {$attribute} must be a string or an array.");

            return false;
        }
        if (! Arr::isList($value)) {
            if (count($value) == 1) {
                $validator = Validator::make($value, $stateSchema['with_value']);
                if (! $validator->passes() || count($validator->validated()) != 1) {
                    $fail("The {$attribute} is invalid.");
                }
            } else {
                $fail("The {$attribute} is invalid.");
            }

            return false;
        } elseif (! $allowList) {
            $fail("The {$attribute} is invalid.");

            return false;
        }

        return true;
    }

    public function getFlattenedStates(array $collectionStates): array
    {
        $flatenedStates = [];

        foreach ($collectionStates as $matrixStates) {
            if (is_string($matrixStates) || ! Arr::isList($matrixStates)) {
                $flatenedStates[] = [$matrixStates];
            } else {
                $normalizedMatrix = [];
                $indexes = [];
                foreach ($matrixStates as $states) {
                    $indexes[] = 0;
                    $states = is_string($states) || ! Arr::isList($states)
                        ? [$states]
                        : $states;

                    if (! empty($states)) {
                        $normalizedMatrix[] = $states;
                    }
                }

                $last = count($normalizedMatrix) - 1;
                $current = $last;
                while ($current != -1) {
                    if ($current == $last) {
                        $state = [];
                        foreach ($normalizedMatrix as $i => $states) {
                            $state[] = $states[$indexes[$i]];
                        }
                        $flatenedStates[] = $state;
                    }
                    if ($indexes[$current] == count($normalizedMatrix[$current]) - 1) {
                        $indexes[$current] = 0;
                        $current--;
                    } else {
                        $indexes[$current] = $indexes[$current] + 1;
                        $current = $last;
                    }
                }
            }
        }

        return $flatenedStates;
    }
}
