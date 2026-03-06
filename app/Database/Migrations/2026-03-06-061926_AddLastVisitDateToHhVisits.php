<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastVisitDateToHhVisits extends Migration
{
    public function up()
    {
        $this->forge->addColumn('hh_visits', [
            'last_visit_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'visit_date'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('hh_visits', 'last_visit_date');
    }
}