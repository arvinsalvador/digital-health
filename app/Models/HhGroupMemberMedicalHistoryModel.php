<?php

namespace App\Models;

use CodeIgniter\Model;

class HhGroupMemberMedicalHistoryModel extends Model
{
    protected $table = 'hh_group_member_medical_histories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'group_member_id',
        'condition_name',
        'date_diagnosed',
        'status',
        'remarks',
    ];
}