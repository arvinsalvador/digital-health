<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhGroupMemberMedicalHistories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'group_member_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'condition_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'date_diagnosed' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('group_member_id');

        $this->forge->addForeignKey(
            'group_member_id',
            'hh_group_members',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('hh_group_member_medical_histories');
    }

    public function down()
    {
        $this->forge->dropTable('hh_group_member_medical_histories', true);
    }
}