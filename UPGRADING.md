# Upgrading

## To template-renderer 2.x

custom-action now requires `comhon-project/template-renderer` 2.x.
If your application uses template-renderer directly (e.g. `Template::render()` with a locale and timezones, `Template::getRenderer()`, custom drivers registered with `Template::extend()`),
follow the [template-renderer upgrade guide](https://github.com/comhon-project/template-renderer/blob/main/UPGRADING.md).

### Custom mailable

If you instantiate `Comhon\CustomAction\Actions\Email\Mailable\Custom` yourself, the locale and timezones are now given with a `RenderOptions` object,
and the replacements can also be a closure that receives the render context:

```php
use Comhon\TemplateRenderer\RenderOptions;

// before
new Custom($mail, $replacements, 'fr', 'Europe/Paris', 'Asia/Tokyo');

// after
new Custom($mail, $replacements, new RenderOptions('fr', 'Europe/Paris', 'Asia/Tokyo'));
```

### Translatable context in emails

In emails (actions extending `AbstractSendEmail`), the translatable context is now applied at render time:

- When the action is triggered from an event, the translatable context of the event and the one of the action are applied to the merged context.
  Before, each one was only applied to its own context before the merge: a translatable key declared by the event is now also translatable when the action overrides its value,
  and a translatable key declared by the action now also applies to a value exposed by the event.
- `getTranslatableContext()` is called statically, without argument.

Templates are unchanged: `{{ value.translate() }}` still gives the translated value.

### Queued emails

Emails queued before the upgrade can't be rendered after it: let your queues be processed before deploying.

Queued emails now contain a closure signed with your application key: if you change `APP_KEY`, emails queued before the change can't be sent.