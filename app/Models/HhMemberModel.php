<?php

namespace App\Models;

use CodeIgniter\Model;

class HhMemberModel extends Model
{
    protected $table = 'hh_members';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'visit_id',
        'last_name','first_name','middle_name',
        'relationship_code','relationship_other',
        'sex','dob','civil_status',
        'philhealth_id','membership_type','philhealth_category',
        'medical_history',
        'lmp_date',
        'educ_attainment','religion',
        'remarks'
    ];
}