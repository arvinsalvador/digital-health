<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'username','password_hash','email',
        'first_name','middle_name','last_name',
        'contact_no','address_line','postal_code',
        'region_pcode','province_pcode','municipality_pcode','barangay_pcode',
        'user_type','status'
    ];
}