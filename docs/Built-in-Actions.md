# Built-in Actions

The custom action library comes with some built-in actions.

## AbstractSendEmail

`AbstractSendEmail` is not directly usable (since it’s abstract), but it serves as a useful skeleton for building actions that send emails. When extending from this class, you just need to implement functions to define the sender, recipients, subject, body, and attachments. You don’t need to implement the rest:
-   settings selection (default setting or scoped setting)
-   localized settings selection (according to the recipient's preferred locale)
-   variable substitution in the subject and the body according to the context (using [twig](https://twig.symfony.com/) by default), values depending on the locale or the timezone of the email can be built with a [renderable context](Context.md#renderable-context)
-   and the actual email sending.

**Note:** When extending `AbstractSendEmail`, to expose the [context](Context.md), instead of implementing `getContextSchema`, you must implement `getCommonContextSchema`.

**Note:** This action is [simulatable](Action-Simulation.md). It implements the `SimulatableInterface` and its `simulate` method. Thus, all actions that extend this class benefit from its simulation mechanism. You just need to implement context faking in order to execute a simulation. The simulation doesn't send an email but returns a JSON response through the API, containing the email subject and body with variable substitution applied. This allows the API consumer to preview the email content before saving changes.

!> **Warning:** Queued emails contain a closure signed with your application key: if you change `APP_KEY`, emails queued before the change can't be sent. Let your queues be processed before changing it.

### Send emails after the database transaction is committed

When the action is executed inside a database transaction, you may want to build the emails inside the transaction, so a failure while building them rolls back the transaction, but send them only once it is committed, so the transaction isn't kept open while emails are sent. To do so, call `sendAfterCommit`:

```php
DB::transaction(function () use ($user) {
    // ...
    SendUserRegisteredEmail::dispatch($user)->sendAfterCommit();
});
```

To always send emails after commit, set the `sendAfterCommit` property in your class:

```php
class SendCompanyCreatedEmail extends SendAutomaticEmail
{
    protected $sendAfterCommit = true;
}
```

-   Emails sent synchronously are fully built immediately: context validated, localized settings selected, subject and body rendered (including [renderable](Context.md#renderable-context) and translatable context), attachments resolved (local files checked, other contents like storage files loaded) and addresses validated. Only the sending is deferred.
-   Emails sent asynchronously (`$sendAsynchronously` property) are rendered by the queue worker, so after the commit. Only their queuing is deferred.
-   If the transaction is rolled back, no email is sent, even the ones already built (e.g. if building the email of the third recipient fails).
-   If there is no transaction, emails are sent immediately, one by one.

**Note:** When an event is dispatched inside a database transaction, each synchronous action it triggers is executed in a savepoint (see [errors](Actions-Triggered-From-Events.md#errors)). So with `sendAfterCommit`, if building one of the emails of such an action fails, none of its emails is sent.

## AbstractSendManualEmail
(extends AbstractSendEmail)

`AbstractSendManualEmail` is not directly usable (since it’s abstract), but it serves as a useful skeleton for building very generic manual actions that send emails. When extending from this class, you just need to define the [context](Context.md). You don’t need to implement the rest. It is fully customizable through the Customization API. To expose the [context](Context.md), instead of implementing `getContextSchema`, you must implement `getCommonContextSchema`.

When extending from this class, you can generate a preview of the email body by using the `simulate` method:
```php
$preview = (new SendUserRegisteredEmail($user))->simulate();
```

And you can override email settings to inject exactly what you want in the email, with the methods `from`, `to`, `cc`, `bcc`, `subject`, `body`, `attachments` and `groupRecipients` (to send only one email to all recipients):

```php
SendUserRegisteredEmail::dispatch($user)
    ->from('foo@bar.com')
    ->subject('overridden subject')
    ->body('overridden body');
```

If your class doesn't define its own constructor, these values can also be given to the constructor of `AbstractSendManualEmail` (`to`, `cc`, `bcc`, `from`, `attachments`, `subject`, `body` and `grouped`):

```php
SendCustomEmail::dispatch(
    to: $user,
    from: 'foo@bar.com',
    subject: 'overridden subject',
    body: 'overridden body',
);
```

Preview generation and settings overriding allow you to fine-tune each email before sending.

Note: If you use your own [Model Resolver](Library-Core#model-resolver), make sure the following classes are registered:

-   `stored-file` => `StoredFileInterface::class`
-   `mailable-entity` => `MailableEntityInterface::class`

## SendAutomaticEmail
(extends AbstractSendEmail)

`SendAutomaticEmail` is a very generic action that should be triggered from events to send emails. It is fully customizable through the Customization API.

Note: If you use your own [Model Resolver](Library-Core#model-resolver), make sure the following classes are registered:

-   `stored-file` => `StoredFileInterface::class`
-   `mailable-entity` => `MailableEntityInterface::class`

## QueueAutomaticEmail
(extends AbstractSendEmail)

`QueueAutomaticEmail` is the same action as `SendAutomaticEmail` except that the action is dispatched via [queues](https://laravel.com/docs/master/queues).
