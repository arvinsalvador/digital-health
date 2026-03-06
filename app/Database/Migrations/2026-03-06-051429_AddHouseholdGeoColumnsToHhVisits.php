<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHouseholdGeoColumnsToHhVisits extends Migration
{
    public function up()
    {
        $fields = [
            'household_latitude' => [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true,
                'after' => 'household_no',
            ],
            'household_longitude' => [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true,
                'after' => 'household_latitude',
            ],
            'household_location_source' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'household_longitude',
            ],
            'household_location_accuracy' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'household_location_source',
            ],
        ];

        $this->forge->addColumn('hh_visits', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('hh_visits', [
            'household_latitude',
            'household_longitude',
            'household_location_source',
            'household_location_accuracy',
        ]);
    }
}
