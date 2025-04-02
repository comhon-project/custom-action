<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Commands\GenerateActionCommand;
use Comhon\CustomAction\Context\ContextFinder;
use Comhon\CustomAction\Context\ContextScoper;
use Comhon\CustomAction\Context\ContextValidator;
use Comhon\CustomAction\Contracts\ContextFinderInterface;
use Comhon\CustomAction\Contracts\ContextScoperInterface;
use Comhon\CustomAction\Contracts\ContextValidatorInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver as FacadesCustomActionModelResolver;
use Comhon\CustomAction\Files\StoredFileInterface;
use Comhon\CustomAction\Listeners\EventActionDispatcher;
use Comhon\CustomAction\Listeners\QueuedEventActionDispatcher;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Comhon\CustomAction\Resolver\ModelResolver;
use Comhon\CustomAction\Rules\HtmlTemplate;
use Comhon\CustomAction\Rules\IsInstanceOf;
use Comhon\CustomAction\Rules\ModelReference;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\CustomAction\Rules\TextTemplate;
use Comhon\ModelResolverContract\ModelResolverInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomActionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-custom-action')
            ->hasConfigFile()
            ->hasMigration('create_laravel-custom-action_table')
            ->hasRoute('routes')
            ->hasConsoleCommand(GenerateActionCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(CustomActionModelResolver::class);
        $this->app->singletonIf(ModelResolverInterface::class, ModelResolver::class);
        $this->app->singletonIf(ContextFinderInterface::class, ContextFinder::class);
        $this->app->singletonIf(ContextValidatorInterface::class, ContextValidator::class);
        $this->app->singletonIf(ContextScoperInterface::class, ContextScoper::class);
    }

    public function packageBooted()
    {
        $eventActionDispatcher = config('custom-action.event_action_dispatcher.should_queue')
            ? QueuedEventActionDispatcher::class
            : EventActionDispatcher::class;
        Event::listen(CustomEventInterface::class, [$eventActionDispatcher, 'handle']);

        $this->registerPolicies();
        $this->registerRules();
        $this->registerMorphMapModels();
        $this->bindModels();
        $this->publishFiles();
    }

    public function registerPolicies()
    {
        if (config('custom-action.use_policies')) {
            $policies = Gate::policies();
            if (! isset($policies[CustomEventInterface::class])) {
                Gate::policy(CustomEventInterface::class, 'App\Policies\CustomAction\EventPolicy');
            }
            if (! isset($policies[EventListener::class])) {
                Gate::policy(EventListener::class, 'App\Policies\CustomAction\EventListenerPolicy');
            }
            if (! isset($policies[CustomActionInterface::class])) {
                Gate::policy(CustomActionInterface::class, 'App\Policies\CustomAction\ActionPolicy');
            }
            if (! isset($policies[EventAction::class])) {
                Gate::policy(EventAction::class, 'App\Policies\CustomAction\EventActionPolicy');
            }
            if (! isset($policies[ManualAction::class])) {
                Gate::policy(ManualAction::class, 'App\Policies\CustomAction\ManualActionPolicy');
            }
            if (! isset($policies[DefaultSetting::class])) {
                Gate::policy(DefaultSetting::class, 'App\Policies\CustomAction\DefaultSettingPolicy');
            }
            if (! isset($policies[ScopedSetting::class])) {
                Gate::policy(ScopedSetting::class, 'App\Policies\CustomAction\ScopedSettingPolicy');
            }
            if (! isset($policies[LocalizedSetting::class])) {
                Gate::policy(LocalizedSetting::class, 'App\Policies\CustomAction\LocalizedSettingPolicy');
            }
        }
    }

    public function registerRules()
    {
        Validator::extend(RuleHelper::getRuleName('model_reference'), ModelReference::class);
        Validator::extend(RuleHelper::getRuleName('is'), IsInstanceOf::class);
        Validator::extend(RuleHelper::getRuleName('text_template'), TextTemplate::class);
        Validator::extend(RuleHelper::getRuleName('html_template'), HtmlTemplate::class);
    }

    public function registerMorphMapModels()
    {
        Relation::morphMap([
            'default-setting' => DefaultSetting::class,
            'scoped-setting' => ScopedSetting::class,
            'event-action' => EventAction::class,
            'manual-action' => ManualAction::class,
        ]);
    }

    public function bindModels()
    {
        FacadesCustomActionModelResolver::bind('stored-file', StoredFileInterface::class);
        FacadesCustomActionModelResolver::bind('mailable-entity', MailableEntityInterface::class);
        FacadesCustomActionModelResolver::bind('custom-event', CustomEventInterface::class);
    }

    public function publishFiles()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).DIRECTORY_SEPARATOR.'policies' => app_path('Policies'.DIRECTORY_SEPARATOR.'CustomAction'),
            ], 'custom-action-policies');
        }
    }
}
