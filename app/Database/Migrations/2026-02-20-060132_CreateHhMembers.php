<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhMembers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'visit_id' => ['type'=>'INT','unsigned'=>true],

            'last_name'   => ['type'=>'VARCHAR','constraint'=>100],
            'first_name'  => ['type'=>'VARCHAR','constraint'=>100],
            'middle_name' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],

            'relationship_code'  => ['type'=>'TINYINT','unsigned'=>true], // 1-5
            'relationship_other' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],

            'sex' => ['type'=>'CHAR','constraint'=>1], // M/F
            'dob' => ['type'=>'DATE'],

            'civil_status' => ['type'=>'VARCHAR','constraint'=>2], // M,S,W,SP,C

            'philhealth_id' => ['type'=>'VARCHAR','constraint'=>40,'null'=>true],
            'membership_type' => ['type'=>'VARCHAR','constraint'=>12,'null'=>true], // member|dependent
            'philhealth_category' => ['type'=>'VARCHAR','constraint'=>5,'null'=>true], // FEP,FEG,IE,N,SC,IP,U

            // Medical history multi-select stored as comma list "HPN,DM"
            'medical_history' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],

            'lmp_date' => ['type'=>'DATE','null'=>true],

            'educ_attainment' => ['type'=>'VARCHAR','constraint'=>3,'null'=>true],
            'religion' => ['type'=>'VARCHAR','constraint'=>80,'null'=>true],

            'remarks' => ['type'=>'TEXT','null'=>true],

            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('visit_id');
        $this->forge->createTable('hh_members');
    }

    public function down()
    {
        $this->forge->dropTable('hh_members');
    }
}