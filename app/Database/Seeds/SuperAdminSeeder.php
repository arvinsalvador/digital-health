<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Prevent duplicate seed
        $exists = $db->table('users')
            ->where('user_type', 'super_admin')
            ->countAllResults();

        if ($exists > 0) {
            echo "Super admin already exists.\n";
            return;
        }

        $db->table('users')->insert([
            'username'      => 'superadmin',
            'password_hash' => password_hash('asdfasdf', PASSWORD_DEFAULT),
            'email'         => 'asalvador@ssct.edu.ph',
            'first_name'    => 'Super',
            'middle_name'   => null,
            'last_name'     => 'Admin',

            'contact_no'    => null,
            'address_line'  => null,
            'postal_code'   => null,

            // Leave location blank for SA (or set to Del Carmen if you prefer)
            'region_pcode'       => null,
            'province_pcode'     => null,
            'municipality_pcode' => null,
            'barangay_pcode'     => null,

            'user_type' => 'super_admin',
            'status'    => 1,

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo "Super admin created.\n";
    }
}