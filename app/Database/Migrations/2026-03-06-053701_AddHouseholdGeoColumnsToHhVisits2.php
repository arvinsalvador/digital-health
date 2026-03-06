<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHouseholdGeoColumnsToHhVisits2 extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('household_latitude', 'hh_visits')) {
            $fields['household_latitude'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true,
                'after' => 'toilet_facility',
            ];
        }

        if (! $this->db->fieldExists('household_longitude', 'hh_visits')) {
            $fields['household_longitude'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true,
                'after' => 'household_latitude',
            ];
        }

        if (! $this->db->fieldExists('geo_source', 'hh_visits')) {
            $fields['geo_source'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'household_longitude',
            ];
        }

        if (! $this->db->fieldExists('geo_accuracy_m', 'hh_visits')) {
            $fields['geo_accuracy_m'] = [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'null' => true,
                'after' => 'geo_source',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('hh_visits', $fields);
        }
    }

    public function down()
    {
        $drop = [];
        foreach (['household_latitude', 'household_longitude', 'geo_source', 'geo_accuracy_m'] as $field) {
            if ($this->db->fieldExists($field, 'hh_visits')) {
                $drop[] = $field;
            }
        }

        if (! empty($drop)) {
            $this->forge->dropColumn('hh_visits', $drop);
        }
    }
}
