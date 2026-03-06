<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhGroupMemberQuarters extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'group_member_id' => ['type' => 'INT', 'unsigned' => true],

            'year'    => ['type' => 'SMALLINT', 'unsigned' => true],
            'quarter' => ['type' => 'TINYINT', 'unsigned' => true], // 1-4

            'age'        => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'class_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['group_member_id', 'year', 'quarter'], false, true); // unique
        $this->forge->createTable('hh_group_member_quarters', true);
    }

    public function down()
    {
        $this->forge->dropTable('hh_group_member_quarters', true);
    }
}