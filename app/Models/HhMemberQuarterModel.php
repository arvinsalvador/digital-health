<?php

namespace App\Models;

use CodeIgniter\Model;

class HhMemberQuarterModel extends Model
{
    protected $table = 'hh_member_quarters';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'member_id','year','quarter','age','class_code'
    ];
}