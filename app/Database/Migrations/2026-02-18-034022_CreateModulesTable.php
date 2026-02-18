<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModulesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','auto_increment'=>true],
            'slug' => ['type'=>'VARCHAR','constraint'=>100],
            'name' => ['type'=>'VARCHAR','constraint'=>150],
            'version' => ['type'=>'VARCHAR','constraint'=>20],
            'description' => ['type'=>'TEXT','null'=>true],
            'author' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'enabled' => ['type'=>'TINYINT','default'=>0],
            'installed_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('modules');
    }

    public function down()
    {
        $this->forge->dropTable('modules');
    }
}
