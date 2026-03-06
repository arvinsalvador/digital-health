<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhFamilyGroups extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'visit_id'  => ['type' => 'INT', 'unsigned' => true],

            'group_name'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'living_status' => ['type' => 'VARCHAR', 'constraint' => 30], // owner_occupant, renter_tenant, boarder_lodger, etc.
            'notes'         => ['type' => 'TEXT', 'null' => true],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('visit_id');
        $this->forge->createTable('hh_family_groups', true);
    }

    public function down()
    {
        $this->forge->dropTable('hh_family_groups', true);
    }
}