<?php

namespace App\Models;

use CodeIgniter\Model;

class HhFamilyGroupModel extends Model
{
    protected $table = 'hh_family_groups';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'visit_id',
        'group_name',
        'living_status',
        'notes',
    ];
}