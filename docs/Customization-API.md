# Customization API

This library provide a ready to use API to configure actions and there settings.

## Prerequisite

before any usage of the Customization API, you must configure :

- A model resolver (see [Model Resolver](Library-Core#model-resolver))
- A Catalog that list usable manual actions if you use manual actions (see [Manual Actions Catalog](Library-Core#manual-actions-catalog))
- A Catalog that list usable events if you use event actions (see [Events Catalog](Library-Core#events-catalog))

Note: In the Customization API you will always use unique names, NOT php class names.

Note: For routes that use `{type}` in there path, the type is actually a unique name of an action class.

Note: Response body will be wrapped or not in a `data` key according your application configuration (see [Data Wrapping](https://laravel.com/docs/master/eloquent-resources#data-wrapping)).

Note: You can configure API by updating some values in the [config file](Library-Configuration.md).

## Authorization

There are two ways to manage Customization API access authorizations.

### Middleware

You can configure [middlewares](https://laravel.com/docs/master/middleware) that will run before accessing the API. To do so, you just have to update the [Configuration file](Library-Configuration.md).

### Policies

You can publish the policies files with:

```bash
php artisan vendor:publish --tag="custom-action-policies"
```

You will be able to configure authorizations to access each route of the Customization API.

Note: You will have to set the `use_policies` to `true` in the [config file](Library-Configuration.md).

## Endpoints Overview

### 1. Actions

#### `GET /actions/{type}/schema`

**Description**: Retrieve the schema for a specific action type.

**Query Parameters**:

| Name          | Type   | Description                                                                                                                                                                 |
| ------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| event_context | string | a unique name of an event. actually when an action is triggered by an event, some of its settings may be defined based on this event (should be sent only for event action) |

**Response Body**:

| Name                                    | Type    | Description                                                                          |
| --------------------------------------- | ------- | ------------------------------------------------------------------------------------ |
| settings_schema                         | object  | schema that describe all customizable settings                                       |
| localized_settings_schema               | object  | schema that describe all customizable settings that are related to a specific locale |
| context_schema                          | object  | schema that describe all context values                                              |
| translatable_context                    | array   | list of all context values that are translatable                                     |
| context_keys_ignored_for_scoped_setting | array   | context keys that can't be used to resolve scoped settings                           |
| simulatable                             | boolean | determine whether the action can be simulated                                        |

---

### 2. Manual Actions

#### `GET /manual-actions`

**Description**: Get the list of allowed manual action types.

#### `GET /manual-actions/{type}`

**Description**: Get details for a specific manual action type (including default settings).

**Response Body**:

| Name                     | Type              | Description                       |
| ------------------------ | ----------------- | --------------------------------- |
| type                     | string            | action type (unique name)         |
| default_setting          | object (nullable) | the default setting of the action |
| default_setting.id       | integer           | the default setting identifier    |
| default_setting.settings | object            | settings by key-value             |

#### `GET /manual-actions/{type}/scoped-settings`

**Description**: Get a list of scoped settings for a specific manual action type.

**Response Body**:

| Name    | Type    | Description                    |
| ------- | ------- | ------------------------------ |
| \*      | array   |                                |
| \*.id   | integer | the scoped setting identifier  |
| \*.name | string  | the name of the scoped setting |

#### `POST /manual-actions/{type}/default-settings`

**Description**: Store default settings for a specific manual action type.

**Request Body**:

| Name     | Type   | Description           |
| -------- | ------ | --------------------- |
| settings | object | settings by key-value |

#### `POST /manual-actions/{type}/scoped-settings`

**Description**: Store scoped settings for a specific manual action type.

**Request Body**:

| Name     | Type    | Required | Description                                                                     |
| -------- | ------- | -------- | ------------------------------------------------------------------------------- |
| name     | string  | true     | the scoped setting name                                                         |
| scope    | object  | true     | scope by path-value (path being the path to a context value using dot notation) |
| settings | object  | true     | settings by key-value                                                           |
| priority | integer | false    | the scoped setting priority                                                     |

#### `POST /manual-actions/{type}/simulate`

**Description**: Simulate an action handling. Typically, it allows you to verify that the settings are properly defined.

**Request Body**:

| Name               | Type   | Required | Description                                                                              |
| ------------------ | ------ | -------- | ---------------------------------------------------------------------------------------- |
| settings           | object | false    | settings by key-value                                                                    |
| localized_settings | object | false    | localized_settings by key-value                                                          |
| locale             | string | false    | the locale of the given localized_settings                                               |
| states             | array  | false    | to kown how to simulate with states see [states](Action-Simulation#simulate-with-states) |

if settings and/or localized settings are not sent, stored settings and/or localized settings will be used.

**Response Body**:

| Name    | Type    | Description                                                                    |
| ------- | ------- | ------------------------------------------------------------------------------ |
| success | boolean | true if simulation is successful                                               |
| result  | object  | the return of the simulation                                                   |
| message | string  | an error message if the simulation failed (only if error thrown is renderable) |
| trace   | array   | the stack trace if the simulation failed (only if the app is in debug mode)    |

---

### 3. Default Settings

#### `GET /default-settings/{id}`

**Description**: Retrieve a specific default setting.

**Response Body**:

| Name     | Type    | Description                    |
| -------- | ------- | ------------------------------ |
| id       | integer | the default setting identifier |
| settings | object  | settings by key-value          |

#### `PUT /default-settings/{id}`

**Description**: Update a specific default setting.

**Request Body**:

| Name     | Type   | Description           |
| -------- | ------ | --------------------- |
| settings | object | settings by key-value |

#### `GET /default-settings/{id}/localized-settings`

**Description**: Get a list of localized settings for a specific default setting.

**Response Body**:

| Name      | Type    | Description                      |
| --------- | ------- | -------------------------------- |
| \*        | array   |                                  |
| \*.id     | integer | the localized setting identifier |
| \*.locale | string  | the localized setting locale     |

#### `POST /default-settings/{id}/localized-settings`

**Description**: Store localized settings for a specific default setting.

**Request Body**:

| Name     | Type   | Description                  |
| -------- | ------ | ---------------------------- |
| locale   | string | the localized setting locale |
| settings | object | settings by key-value        |

---

### 4. Scoped Settings

#### `GET /scoped-settings/{id}`

**Description**: Retrieve a specific scoped setting.

**Response Body**:

| Name     | Type    | Description                                                                     |
| -------- | ------- | ------------------------------------------------------------------------------- |
| name     | string  | the scoped setting name                                                         |
| scope    | object  | scope by path-value (path being the path to a context value using dot notation) |
| settings | object  | settings by key-value                                                           |
| priority | integer | the scoped setting priority                                                     |

#### `PUT /scoped-settings/{id}`

**Description**: Update a specific scoped setting.

**Request Body**:

| Name     | Type    | Required | Description                                                                     |
| -------- | ------- | -------- | ------------------------------------------------------------------------------- |
| name     | string  | true     | the scoped setting name                                                         |
| scope    | object  | true     | scope by path-value (path being the path to a context value using dot notation) |
| settings | object  | true     | settings by key-value                                                           |
| priority | integer | false    | the scoped setting priority                                                     |

#### `DELETE /scoped-settings/{id}`

**Description**: Delete a specific scoped setting.

#### `GET /scoped-settings/{id}/localized-settings`

**Description**: Get a list of localized settings for a specific scoped setting.

**Response Body**:

| Name      | Type    | Description                      |
| --------- | ------- | -------------------------------- |
| \*        | array   |                                  |
| \*.id     | integer | the localized setting identifier |
| \*.locale | string  | the localized setting locale     |

#### `POST /scoped-settings/{id}/localized-settings`

**Description**: Store localized settings for a specific scoped setting.

**Request Body**:

| Name     | Type   | Description                  |
| -------- | ------ | ---------------------------- |
| locale   | string | the localized setting locale |
| settings | object | settings by key-value        |

---

### 5. Localized Settings

#### `GET /localized-settings/{id}`

**Description**: Retrieve a specific localized setting.

**Response Body**:

| Name     | Type    | Description                      |
| -------- | ------- | -------------------------------- |
| id       | integer | the localized setting identifier |
| locale   | string  | the localized setting locale     |
| settings | object  | settings by key-value            |

#### `PUT /localized-settings/{id}`

**Description**: Update a specific localized setting.

**Request Body**:

| Name     | Type   | Description                  |
| -------- | ------ | ---------------------------- |
| locale   | string | the localized setting locale |
| settings | object | settings by key-value        |

#### `DELETE /localized-settings/{id}`

**Description**: Delete a specific localized setting.

---

### 6. Events

#### `GET /events`

**Description**: Get the list of allowed events.

#### `GET /events/{event}/schema`

**Description**: Retrieve the schema for a specific event.

**Response Body**:

| Name                 | Type    | Description                                      |
| -------------------- | ------- | ------------------------------------------------ |
| context_schema       | object  | schema that describe all context values          |
| translatable_context | array   | list of all context values that are translatable |
| allowed_actions      | array   | all actions that can be triggered by the event   |
| fakable              | boolean | determine whether the event can be faked         |

#### `GET /events/{event}/listeners`

**Description**: Get a list of listeners for a specific event.

**Response Body**:

| Name     | Type    | Description                                                                     |
| -------- | ------- | ------------------------------------------------------------------------------- |
| \*       | array   |                                                                                 |
| \*.id    | integer | the event listener identifier                                                   |
| \*.event | string  | the event that trigger event listener                                           |
| \*.name  | string  | the name of the event listener                                                  |
| \*.scope | object  | scope by path-value (path being the path to a context value using dot notation) |

#### `POST /events/{event}/listeners`

**Description**: Store a new listener for a specific event.

**Request Body**:

| Name  | Type   | Description                                                                     |
| ----- | ------ | ------------------------------------------------------------------------------- |
| name  | string | the name of the event listener                                                  |
| scope | object | scope by path-value (path being the path to a context value using dot notation) |

---

### 7. Event Listeners

#### `PUT /event-listeners/{id}`

**Description**: Update a specific event listener.

**Request Body**:

| Name  | Type   | Description                                                                     |
| ----- | ------ | ------------------------------------------------------------------------------- |
| scope | object | scope by path-value (path being the path to a context value using dot notation) |
| name  | string | the name of the event listener                                                  |

#### `DELETE /event-listeners/{id}`

**Description**: Delete a specific event listener.

#### `GET /event-listeners/{id}/actions`

**Description**: Get a list of actions associated with a specific event listener.

**Response Body**:

| Name    | Type    | Description                  |
| ------- | ------- | ---------------------------- |
| \*      | array   |                              |
| \*.id   | integer | the event action identifier  |
| \*.type | string  | the type of the event action |
| \*.name | string  | the name of the event action |

#### `POST /event-listeners/{id}/actions`

**Description**: Store a new action for a specific event listener.

**Request Body**:

| Name     | Type   | Required | Description                                            |
| -------- | ------ | -------- | ------------------------------------------------------ |
| type     | string | true     | the type of the event action                           |
| name     | string | true     | the name of the event action                           |
| settings | object | false    | settings by key-value (used to create default setting) |

---

### 8. Event Actions

#### `GET /event-actions/{id}`

**Description**: Retrieve details of a specific event action (including default settings).

**Response Body**:

| Name                     | Type    | Description                       |
| ------------------------ | ------- | --------------------------------- |
| id                       | integer | the event action identifier       |
| type                     | string  | the type of the event action      |
| name                     | string  | the name of the event action      |
| default_setting          | object  | the default setting of the action |
| default_setting.id       | integer | the default setting identifier    |
| default_setting.settings | object  | settings by key-value             |

#### `PUT /event-actions/{id}`

**Description**: Update a specific event action.

**Request Body**:

| Name | Type   | Description                  |
| ---- | ------ | ---------------------------- |
| name | string | the name of the event action |

#### `DELETE /event-actions/{id}`

**Description**: Delete a specific event action.

#### `GET /event-actions/{id}/scoped-settings`

**Description**: Get a list of scoped settings for a specific event action.

**Response Body**:

| Name    | Type    | Description                    |
| ------- | ------- | ------------------------------ |
| \*      | array   |                                |
| \*.id   | integer | the scoped setting identifier  |
| \*.name | string  | the name of the scoped setting |

#### `POST /event-actions/{id}/default-settings`

**Description**: Store default settings for a specific event action.

**Request Body**:

| Name     | Type   | Description           |
| -------- | ------ | --------------------- |
| settings | object | settings by key-value |

#### `POST /event-actions/{id}/scoped-settings`

**Description**: Store scoped settings for a specific event action.

**Request Body**:

| Name     | Type    | Required | Description                                                                     |
| -------- | ------- | -------- | ------------------------------------------------------------------------------- |
| name     | string  | true     | the scoped setting name                                                         |
| scope    | object  | true     | scope by path-value (path being the path to a context value using dot notation) |
| settings | object  | true     | settings by key-value                                                           |
| priority | integer | false    | the scoped setting priority                                                     |

#### `POST /event-actions/{id}/simulate`

**Description**: Simulate an action handling. Typically, it allows you to verify that the settings are properly defined.

**Request Body**:

| Name               | Type   | Required | Description                                                                              |
| ------------------ | ------ | -------- | ---------------------------------------------------------------------------------------- |
| settings           | object | false    | settings by key-value                                                                    |
| localized_settings | object | false    | localized_settings by key-value                                                          |
| locale             | string | false    | the locale of the given localized_settings                                               |
| states             | array  | false    | to kown how to simulate with states see [states](Action-Simulation#simulate-with-states) |

if settings and/or localized settings are not sent, stored settings and/or localized settings will be used.

**Response Body**:

| Name    | Type    | Description                                                                    |
| ------- | ------- | ------------------------------------------------------------------------------ |
| success | boolean | true if simulation is successful                                               |
| result  | object  | the return of the simulation                                                   |
| message | string  | an error message if the simulation failed (only if error thrown is renderable) |
| trace   | array   | the stack trace if the simulation failed (only if the app is in debug mode)    |
