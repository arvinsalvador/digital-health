<?php

namespace App\Models;

use CodeIgniter\Model;

class HhGroupMemberQuarterModel extends Model
{
    protected $table = 'hh_group_member_quarters';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'group_member_id',
        'year',
        'quarter',
        'age',
        'class_code',
    ];
}