# Built-in Actions

The custom action library come with some built-in actions.

## AbstractSendEmail

Actually, `AbstractSendEmail` is not directly usable (since it’s abstract), but it serves as a useful skeleton for building actions that send emails. When extending from this class, you just need to implement functions to define the sender, recipients, subject, body, and attachments. You don’t need to implement the rest : 
-   settings selection (default setting or scoped setting)
-   localized settings selection (according recipient preferred locale)
-   variable substitution according context in the subject and in the body (using [twig](https://twig.symfony.com/) by default)
-   and the actual email sending.

**Note:** When extending `AbstractSendEmail`, to expose the [context](Context.md), instead of implementing `getContextSchema`, You must implements `getCommonContextSchema`.

**Note:** This action is [simulatable](Action-Simulation.md). It implement the `SimulatableInterface` and implements the `simulate` method. Thus, all actions that extend this class benefit from its simulation mechanism. You just need to implement context faking in order to execute a simulation. The simulation doesn't send an email but returns a JSON response through the API, containing the email subject and body with variable substitution applied. This allows the API consumer to preview the email content before saving changes.

## AbstractSendManualEmail
(extends AbstractSendEmail)

Actually, `AbstractSendManualEmail` is not directly usable (since it’s abstract), but it serves as a useful skeleton for building very generic manual actions that send emails. When extending from this class, you just need to define the [context](Context.md). You don’t need to implement the rest. It is fully customizable through the Customization API. To expose the [context](Context.md), instead of implementing `getContextSchema`, You must implements `getCommonContextSchema`.

When extending from this class, you can generate a preview of the email body by using the function `simulate`:
```php
$preview = (new SendUserRegisteredEmail($user))->simulate();
```

And you can override email settings to inject exactly what you want in the email. You just have to define `from`, `subject`, `body` in your class as public or protected attributes and set them if you want (typically inside your constructor).

```php
$from = 'foo@bar.com';
$subject = 'overridden subject';
$body = 'overridden body';
SendUserRegisteredEmail::dispatch($user, $from, $subject, $body);
```

Preview generation and settings overriding allow you to fine-tune each email before sending.

Note: If you use your own [Model Resolver](Library-Core#model-resolver), make sure following classes are registered : 

-   `stored-file` => `StoredFileInterface::class`
-   `mailable-entity` => `MailableEntityInterface::class`

## SendAutomaticEmail
(extends AbstractSendEmail)

`SendAutomaticEmail` is a very generic action that should be triggered from events to send email. It is fully customizable through the Customization API.

Note: If you use your own [Model Resolver](Library-Core#model-resolver), make sure following classes are registered : 

-   `stored-file` => `StoredFileInterface::class`
-   `mailable-entity` => `MailableEntityInterface::class`

## QueueAutomaticEmail
(extends AbstractSendEmail)

`QueueAutomaticEmail` is the same action as `SendAutomaticEmail` except that the action is dispatched via [queues](https://laravel.com/docs/master/queues).