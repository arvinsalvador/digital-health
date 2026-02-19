<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AdminAreaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LocationsImport extends BaseCommand
{
    protected $group       = 'Locations';
    protected $name        = 'locations:import';
    protected $description = 'Import PH admin boundaries (ADM1-ADM4) from Excel into admin_areas';

    public function run(array $params)
    {
        $file = $params[0] ?? null;

        if (!$file || !is_file($file)) {
            CLI::error('Usage: php spark locations:import "path/to/phl_adminboundaries_tabulardata.xlsx"');
            return;
        }

        $model = new AdminAreaModel();

        $spreadsheet = IOFactory::load($file);

        // Helper upsert by pcode
        $upsert = function(string $pcode, string $name, int $level, ?string $parent) use ($model) {
            $existing = $model->where('pcode', $pcode)->first();

            $payload = [
                'pcode' => $pcode,
                'name'  => $name,
                'level' => $level,
                'parent_pcode' => $parent,
                'is_active' => 1,
            ];

            if ($existing) {
                $model->update($existing['id'], $payload);
            } else {
                $model->insert($payload);
            }
        };

        // ADM1: Regions
        CLI::write('Importing ADM1 (Regions)...', 'yellow');
        $ws = $spreadsheet->getSheetByName('ADM1');
        $rows = $ws->toArray();
        $header = array_map('strval', $rows[0]);
        $idxName = array_search('ADM1_EN', $header);
        $idxCode = array_search('ADM1_PCODE', $header);

        for ($i=1; $i<count($rows); $i++) {
            $name = trim((string)$rows[$i][$idxName]);
            $code = trim((string)$rows[$i][$idxCode]);
            if ($name && $code) $upsert($code, $name, 1, null);
        }

        // ADM2: Provinces (parent: ADM1_PCODE)
        CLI::write('Importing ADM2 (Provinces)...', 'yellow');
        $ws = $spreadsheet->getSheetByName('ADM2');
        $rows = $ws->toArray();
        $header = array_map('strval', $rows[0]);
        $idxName = array_search('ADM2_EN', $header);
        $idxCode = array_search('ADM2_PCODE', $header);
        $idxParent = array_search('ADM1_PCODE', $header);

        for ($i=1; $i<count($rows); $i++) {
            $name = trim((string)$rows[$i][$idxName]);
            $code = trim((string)$rows[$i][$idxCode]);
            $parent = trim((string)$rows[$i][$idxParent]);
            if ($name && $code && $parent) $upsert($code, $name, 2, $parent);
        }

        // ADM3: Municipalities/City (parent: ADM2_PCODE)
        CLI::write('Importing ADM3 (Municipalities)...', 'yellow');
        $ws = $spreadsheet->getSheetByName('ADM3');
        $rows = $ws->toArray();
        $header = array_map('strval', $rows[0]);
        $idxName = array_search('ADM3_EN', $header);
        $idxCode = array_search('ADM3_PCODE', $header);
        $idxParent = array_search('ADM2_PCODE', $header);

        for ($i=1; $i<count($rows); $i++) {
            $name = trim((string)$rows[$i][$idxName]);
            $code = trim((string)$rows[$i][$idxCode]);
            $parent = trim((string)$rows[$i][$idxParent]);
            if ($name && $code && $parent) $upsert($code, $name, 3, $parent);
        }

        // ADM4: Barangays (parent: ADM3_PCODE)
        CLI::write('Importing ADM4 (Barangays)...', 'yellow');
        $ws = $spreadsheet->getSheetByName('ADM4');
        $rows = $ws->toArray();
        $header = array_map('strval', $rows[0]);
        $idxName = array_search('ADM4_EN', $header);
        $idxCode = array_search('ADM4_PCODE', $header);
        $idxParent = array_search('ADM3_PCODE', $header);

        for ($i=1; $i<count($rows); $i++) {
            $name = trim((string)$rows[$i][$idxName]);
            $code = trim((string)$rows[$i][$idxCode]);
            $parent = trim((string)$rows[$i][$idxParent]);
            if ($name && $code && $parent) $upsert($code, $name, 4, $parent);
        }

        CLI::write('✅ Import complete.', 'green');
    }
}
