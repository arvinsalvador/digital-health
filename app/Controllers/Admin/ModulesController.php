<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use App\Services\ModuleService;
use ZipArchive;

class ModulesController extends BaseController
{
    /**
     * Only these roles can manage modules.
     */
    private array $allowedRoles = ['super_admin', 'admin', 'staff'];

    public function index()
    {
        $actor = $this->actor();

        if (! $this->isAllowed($actor)) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'Access denied. You are not allowed to manage modules.');
        }

        return view('admin/settings/modules/index', [
            'modules' => (new ModuleModel())->orderBy('name', 'ASC')->findAll(),
            'pageTitle' => 'Modules',
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function upload()
    {
        $actor = $this->actor();
        if (! $this->isAllowed($actor)) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'Access denied.');
        }

        $file = $this->request->getFile('module_zip');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Invalid upload.');
        }

        $ext = strtolower((string)$file->getClientExtension());
        if ($ext !== 'zip') {
            return redirect()->back()->with('error', 'Please upload a ZIP file.');
        }

        $tmp = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(dirname($tmp), basename($tmp));

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return redirect()->back()->with('error', 'Unable to open ZIP file.');
        }

        // Extract to temp folder
        $extract = WRITEPATH . 'modules/tmp_' . time();
        if (!is_dir($extract)) {
            mkdir($extract, 0777, true);
        }

        $zip->extractTo($extract);
        $zip->close();

        // Support ZIPs that contain a single folder (common)
        $moduleRoot = $this->detectModuleRoot($extract);
        if (! $moduleRoot) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return redirect()->back()->with('error', 'module.json missing. Please zip the module root folder correctly.');
        }

        $manifestPath = $moduleRoot . '/module.json';
        $data = json_decode((string)file_get_contents($manifestPath), true);

        if (!is_array($data) || empty($data['slug']) || empty($data['name']) || empty($data['version'])) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return redirect()->back()->with('error', 'Invalid module.json (missing required fields).');
        }

        $slug = trim((string)$data['slug']);

        // Slug safety
        if (!preg_match('/^[a-z0-9\-_]+$/i', $slug)) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return redirect()->back()->with('error', 'Invalid module slug. Use letters/numbers/-/_.');
        }

        $finalPath = WRITEPATH . "modules/{$slug}";

        if (is_dir($finalPath)) {
            $this->safeDeleteDirectory($extract);
            @unlink($tmp);
            return redirect()->back()->with('error', "Module '{$slug}' already exists.");
        }

        // Move module root into final folder
        // If moduleRoot is inside extract, move only that folder contents into finalPath
        if ($moduleRoot !== $extract) {
            // Create finalPath and move contents
            mkdir($finalPath, 0777, true);
            $this->recursiveCopy($moduleRoot, $finalPath);
            $this->safeDeleteDirectory($extract);
        } else {
            // Extract folder itself is module root; rename into finalPath
            rename($extract, $finalPath);
        }

        $canDisable = (int)($data['flags']['can_disable'] ?? 1);
        $canDelete  = (int)($data['flags']['can_delete'] ?? 1);

        // Protected modules (cannot disable) => enabled by default
        $enabled = $canDisable ? 0 : 1;

        (new ModuleModel())->save([
            'slug'        => $slug,
            'name'        => (string)$data['name'],
            'version'     => (string)$data['version'],
            'description' => (string)($data['description'] ?? ''),
            'author'      => (string)($data['author'] ?? ''),
            'menu_json'   => json_encode($data['menu'] ?? []),
            'can_disable' => $canDisable,
            'can_delete'  => $canDelete,
            'enabled'     => $enabled,
            'installed_at'=> date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Publish module assets if it provides /public
        $this->publishAssets($slug);

        // Hook: install
        service('moduleBootstrap')->runHook($slug, 'install');

        // Rebuild enabled cache
        (new ModuleService())->rebuildEnabledCache();

        @unlink($tmp);

        return redirect()->back()->with('success', 'Module installed successfully.');
    }

    public function enable($slug)
    {
        $actor = $this->actor();
        if (! $this->isAllowed($actor)) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $model = new ModuleModel();
        $module = $model->where('slug', (string)$slug)->first();

        if (! $module) {
            return redirect()->back()->with('error', 'Module not found.');
        }

        $model->update((int)$module['id'], [
            'enabled' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        service('moduleBootstrap')->runHook((string)$slug, 'enable');
        (new ModuleService())->rebuildEnabledCache();

        return redirect()->back()->with('success', 'Module enabled.');
    }

    public function disable($slug)
    {
        $actor = $this->actor();
        if (! $this->isAllowed($actor)) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $model  = new ModuleModel();
        $module = $model->where('slug', (string)$slug)->first();

        if (! $module) {
            return redirect()->back()->with('error', 'Module not found.');
        }

        if (!(int)$module['can_disable']) {
            return redirect()->back()->with('error', 'Module is protected (cannot be disabled).');
        }

        $model->update((int)$module['id'], [
            'enabled' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        service('moduleBootstrap')->runHook((string)$slug, 'disable');
        (new ModuleService())->rebuildEnabledCache();

        return redirect()->back()->with('success', 'Module disabled.');
    }

    public function delete($slug)
    {
        $actor = $this->actor();
        if (! $this->isAllowed($actor)) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $model  = new ModuleModel();
        $module = $model->where('slug', (string)$slug)->first();

        if (! $module) {
            return redirect()->back()->with('error', 'Module not found.');
        }

        if (!(int)$module['can_delete']) {
            return redirect()->back()->with('error', 'Module is protected (cannot be removed).');
        }

        // Hook: uninstall before removing files
        service('moduleBootstrap')->runHook((string)$slug, 'uninstall');

        $service = new ModuleService();

        // Remove module code
        $service->deleteDirectory(WRITEPATH . "modules/{$slug}");

        // Remove published assets
        $service->deleteDirectory(FCPATH . "modules/{$slug}");

        // Remove DB record
        $model->delete((int)$module['id']);

        $service->rebuildEnabledCache();

        return redirect()->back()->with('success', 'Module removed.');
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function isAllowed(array $actor): bool
    {
        $type = (string)($actor['user_type'] ?? '');
        return in_array($type, $this->allowedRoles, true);
    }

    /**
     * Determines where module.json is located after extraction.
     * Supports:
     * - module.json at extract root
     * - single folder inside extract containing module.json
     */
    private function detectModuleRoot(string $extractPath): ?string
    {
        if (is_file($extractPath . '/module.json')) {
            return $extractPath;
        }

        $items = array_values(array_filter(scandir($extractPath), function ($x) {
            return $x !== '.' && $x !== '..';
        }));

        if (count($items) === 1) {
            $maybeDir = $extractPath . '/' . $items[0];
            if (is_dir($maybeDir) && is_file($maybeDir . '/module.json')) {
                return $maybeDir;
            }
        }

        return null;
    }

    /**
     * Publish module assets:
     * writable/modules/<slug>/public/* -> public/modules/<slug>/*
     */
    private function publishAssets(string $slug): void
    {
        $src = WRITEPATH . "modules/{$slug}/public";
        $dst = FCPATH . "modules/{$slug}";

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
                @copy($srcPath, $dstPath);
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