<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeUserRoles extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $db->query("UPDATE users SET user_type = 'barangay_captain' WHERE user_type = 'brgy_captain'");
        $db->query("UPDATE users SET user_type = 'staff' WHERE user_type = 'brgy_secretary'");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $db->query("UPDATE users SET user_type = 'brgy_captain' WHERE user_type = 'barangay_captain'");
        $db->query("UPDATE users SET user_type = 'brgy_secretary' WHERE user_type = 'staff'");
    }
}