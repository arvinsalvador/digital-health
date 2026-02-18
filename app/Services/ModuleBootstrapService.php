<?php

namespace App\Services;

use App\Contracts\ModuleBootstrapInterface;
use App\Models\ModuleModel;

class ModuleBootstrapService
{
    private static bool $booted = false;

    public function bootEnabled(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        $model = new ModuleModel();

        // Enabled + protected modules (cannot disable)
        $modules = $model
            ->groupStart()
                ->where('enabled', 1)
                ->orWhere('can_disable', 0)
            ->groupEnd()
            ->findAll();

        foreach ($modules as $m) {
            $manifestPath = WRITEPATH . "modules/{$m['slug']}/module.json";
            if (!is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $this->runBoot($m['slug'], $manifest);
        }
    }

    public function runHook(string $slug, string $hook): void
    {
        $manifestPath = WRITEPATH . "modules/{$slug}/module.json";
        if (!is_file($manifestPath)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
        $instance = $this->loadBootstrapInstance($slug, $manifest);

        if (!$instance) {
            return;
        }

        if (method_exists($instance, $hook)) {
            $instance->{$hook}();
        }
    }

    private function runBoot(string $slug, array $manifest): void
    {
        $instance = $this->loadBootstrapInstance($slug, $manifest);
        if (!$instance) {
            return;
        }

        if (method_exists($instance, 'boot')) {
            $instance->boot();
        }
    }

    private function loadBootstrapInstance(string $slug, array $manifest): ?object
    {
        if (empty($manifest['bootstrap']['file'])) {
            return null;
        }

        $file = $manifest['bootstrap']['file'];
        $class = $manifest['bootstrap']['class'] ?? 'Bootstrap';

        $bootstrapPath = WRITEPATH . "modules/{$slug}/{$file}";
        if (!is_file($bootstrapPath)) {
            return null;
        }

        require_once $bootstrapPath;

        // Convention: Modules\<StudlySlug>\Bootstrap (or custom class name)
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
        $fqcn = "Modules\\{$studly}\\{$class}";

        // Allow fully-qualified class in manifest
        if (str_contains($class, '\\')) {
            $fqcn = $class;
        }

        if (!class_exists($fqcn)) {
            return null;
        }

        $instance = new $fqcn();

        // Optional: enforce interface (recommended)
        // if (!($instance instanceof ModuleBootstrapInterface)) return null;

        return $instance;
    }
}
