<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhVisits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],

            'visit_date'    => ['type'=>'DATE'],
            'visit_quarter' => ['type'=>'TINYINT','unsigned'=>true], // 1-4

            'interviewed_by_user_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],

            'sitio_purok'   => ['type'=>'VARCHAR','constraint'=>120],
            'barangay_pcode'=> ['type'=>'VARCHAR','constraint'=>50],
            'municipality_pcode'=> ['type'=>'VARCHAR','constraint'=>50,'null'=>true],

            'household_no'  => ['type'=>'VARCHAR','constraint'=>50],

            'respondent_last_name'   => ['type'=>'VARCHAR','constraint'=>100],
            'respondent_first_name'  => ['type'=>'VARCHAR','constraint'=>100],
            'respondent_middle_name' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],

            'respondent_relation'       => ['type'=>'VARCHAR','constraint'=>50], // e.g. Head/Spouse/Son/Daughter/Other
            'respondent_relation_other' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],

            // Ethnicity: ip_household OR tribe
            'ethnicity_mode'  => ['type'=>'VARCHAR','constraint'=>20], // ip_household|tribe
            'ethnicity_tribe' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],

            // Socioeconomic: nhts_4ps|nhts_non4ps|non_nhts
            'socioeconomic_status' => ['type'=>'VARCHAR','constraint'=>20],
            'nhts_no'              => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],

            // Water source
            'water_source'       => ['type'=>'VARCHAR','constraint'=>20], // level1|level2|level3|others
            'water_source_other' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],

            // Toilet facility A-G
            'toilet_facility' => ['type'=>'CHAR','constraint'=>1],

            'remarks' => ['type'=>'TEXT','null'=>true],

            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['barangay_pcode','visit_date']);
        $this->forge->createTable('hh_visits');
    }

    public function down()
    {
        $this->forge->dropTable('hh_visits');
    }
}