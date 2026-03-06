<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhGroupMembers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'family_group_id' => ['type' => 'INT', 'unsigned' => true],

            // Link to an existing hh_members record (from another visit/household)
            'linked_member_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            // Snapshot/local person details (used when unlinked OR as display snapshot when linked)
            'local_last_name'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'local_first_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'local_middle_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],

            'relationship_code'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true], // 1-5
            'relationship_other' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],

            'sex' => ['type' => 'CHAR', 'constraint' => 1, 'null' => true], // M/F
            'dob' => ['type' => 'DATE', 'null' => true],

            // Civil now uses full strings (single/married/...)
            'civil_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],

            'philhealth_id'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'membership_type'     => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true], // member|dependent
            'philhealth_category' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],

            // Medical history multi-select stored as comma list "HPN,DM"
            'medical_history' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],

            'lmp_date' => ['type' => 'DATE', 'null' => true],

            'educ_attainment' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            'religion'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],

            // member-level living/occupancy status within this household
            'status_in_household' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true], // primary_resident, temporary, boarder, etc.
            'stay_from' => ['type' => 'DATE', 'null' => true],
            'stay_to'   => ['type' => 'DATE', 'null' => true],

            'remarks' => ['type' => 'TEXT', 'null' => true],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('family_group_id');
        $this->forge->addKey('linked_member_id');
        $this->forge->createTable('hh_group_members', true);
    }

    public function down()
    {
        $this->forge->dropTable('hh_group_members', true);
    }
}