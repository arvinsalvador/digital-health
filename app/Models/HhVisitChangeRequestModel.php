<?php

namespace App\Models;

use CodeIgniter\Model;

class HhVisitChangeRequestModel extends Model
{
    protected $table = 'hh_visit_change_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'target_visit_id',
        'request_type',
        'status',
        'review_level',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'barangay_pcode',
        'municipality_pcode',
        'summary_text',
        'change_payload_json',
        'diff_payload_json',
        'reviewer_notes',
        'reviewed_at',
        'applied_at',
    ];
}