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
        return view('admin/settings/modules/index',[
            'modules' => (new ModuleModel())->findAll(),
            'pageTitle'=>'Modules'
        ]);
    }

    public function upload()
    {
        $file = $this->request->getFile('module_zip');

        if (!$file->isValid())
            return back()->with('error','Invalid upload');

        $tmp = WRITEPATH.'uploads/'.$file->getRandomName();
        $file->move(dirname($tmp), basename($tmp));

        $zip = new ZipArchive;
        $extract = WRITEPATH.'modules/tmp_'.time();
        mkdir($extract,0777,true);

        $zip->open($tmp);
        $zip->extractTo($extract);
        $zip->close();

        $manifest = $extract.'/module.json';

        if (!file_exists($manifest))
            return back()->with('error','module.json missing');

        $data = json_decode(file_get_contents($manifest),true);

        $slug = $data['slug'];

        rename($extract, WRITEPATH."modules/$slug");

        (new ModuleModel())->save([
            'slug'=>$slug,
            'name'=>$data['name'],
            'version'=>$data['version'],
            'description'=>$data['description'] ?? '',
            'author'=>$data['author'] ?? '',
            'menu_json'=>json_encode($data['menu'] ?? []),
            'can_disable'=>$data['flags']['can_disable'] ?? 1,
            'can_delete'=>$data['flags']['can_delete'] ?? 1,
            'enabled'=> ($data['flags']['can_disable'] ?? 1) ? 0 : 1,
            'installed_at'=>date('Y-m-d H:i:s')
        ]);

        service(ModuleService::class)->rebuildEnabledCache();

        return back()->with('success','Module installed');
    }

    public function enable($slug)
    {
        (new ModuleModel())->update(['slug'=>$slug],['enabled'=>1]);
        service(ModuleService::class)->rebuildEnabledCache();
        return back();
    }

    public function disable($slug)
    {
        $model = new ModuleModel();
        $module = $model->where('slug',$slug)->first();

        if (!$module['can_disable'])
            return back()->with('error','Module protected');

        $model->update($module['id'],['enabled'=>0]);

        service(ModuleService::class)->rebuildEnabledCache();

        return back();
    }

    public function delete($slug)
    {
        $model = new ModuleModel();
        $module = $model->where('slug',$slug)->first();

        if (!$module['can_delete'])
            return back()->with('error','Module protected');

        $service = service(ModuleService::class);

        $service->deleteDirectory(
            WRITEPATH."modules/$slug"
        );

        $service->deleteDirectory(
            FCPATH."modules/$slug"
        );

        $model->delete($module['id']);

        $service->rebuildEnabledCache();

        return back()->with('success','Module removed');
    }
}
