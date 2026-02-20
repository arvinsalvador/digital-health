<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],

            'username'      => ['type'=>'VARCHAR','constraint'=>50],
            'password_hash' => ['type'=>'VARCHAR','constraint'=>255],
            'email'         => ['type'=>'VARCHAR','constraint'=>120],

            'first_name'    => ['type'=>'VARCHAR','constraint'=>100],
            'middle_name'   => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'last_name'     => ['type'=>'VARCHAR','constraint'=>100],

            'contact_no'    => ['type'=>'VARCHAR','constraint'=>30,'null'=>true],

            'address_line'  => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'postal_code'   => ['type'=>'VARCHAR','constraint'=>10,'null'=>true],

            // Address PCODEs (from admin_areas)
            'region_pcode'       => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'province_pcode'     => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'municipality_pcode' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'barangay_pcode'     => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],

            // Phase-1 user types
            'user_type'     => ['type'=>'VARCHAR','constraint'=>30],

            // 1=active, 0=disabled
            'status'        => ['type'=>'TINYINT','default'=>1],

            'created_at'    => ['type'=>'DATETIME','null'=>true],
            'updated_at'    => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');

        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}