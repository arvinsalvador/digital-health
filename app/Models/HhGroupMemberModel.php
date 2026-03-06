<?php

namespace App\Models;

use CodeIgniter\Model;

class HhGroupMemberModel extends Model
{
    protected $table = 'hh_group_members';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'family_group_id',
        'linked_member_id',

        'local_last_name',
        'local_first_name',
        'local_middle_name',

        'relationship_code',
        'relationship_other',

        'sex',
        'dob',
        'civil_status',

        'philhealth_id',
        'membership_type',
        'philhealth_category',

        'medical_history',
        'lmp_date',
        'educ_attainment',
        'religion',

        'status_in_household',
        'stay_from',
        'stay_to',

        'remarks',
    ];
}