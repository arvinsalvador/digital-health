<?php

namespace App\Commands;

use App\Models\HhMemberModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BackfillHhMembers extends BaseCommand
{
    protected $group = 'Household Profiling';
    protected $name = 'hh:backfill-members';
    protected $description = 'Backfill hh_members from existing hh_group_members records that are not yet linked.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $hhMemberModel = new HhMemberModel();

        $rows = $db->table('hh_group_members gm')
            ->select('gm.*, fg.visit_id')
            ->join('hh_family_groups fg', 'fg.id = gm.family_group_id', 'inner')
            ->groupStart()
                ->where('gm.linked_member_id', null)
                ->orWhere('gm.linked_member_id', 0)
            ->groupEnd()
            ->orderBy('gm.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            CLI::write('No unlinked group members found.', 'yellow');
            return;
        }

        $created = 0;
        $reused = 0;
        $updated = 0;

        $db->transStart();

        foreach ($rows as $row) {
            if (
                empty($row['local_last_name']) ||
                empty($row['local_first_name']) ||
                empty($row['sex']) ||
                empty($row['dob'])
            ) {
                continue;
            }

            $existing = $hhMemberModel
                ->where('last_name', $row['local_last_name'])
                ->where('first_name', $row['local_first_name'])
                ->where('sex', $row['sex'])
                ->where('dob', $row['dob'])
                ->orderBy('id', 'DESC')
                ->first();

            if ($existing) {
                $memberId = (int) $existing['id'];
                $reused++;
            } else {
                $memberId = $hhMemberModel->insert([
                    'visit_id' => $row['visit_id'],
                    'last_name' => $row['local_last_name'],
                    'first_name' => $row['local_first_name'],
                    'middle_name' => $row['local_middle_name'] ?: null,
                    'relationship_code' => !empty($row['relationship_code']) ? (int) $row['relationship_code'] : null,
                    'relationship_other' => $row['relationship_other'] ?: null,
                    'sex' => $row['sex'],
                    'dob' => $row['dob'],
                    'civil_status' => $this->legacyCivilStatusCode($row['civil_status'] ?? null),
                    'philhealth_id' => $row['philhealth_id'] ?: null,
                    'membership_type' => $row['membership_type'] ?: null,
                    'philhealth_category' => $row['philhealth_category'] ?: null,
                    'medical_history' => $row['medical_history'] ?: null,
                    'lmp_date' => $row['lmp_date'] ?: null,
                    'educ_attainment' => $row['educ_attainment'] ?: null,
                    'religion' => $row['religion'] ?: null,
                    'remarks' => $row['remarks'] ?: null,
                ], true);
                $created++;
            }

            $db->table('hh_group_members')
                ->where('id', $row['id'])
                ->update(['linked_member_id' => $memberId]);
            $updated++;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Backfill failed. Transaction was rolled back.');
            return;
        }

        CLI::write("Done. Created: {$created}, Reused: {$reused}, Updated group members: {$updated}", 'green');
    }

    private function legacyCivilStatusCode(?string $civil): ?string
    {
        $civil = strtolower(trim((string) $civil));
        if ($civil === '') {
            return null;
        }

        return match ($civil) {
            'single' => 'S',
            'married' => 'M',
            'widowed' => 'W',
            'separated' => 'SP',
            'live_in' => 'C',
            default => null,
        };
    }
}
