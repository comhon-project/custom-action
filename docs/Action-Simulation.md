# Action Simulation

When defining [settings](Settings.md), it can be difficult to ensure they are configured correctly. While the settings may be valid, the resulting behavior might differ from what is expected. A preview of the outcome is often desirable before the action is executed for real. To achieve this, the action can be simulated and its result observed.

## Prerequisite

Before running a simulation, a fake context for the action and the way the simulation should be handled must be defined.

### Fake context

Actions that implement `CustomActionInterface` and events that implement `CustomEventInterface` can be faked depending if the action is a manual action or an action triggered from event (event action).

-   for manual action you will have to fake the action
-   for event action you will have to fake the event

By "faking" the context, we mean instantiating an action or event by injecting fake data into the object as its [context](Context.md). To do so, the class must implements the interface `FakableInterface` and you will have to implements the static method `fake`.

```php
    public function __construct(public MyContextObjet $myContextObjet){}

    public static function fake(): static
    {
        return new static(MyContextObjet::factory()->create());
    }
```

**Note:** The entire simulation process is performed within a database transaction to prevent any permanent changes. Therefore, you can apply changes to the database safely, any changes will be rolled back. If you want to be sure that the fake method is safely called in the simulation process, you can use the `isFakingSafe` method (returns a boolean) or the `ensureFakingSafe` method (throws an exception if not safe).


```php
use Comhon\CustomAction\Services\ActionService;

    public function __construct(public MyContextObjet $myContextObjet){}

    public static function fake(): static
    {
        ActionService::ensureFakingSafe(); // <- throw exception if not safe
        return new static(MyContextObjet::factory()->create());
    }
```

### Define the simulation

To mark an action as simulatable, the custom action class must implement the interface `SimulatableInterface` and you will have to implement the method `simulate`. Note that dependencies can be type-hinted on the `simulate` method. These dependencies are automatically injected by the Laravel [service container](https://laravel.com/docs/master/container).

Obviously, the simulate method should use all or almost all the code that is used when the action is actually handled, otherwise, the simulation would be pointless. 

```php
    public function simulate(MyInjectedDependency $dependency)
    {
        $model = $this->doMagic($dependency->getMagic());
        return $model->toArray();
    }

    public function handle(MyInjectedDependency $dependency)
    {
        $model = $this->doMagic($dependency->getMagic());
        $model->save();
    }
```

The return value of the `simulate` method will be sent as the API response.

## Execute the simulation

The customization API allows the consumer to simulate an action, they just have to call either :

-   [POST /manual-actions/{type}/simulate](Customization-API#post-manual-actionstypesimulate)
-   [POST /event-actions/{id}/simulate](Customization-API#post-event-actionsidsimulate)

When one of these routes is called, settings can be sent in the request body to be used when handling the action instead of those stored in the database.

On the client side, it can be determined that an action is simulatable when retrieving the action schema through the `simulatable` return property.  
-   [GET /actions/{type}/schema](Customization-API#get-actionstypeschema)

## States

As custom actions may have different outcomes depending on the [context](Context.md), the simulation should be performed across several states. Therefore, when you execute a simulation you can specify several states that will permit to build several contexts.

### Build context with a state

A state is defined by a set of one or more values. State values will be injected to the `fake` method (defined on a custom action or on a custom event). You will be able to define your context as you want with the injected state.

A state value can be either
-   a simple string
-   an array with only one key and an associated value.

implementation example:

```php
    public static function fake(?array $state = null): static
    {
        $dependencyCount = 1;
        $modelState = [];
        foreach ($state ?? [] as $value) {
            if (is_array($value)) {
                match (array_key_first($value)) {
                    'dependency_count' => $dependencyCount = $value['dependency_count'],
                }
            } else {
                match ($value) {
                    'status_verified' => $modelState['verified_at'] = Carbon::now(),
                }
            }
        }
        $model = MyModel::factory($modelState)
            ->has(MyDependency::factory()->count($dependencyCount))
            ->create();

        return new static($model);
    }
```

### State definition

To enable several states to simulate action, A custom action or a custom event must implement the interface `HasFakeStateInterface` and must implement the method `getFakeStateSchema`. The return of this function will permit to validate the given states accross the API to simulate an action.

There are two way to define a state value:
-   When a state value is a simple string you just have to define a value without a key.
-   When a state value is a key/value peer you must define a key and associate a [validation](https://laravel.com/docs/master/validation) as value.

```php
    public static function getFakeStateSchema(): array
    {
        return [
            'status_verified',
            'dependency_count' => 'integer|min:1',
        ];
    }
```

On the client side, it can be determined that an action can be simulated with several states when retrieving the action schema through the `fake_state_schema` return property.  
-   [GET /actions/{type}/schema](Customization-API#get-actionstypeschema)

if `fake_state_schema` is not null that means the action can be tested with several states across the API.

### Simulate with states

#### Request

To simulate action with several states, you will have call `simulate` routes mentioned earlier but with the additional `states` input. The `states` input is a list of states and each one of them is a matrix of state values.

Example:
```json
{
  "states": [
    [
      ["value_1", {"key_1": 5}],
      ["value_2", "value_3"],
      ["value_4"]
    ],
    [
      [{"key_1": 10}],
      ["value_5"]
    ],
    [
      ["value_6"]
    ]
  ]
}
```
6 simulations will be executed with following states:
-   `value_1`, `value_2`, `value_4`
-   `value_1`, `value_3`, `value_4`
-   `{"key_1": 5}`, `value_2`, `value_4`
-   `{"key_1": 5}`, `value_3`, `value_4`
-   `{"key_1": 10}`, `value_5`
-   `value_6`

There are some syntax shortcuts to simplify the `states` value: 
```json
{
  "states": [
    "value_1",
    {"key_1": 5},
    ["value_2"],
    [{"key_1": 10}],
  ]
}
```
is equivalent to : 
```json
{
  "states": [
    [["value_1"]],
    [[{"key_1": 5}]],
    [["value_2"]],
    [[{"key_1": 10}]],
  ]
}
```

#### Response

The response will contain an array of simulations results.

```json
[
  {
    "success": true,
    "result": {"some": "data"},
    "state": ["state_value_1", "state_value_2"],
  },
  {
    "success": false,
    "message": "some message",
    "state": ["state_value_3"],
  }
]
```
(the array of result may be encapsuled in a `data` key)