<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminAreaModel;

class LocationsController extends BaseController
{
    public function index()
    {
        return view('admin/settings/locations/index', [
            'pageTitle' => 'Locations'
        ]);
    }

    public function list()
    {
        $level  = (int)($this->request->getGet('level') ?? 1);
        $parent = (string)($this->request->getGet('parent') ?? '');

        $model = new AdminAreaModel();
        $rows = $model->listByLevel($level, $parent ?: null);

        // return only what you said you need
        $out = array_map(fn($r) => [
            'pcode' => $r['pcode'],
            'name'  => $r['name'],
            'level' => (int)$r['level'],
            'parent_pcode' => $r['parent_pcode'],
            'is_active' => (int)$r['is_active'],
        ], $rows);

        return $this->response->setJSON($out);
    }

    public function toggle($pcode)
    {
        $model = new AdminAreaModel();
        $row = $model->where('pcode', $pcode)->first();

        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON(['message'=>'Not found']);
        }

        $new = $row['is_active'] ? 0 : 1;
        $model->update($row['id'], ['is_active' => $new]);

        return $this->response->setJSON(['ok'=>true,'is_active'=>$new]);
    }

    public function rename($pcode)
    {
        $name = trim((string)$this->request->getPost('name'));

        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message'=>'Name is required']);
        }

        $model = new AdminAreaModel();
        $row = $model->where('pcode', $pcode)->first();

        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON(['message'=>'Not found']);
        }

        $model->update($row['id'], ['name' => $name]);

        return $this->response->setJSON(['ok'=>true]);
    }
}
