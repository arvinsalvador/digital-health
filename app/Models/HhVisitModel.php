<?php

namespace App\Models;

use CodeIgniter\Model;

class HhVisitModel extends Model
{
    protected $table = 'hh_visits';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'visit_date','visit_quarter','interviewed_by_user_id',
        'sitio_purok','barangay_pcode','municipality_pcode',
        'household_no',
        'household_latitude','household_longitude','household_location_source','household_location_accuracy',
        'respondent_last_name','respondent_first_name','respondent_middle_name',
        'respondent_relation','respondent_relation_other',
        'ethnicity_mode','ethnicity_tribe',
        'socioeconomic_status','nhts_no',
        'water_source','water_source_other',
        'toilet_facility',
        'remarks'
    ];
}
