<?php

namespace Comhon\CustomAction\Bindings;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;

class EventBindingsContainer implements BindingsContainerInterface
{
    public function __construct(private HasBindingsInterface $event) {}

    public function getBindingValues(?string $locale = null): array
    {
        return $this->event->getBindingValues($locale);
    }

    public function getBindingSchema(): ?array
    {
        return $this->event->getBindingSchema();
    }

    public function getEvent(): HasBindingsInterface
    {
        return $this->event;
    }
}
