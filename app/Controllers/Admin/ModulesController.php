<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use App\Services\ModuleService;
use ZipArchive;

class ModulesController extends BaseController
{
    public function index()
    {
        return view('admin/settings/modules/index', [
            'modules'   => (new ModuleModel())->findAll(),
            'pageTitle' => 'Modules',
        ]);
    }

    public function upload()
    {
        $file = $this->request->getFile('module_zip');

        if (!$file || !$file->isValid()) {
            return back()->with('error', 'Invalid upload');
        }

        // Basic validation (zip only)
        $ext = strtolower($file->getClientExtension());
        if ($ext !== 'zip') {
            return back()->with('error', 'Please upload a ZIP file.');
        }

        $tmp = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(dirname($tmp), basename($tmp));

        $zip = new ZipArchive();

        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return back()->with('error', 'Unable to open ZIP file.');
        }

        // Extract to temp folder
        $extract = WRITEPATH . 'modules/tmp_' . time();
        mkdir($extract, 0777, true);

        $zip->extractTo($extract);
        $zip->close();

        $manifest = $extract . '/module.json';
        if (!file_exists($manifest)) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return back()->with('error', 'module.json missing');
        }

        $data = json_decode(file_get_contents($manifest), true);
        if (!is_array($data) || empty($data['slug']) || empty($data['name']) || empty($data['version'])) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return back()->with('error', 'Invalid module.json (missing required fields).');
        }

        $slug = trim($data['slug']);

        // Slug safety (letters, numbers, dash, underscore)
        if (!preg_match('/^[a-z0-9\-_]+$/i', $slug)) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return back()->with('error', 'Invalid module slug. Use letters/numbers/-/_.');
        }

        $finalPath = WRITEPATH . "modules/$slug";

        // Prevent overwrite (later we can implement "replace module" flow)
        if (is_dir($finalPath)) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return back()->with('error', "Module '$slug' already exists.");
        }

        // Move extracted module into final folder
        rename($extract, $finalPath);

        // Save module record
        $canDisable = (int)($data['flags']['can_disable'] ?? 1);
        $canDelete  = (int)($data['flags']['can_delete'] ?? 1);

        (new ModuleModel())->save([
            'slug'        => $slug,
            'name'        => $data['name'],
            'version'     => $data['version'],
            'description' => $data['description'] ?? '',
            'author'      => $data['author'] ?? '',
            'menu_json'   => json_encode($data['menu'] ?? []),
            'can_disable' => $canDisable,
            'can_delete'  => $canDelete,
            // Protected modules (cannot disable) should be enabled by default
            'enabled'     => $canDisable ? 0 : 1,
            'installed_at'=> date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // OPTIONAL: publish module assets if module provides a /public directory
        // writable/modules/<slug>/public/*  -> public/modules/<slug>/*
        $this->publishAssets($slug);

        // HOOK: after install
        service('moduleBootstrap')->runHook($slug, 'install');

        // Rebuild enabled routes cache (also includes protected modules)
        service(ModuleService::class)->rebuildEnabledCache();

        @unlink($tmp);

        return back()->with('success', 'Module installed');
    }

    public function enable($slug)
    {
        (new ModuleModel())->update(['slug' => $slug], ['enabled' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        // HOOK: enable
        service('moduleBootstrap')->runHook($slug, 'enable');

        service(ModuleService::class)->rebuildEnabledCache();
        return back()->with('success', 'Module enabled');
    }

    public function disable($slug)
    {
        $model  = new ModuleModel();
        $module = $model->where('slug', $slug)->first();

        if (!$module) {
            return back()->with('error', 'Module not found');
        }

        if (!(int)$module['can_disable']) {
            return back()->with('error', 'Module protected (cannot be disabled)');
        }

        $model->update($module['id'], ['enabled' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        // HOOK: disable
        service('moduleBootstrap')->runHook($slug, 'disable');

        service(ModuleService::class)->rebuildEnabledCache();
        return back()->with('success', 'Module disabled');
    }

    public function delete($slug)
    {
        $model  = new ModuleModel();
        $module = $model->where('slug', $slug)->first();

        if (!$module) {
            return back()->with('error', 'Module not found');
        }

        if (!(int)$module['can_delete']) {
            return back()->with('error', 'Module protected (cannot be removed)');
        }

        // HOOK: uninstall (must happen BEFORE deleting files)
        service('moduleBootstrap')->runHook($slug, 'uninstall');

        $service = service(ModuleService::class);

        // Remove module code
        $service->deleteDirectory(WRITEPATH . "modules/$slug");

        // Remove published assets
        $service->deleteDirectory(FCPATH . "modules/$slug");

        // Remove DB record
        $model->delete($module['id']);

        $service->rebuildEnabledCache();

        return back()->with('success', 'Module removed');
    }

    /**
     * Publish module assets:
     *   writable/modules/<slug>/public/* -> public/modules/<slug>/*
     */
    private function publishAssets(string $slug): void
    {
        $src = WRITEPATH . "modules/$slug/public";
        $dst = FCPATH . "modules/$slug";

        if (!is_dir($src)) {
            return;
        }

        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $this->recursiveCopy($src, $dst);
    }

    private function recursiveCopy(string $src, string $dst): void
    {
        $dir = opendir($src);
        if (!$dir) return;

        @mkdir($dst, 0777, true);

        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') continue;

            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcPath)) {
                $this->recursiveCopy($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }

        closedir($dir);
    }

    private function safeDeleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->safeDeleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
