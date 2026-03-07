<?php

namespace App\Models;

use CodeIgniter\Model;

class HhVisitModel extends Model
{
    protected $table = 'hh_visits';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'visit_date',
        'last_visit_date',
        'visit_count',
        'visit_quarter',
        'interviewed_by_user_id',
        'sitio_purok',
        'household_latitude',
        'household_longitude',
        'geo_source',
        'geo_accuracy_m',
        'barangay_pcode',
        'municipality_pcode',
        'household_no',
        'respondent_last_name',
        'respondent_first_name',
        'respondent_middle_name',
        'respondent_relation',
        'respondent_relation_other',
        'ethnicity_mode',
        'ethnicity_tribe',
        'socioeconomic_status',
        'nhts_no',
        'water_source',
        'water_source_other',
        'toilet_facility',
        'remarks',

        'approval_status',
        'approval_action',
        'submitted_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'approval_remarks',
        'pending_delete_requested_by',
        'pending_delete_requested_at',
    ];
}