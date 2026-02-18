<?php

namespace App\Contracts;

interface ModuleBootstrapInterface
{
    public function boot(): void;

    // Lifecycle hooks (optional to implement, but recommended)
    public function install(): void;
    public function enable(): void;
    public function disable(): void;
    public function uninstall(): void;
}
