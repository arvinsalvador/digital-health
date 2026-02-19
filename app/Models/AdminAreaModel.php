<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminAreaModel extends Model
{
    protected $table = 'admin_areas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'pcode','name','level','parent_pcode','is_active'
    ];

    protected $useTimestamps = true;

    public function listByLevel(int $level, ?string $parent = null): array
    {
        $b = $this->where('level', $level);

        if ($parent !== null && $parent !== '') {
            $b->where('parent_pcode', $parent);
        }

        return $b->orderBy('name','ASC')->findAll();
    }
}
