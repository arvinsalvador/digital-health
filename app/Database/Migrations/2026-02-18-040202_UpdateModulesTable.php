<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateModulesTable extends Migration
{
    public function up()
    {
        $fields = [

            'can_disable' => [
                'type' => 'TINYINT',
                'default' => 1,
                'after' => 'enabled'
            ],

            'can_delete' => [
                'type' => 'TINYINT',
                'default' => 1,
                'after' => 'can_disable'
            ],

            'menu_json' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'author'
            ],

            'is_core' => [
                'type' => 'TINYINT',
                'default' => 0
            ]
        ];

        $this->forge->addColumn('modules', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('modules',
            ['can_disable','can_delete','menu_json','is_core']);
    }
}
