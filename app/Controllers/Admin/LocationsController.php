<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminAreaModel;

class LocationsController extends BaseController
{
    /**
     * Only these roles can manage locations.
     */
    private array $allowedRoles = ['super_admin', 'admin', 'staff'];

    public function index()
    {
        $actor = $this->actor();

        if (! $this->isAllowed($actor)) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'Access denied. You are not allowed to manage locations.');
        }

        return view('admin/settings/locations/index', [
            'pageTitle' => 'Locations',
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    /**
     * GET /admin/settings/locations/list?level=1&parent=PCODE
     * Returns: [{pcode,name,level,parent_pcode,is_active}, ...]
     */
    public function list()
    {
        $actor = $this->actor();

        if (! $this->isAllowed($actor)) {
            return $this->response->setStatusCode(403)->setJSON([
                'message' => 'Access denied.',
            ]);
        }

        $level  = (int)($this->request->getGet('level') ?? 1);
        $parent = (string)($this->request->getGet('parent') ?? '');

        // Safety: normalize levels
        if ($level < 1) $level = 1;
        if ($level > 4) $level = 4;

        $model = new AdminAreaModel();
        $rows  = $model->listByLevel($level, $parent ?: null);

        // Return only what your UI needs
        $out = array_map(static fn($r) => [
            'pcode'        => $r['pcode'],
            'name'         => $r['name'],
            'level'        => (int)$r['level'],
            'parent_pcode' => $r['parent_pcode'],
            'is_active'    => (int)$r['is_active'],
        ], $rows);

        return $this->response->setJSON($out);
    }

    /**
     * POST /admin/settings/locations/toggle/{pcode}
     */
    public function toggle($pcode)
    {
        $actor = $this->actor();

        if (! $this->isAllowed($actor)) {
            return $this->response->setStatusCode(403)->setJSON([
                'message' => 'Access denied.',
            ]);
        }

        $pcode = trim((string)$pcode);
        if ($pcode === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Invalid pcode.',
            ]);
        }

        $model = new AdminAreaModel();
        $row   = $model->where('pcode', $pcode)->first();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'message' => 'Not found.',
            ]);
        }

        $new = ((int)$row['is_active'] === 1) ? 0 : 1;
        $model->update((int)$row['id'], ['is_active' => $new]);

        return $this->response->setJSON([
            'ok' => true,
            'is_active' => $new,
        ]);
    }

    /**
     * POST /admin/settings/locations/rename/{pcode}
     * Body: name=New Name
     */
    public function rename($pcode)
    {
        $actor = $this->actor();

        if (! $this->isAllowed($actor)) {
            return $this->response->setStatusCode(403)->setJSON([
                'message' => 'Access denied.',
            ]);
        }

        $pcode = trim((string)$pcode);
        if ($pcode === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Invalid pcode.',
            ]);
        }

        $name = trim((string)$this->request->getPost('name'));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Name is required.',
            ]);
        }

        $model = new AdminAreaModel();
        $row   = $model->where('pcode', $pcode)->first();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'message' => 'Not found.',
            ]);
        }

        $model->update((int)$row['id'], ['name' => $name]);

        return $this->response->setJSON([
            'ok' => true,
        ]);
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function isAllowed(array $actor): bool
    {
        $type = (string)($actor['user_type'] ?? '');
        return in_array($type, $this->allowedRoles, true);
    }
}