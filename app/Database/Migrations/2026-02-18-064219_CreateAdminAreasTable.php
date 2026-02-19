<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminAreasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'pcode' => ['type'=>'VARCHAR','constraint'=>50],
            'name' => ['type'=>'VARCHAR','constraint'=>200],
            'level' => ['type'=>'TINYINT','unsigned'=>true], // 1-4
            'parent_pcode' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'is_active' => ['type'=>'TINYINT','default'=>1],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('pcode');
        $this->forge->addKey(['level', 'parent_pcode']);
        $this->forge->createTable('admin_areas');
    }

    public function down()
    {
        $this->forge->dropTable('admin_areas');
    }
}
