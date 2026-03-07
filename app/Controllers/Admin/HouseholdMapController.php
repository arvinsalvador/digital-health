<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class HouseholdMapController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        $db = \Config\Database::connect();

        $builder = $db->table('hh_visits v')
            ->select([
                'v.id',
                'v.household_no',
                'v.respondent_last_name',
                'v.respondent_first_name',
                'v.respondent_middle_name',
                'v.ethnicity_mode',
                'v.ethnicity_tribe',
                'v.socioeconomic_status',
                'v.water_source',
                'v.water_source_other',
                'v.toilet_facility',
                'v.sitio_purok',
                'v.barangay_pcode',
                'v.municipality_pcode',
                'v.household_latitude',
                'v.household_longitude',
                'ba.name AS barangay_name',
                'mu.name AS municipality_name',
                'fg.family_group_count',
            ])
            ->join('admin_areas ba', 'ba.pcode = v.barangay_pcode', 'left')
            ->join('admin_areas mu', 'mu.pcode = v.municipality_pcode', 'left')
            ->join('(SELECT visit_id, COUNT(*) AS family_group_count FROM hh_family_groups GROUP BY visit_id) fg', 'fg.visit_id = v.id', 'left', false)
            ->where('v.household_latitude IS NOT NULL', null, false)
            ->where('v.household_longitude IS NOT NULL', null, false)
            ->orderBy('v.visit_date', 'DESC')
            ->orderBy('v.id', 'DESC');

        if (($actor['user_type'] ?? '') === 'super_admin') {
            // no extra filter
        } elseif (in_array(($actor['user_type'] ?? ''), ['admin', 'staff'], true)) {
            $builder->where('v.municipality_pcode', $actor['municipality_pcode'] ?? null);
        } else {
            $builder->where('v.barangay_pcode', $actor['barangay_pcode'] ?? null);
        }

        $rows = $builder->get()->getResultArray();

        $markers = [];
        foreach ($rows as $r) {
            $lat = isset($r['household_latitude']) ? (float) $r['household_latitude'] : null;
            $lng = isset($r['household_longitude']) ? (float) $r['household_longitude'] : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $markers[] = [
                'id' => (int) $r['id'],
                'lat' => $lat,
                'lng' => $lng,
                'household_no' => (string) ($r['household_no'] ?? ''),
                'respondent_name' => $this->formatRespondentName($r),
                'family_group_count' => (int) ($r['family_group_count'] ?? 0),
                'ethnicity' => $this->formatEthnicity($r),
                'socioeconomic_status' => $this->formatSocioeconomicStatus((string) ($r['socioeconomic_status'] ?? '')),
                'water_source' => $this->formatWaterSource($r),
                'toilet_type' => $this->formatToiletType((string) ($r['toilet_facility'] ?? '')),
                'sitio_purok' => (string) ($r['sitio_purok'] ?? ''),
                'barangay_name' => (string) ($r['barangay_name'] ?? ''),
                'municipality_name' => (string) ($r['municipality_name'] ?? ''),
                'edit_url' => base_url('admin/registry/household-profiling/' . (int) $r['id'] . '/edit'),
            ];
        }

        return view('admin/registry/household_map/index', [
            'pageTitle' => 'Household Map',
            'actor' => $actor,
            'markers' => $markers,
            'markerCount' => count($markers),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    private function formatRespondentName(array $row): string
    {
        $last = trim((string) ($row['respondent_last_name'] ?? ''));
        $first = trim((string) ($row['respondent_first_name'] ?? ''));
        $middle = trim((string) ($row['respondent_middle_name'] ?? ''));

        $name = $last;
        if ($first !== '') {
            $name .= ($name !== '' ? ', ' : '') . $first;
        }
        if ($middle !== '') {
            $name .= ' ' . $middle;
        }

        return trim($name);
    }

    private function formatEthnicity(array $row): string
    {
        $mode = (string) ($row['ethnicity_mode'] ?? '');
        $tribe = trim((string) ($row['ethnicity_tribe'] ?? ''));

        if ($mode === 'tribe') {
            return $tribe !== '' ? 'IP Household - ' . $tribe : 'IP Household';
        }

        if ($mode === 'ip' || $mode === 'ip_household') {
            return 'IP Household';
        }

        return $tribe !== '' ? $tribe : 'Not specified';
    }

    private function formatSocioeconomicStatus(string $value): string
    {
        return match ($value) {
            'nhts_4ps'    => 'NHTS / 4Ps',
            'nhts_non4ps' => 'NHTS / Non-4Ps',
            'non_nhts'    => 'Non-NHTS',
            'nhts'        => 'NHTS',
            default       => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : 'Not specified',
        };
    }

    private function formatWaterSource(array $row): string
    {
        $value = (string) ($row['water_source'] ?? '');
        $other = trim((string) ($row['water_source_other'] ?? ''));

        return match ($value) {
            'level1' => 'Level I',
            'level2' => 'Level II',
            'level3' => 'Level III',
            'others' => $other !== '' ? 'Others - ' . $other : 'Others',
            default  => $value !== '' ? strtoupper(str_replace('_', ' ', $value)) : 'Not specified',
        };
    }

    private function formatToiletType(string $value): string
    {
        return match (strtoupper($value)) {
            'A' => 'Water-sealed sewer/septic tank',
            'B' => 'Water-sealed other depository',
            'C' => 'Closed pit',
            'D' => 'Open pit',
            'E' => 'Drop / overhung latrine',
            'F' => 'No toilet / open defecation',
            'G' => 'Others',
            default => $value !== '' ? $value : 'Not specified',
        };
    }
}
