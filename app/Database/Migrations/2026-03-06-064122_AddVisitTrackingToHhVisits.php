<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisitTrackingToHhVisits extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('last_visit_date', 'hh_visits')) {
            $fields['last_visit_date'] = [
                'type' => 'DATE',
                'null' => true,
                'after' => 'visit_date',
            ];
        }

        if (! $this->db->fieldExists('visit_count', 'hh_visits')) {
            $fields['visit_count'] = [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 1,
                'after' => 'last_visit_date',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('hh_visits', $fields);
        }

        // Backfill existing rows safely
        $this->db->query("
            UPDATE hh_visits
            SET last_visit_date = COALESCE(last_visit_date, visit_date)
        ");

        $this->db->query("
            UPDATE hh_visits
            SET visit_count = COALESCE(NULLIF(visit_count, 0), 1)
        ");
    }

    public function down()
    {
        if ($this->db->fieldExists('visit_count', 'hh_visits')) {
            $this->forge->dropColumn('hh_visits', 'visit_count');
        }

        if ($this->db->fieldExists('last_visit_date', 'hh_visits')) {
            $this->forge->dropColumn('hh_visits', 'last_visit_date');
        }
    }
}