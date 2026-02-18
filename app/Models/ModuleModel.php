<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table = 'modules';
    protected $allowedFields = [
        'slug','name','version','description','author',
        'enabled','can_disable','can_delete',
        'menu_json','is_core',
        'installed_at','updated_at'
    ];
}
