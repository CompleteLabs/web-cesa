<?php

namespace Cesa\Contact;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ContactPlugin implements Plugin
{
    public function getId(): string
    {
        return 'contact';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: $this->getPluginBasePath('/Filament/Resources'), 
            for: 'Cesa\\Contact\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void
    {
        // Boot logic here
    }

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new \ReflectionClass(get_class($this));
        return dirname($reflector->getFileName()).($path ?? '');
    }
}
