<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhMemberQuarters extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'member_id' => ['type'=>'INT','unsigned'=>true],

            'year' => ['type'=>'SMALLINT','unsigned'=>true],
            'quarter' => ['type'=>'TINYINT','unsigned'=>true], // 1-4

            'age' => ['type'=>'TINYINT','unsigned'=>true],
            'class_code' => ['type'=>'VARCHAR','constraint'=>10],

            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['member_id','year','quarter'], false, true); // unique
        $this->forge->createTable('hh_member_quarters');
    }

    public function down()
    {
        $this->forge->dropTable('hh_member_quarters');
    }
}