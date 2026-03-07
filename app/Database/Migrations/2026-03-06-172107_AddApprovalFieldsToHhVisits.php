<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApprovalFieldsToHhVisits extends Migration
{
    public function up()
    {
        $fields = [
            'approval_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'approved',
                'after'      => 'remarks',
            ],
            'approval_action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'approval_status',
            ],
            'submitted_by_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'approval_action',
            ],
            'approved_by_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'submitted_by_user_id',
            ],
            'approved_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'approved_by_user_id',
            ],
            'rejected_by_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'approved_at',
            ],
            'rejected_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'rejected_by_user_id',
            ],
            'approval_remarks' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'rejected_at',
            ],
            'pending_delete_requested_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'approval_remarks',
            ],
            'pending_delete_requested_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'pending_delete_requested_by',
            ],
        ];

        $this->forge->addColumn('hh_visits', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('hh_visits', [
            'approval_status',
            'approval_action',
            'submitted_by_user_id',
            'approved_by_user_id',
            'approved_at',
            'rejected_by_user_id',
            'rejected_at',
            'approval_remarks',
            'pending_delete_requested_by',
            'pending_delete_requested_at',
        ]);
    }
}