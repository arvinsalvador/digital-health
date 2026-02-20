<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AdminAreaModel; // from Locations module

class UsersController extends BaseController
{
    private array $barangayRoles = ['brgy_captain','brgy_secretary','bhw'];
    private array $municipalRoles = ['admin','staff'];

    public function index()
    {
        // Phase-1: Only Super Admin can access (we’ll expand later)
        // If you don't have auth yet, comment this out for now.
        // $this->requireSuperAdmin();

        $users = (new UserModel())->orderBy('created_at','DESC')->findAll();

        return view('admin/settings/users/index', [
            'pageTitle' => 'Users',
            'users' => $users
        ]);
    }

    public function create()
    {
        // $this->requireSuperAdmin();

        // Resolve Del Carmen defaults from admin_areas (if imported)
        $defaults = $this->resolveDelCarmenDefaults();

        return view('admin/settings/users/form', [
            'pageTitle' => 'Add User',
            'mode' => 'create',
            'user' => null,
            'defaults' => $defaults,
        ]);
    }

    public function store()
    {
        // $this->requireSuperAdmin();

        $data = $this->request->getPost();

        $rules = [
            'username' => 'required|min_length[4]|max_length[50]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[super_admin,admin,staff,brgy_captain,brgy_secretary,bhw]',
        ];

        if (!$this->validate($rules)) {
            return back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userType = $data['user_type'];

        // Municipality defaults (Del Carmen)
        $defaults = $this->resolveDelCarmenDefaults();
        $region = $defaults['region_pcode'] ?? null;
        $province = $defaults['province_pcode'] ?? null;
        $municipality = $defaults['municipality_pcode'] ?? null;

        $barangay = $data['barangay_pcode'] ?? null;

        // Barangay assignment required for barangay roles
        if (in_array($userType, $this->barangayRoles, true) && empty($barangay)) {
            return back()->withInput()->with('error', 'Barangay is required for barangay-level users.');
        }

        // Non-barangay roles should not be tied to barangay
        if (!in_array($userType, $this->barangayRoles, true)) {
            $barangay = null;
        }

        $model = new UserModel();
        $model->insert([
            'username' => trim($data['username']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => trim($data['email']),
            'first_name' => trim($data['first_name']),
            'middle_name' => trim($data['middle_name'] ?? '') ?: null,
            'last_name' => trim($data['last_name']),
            'contact_no' => trim($data['contact_no'] ?? '') ?: null,

            'address_line' => trim($data['address_line'] ?? '') ?: null,
            'postal_code' => trim($data['postal_code'] ?? '') ?: null,

            'region_pcode' => $region,
            'province_pcode' => $province,
            'municipality_pcode' => $municipality,
            'barangay_pcode' => $barangay,

            'user_type' => $userType,
            'status' => 1,
        ]);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User created.');
    }

    public function edit($id)
    {
        // $this->requireSuperAdmin();

        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        $defaults = $this->resolveDelCarmenDefaults();

        return view('admin/settings/users/form', [
            'pageTitle' => 'Edit User',
            'mode' => 'edit',
            'user' => $user,
            'defaults' => $defaults,
        ]);
    }

    public function update($id)
    {
        // $this->requireSuperAdmin();

        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        $data = $this->request->getPost();

        $rules = [
            'username' => "required|min_length[4]|max_length[50]|is_unique[users.username,id,{$id}]",
            'email'    => "required|valid_email|is_unique[users.email,id,{$id}]",
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[super_admin,admin,staff,brgy_captain,brgy_secretary,bhw]',
        ];

        if (!$this->validate($rules)) {
            return back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userType = $data['user_type'];
        $barangay = $data['barangay_pcode'] ?? null;

        if (in_array($userType, $this->barangayRoles, true) && empty($barangay)) {
            return back()->withInput()->with('error', 'Barangay is required for barangay-level users.');
        }

        if (!in_array($userType, $this->barangayRoles, true)) {
            $barangay = null;
        }

        $payload = [
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'first_name' => trim($data['first_name']),
            'middle_name' => trim($data['middle_name'] ?? '') ?: null,
            'last_name' => trim($data['last_name']),
            'contact_no' => trim($data['contact_no'] ?? '') ?: null,
            'address_line' => trim($data['address_line'] ?? '') ?: null,
            'postal_code' => trim($data['postal_code'] ?? '') ?: null,
            'user_type' => $userType,
            'barangay_pcode' => $barangay,
        ];

        // Optional password change
        if (!empty($data['password'])) {
            $payload['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $model->update($id, $payload);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User updated.');
    }

    public function toggle($id)
    {
        // $this->requireSuperAdmin();

        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        $new = ((int)$user['status'] === 1) ? 0 : 1;
        $model->update($id, ['status' => $new]);

        return redirect()->to(base_url('admin/settings/users'))->with('success', $new ? 'User enabled.' : 'User disabled.');
    }

    /**
     * Resolve Del Carmen defaults via admin_areas (Locations import required).
     * If the lookup fails, returns nulls (still allows create, but barangay list may be empty).
     */
    private function resolveDelCarmenDefaults(): array
    {
        $areas = new AdminAreaModel();

        // Municipality name match (from your dataset)
        $mun = $areas->where('level', 3)->where('name', 'Del Carmen')->first();
        if (!$mun) {
            return [
                'region_pcode' => null,
                'province_pcode' => null,
                'municipality_pcode' => null,
            ];
        }

        $province = $areas->where('pcode', $mun['parent_pcode'])->first();
        $region = $province ? $areas->where('pcode', $province['parent_pcode'])->first() : null;

        return [
            'region_pcode' => $region['pcode'] ?? null,
            'province_pcode' => $province['pcode'] ?? null,
            'municipality_pcode' => $mun['pcode'] ?? null,
        ];
    }

    // Placeholder for later auth enforcement
    // private function requireSuperAdmin(): void
    // {
    //     $u = session('user'); // up to your auth implementation
    //     if (!$u || ($u['user_type'] ?? '') !== 'super_admin') {
    //         throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    //     }
    // }
}