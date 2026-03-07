<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHhVisitChangeRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'target_visit_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'Null for create requests; set for update/delete requests',
            ],

            'request_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'create | update | delete',
            ],

            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'comment'    => 'pending | approved | rejected',
            ],

            'review_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'staff | admin',
            ],

            'requested_by_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],

            'reviewed_by_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],

            'barangay_pcode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],

            'municipality_pcode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],

            'summary_text' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Short human-readable overview like: Respondent last name updated',
            ],

            'change_payload_json' => [
                'type' => 'LONGTEXT',
                'comment' => 'Full submitted draft payload',
            ],

            'diff_payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'Computed diff between live record and submitted draft',
            ],

            'reviewer_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'applied_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'When the approved request was applied to live tables',
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
        $this->forge->addKey('target_visit_id');
        $this->forge->addKey('request_type');
        $this->forge->addKey('status');
        $this->forge->addKey('review_level');
        $this->forge->addKey('requested_by_user_id');
        $this->forge->addKey('reviewed_by_user_id');
        $this->forge->addKey('barangay_pcode');
        $this->forge->addKey('municipality_pcode');

        $this->forge->createTable('hh_visit_change_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('hh_visit_change_requests', true);
    }
}