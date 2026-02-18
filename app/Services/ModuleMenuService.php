<?php

namespace App\Services;

use App\Models\ModuleModel;

class ModuleMenuService
{
    public function getMenus()
    {
        $modules = (new ModuleModel())
            ->groupStart()
                ->where('enabled',1)
                ->orWhere('can_disable',0)
            ->groupEnd()
            ->findAll();

        $menus = [
            'primary'=>[],
            'settings'=>[]
        ];

        foreach ($modules as $m) {

            $items = json_decode($m['menu_json'],true);

            if (!$items) continue;

            foreach ($items as $menu) {
                $menus[$menu['type']][] = $menu;
            }
        }

        return $menus;
    }
}
