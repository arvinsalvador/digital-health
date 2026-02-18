<?php

namespace App\Services;

use App\Models\ModuleModel;

class ModuleService
{
    protected $path;

    public function __construct()
    {
        $this->path = WRITEPATH.'modules/';
    }

    public function rebuildEnabledCache()
    {
        $model = new ModuleModel();

        $modules = $model
            ->groupStart()
                ->where('enabled',1)
                ->orWhere('can_disable',0)
            ->groupEnd()
            ->findAll();

        $slugs = array_column($modules,'slug');

        file_put_contents(
            WRITEPATH.'modules/enabled.php',
            "<?php return ".var_export($slugs,true).";"
        );
    }

    public function deleteDirectory($dir)
    {
        if (!is_dir($dir)) return;

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;

            $path = $dir.'/'.$item;

            is_dir($path)
                ? $this->deleteDirectory($path)
                : unlink($path);
        }

        rmdir($dir);
    }
}
