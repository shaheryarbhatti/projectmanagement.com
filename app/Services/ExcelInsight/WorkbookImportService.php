<?php

namespace App\Services\ExcelInsight;

use App\Models\DatabaseSmartRecord;
use App\Models\PivotTableEntry;
use App\Models\SuspensionResumptionRecord;
use App\Models\WorkbookUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkbookImportService
{
    public function process(WorkbookUpload $upload, bool $shouldTruncate = true): array
    {
        $spreadsheet = IOFactory::load(Storage::disk(config('excel_insights.upload_disk'))->path($upload->stored_path));
        $tabs = $upload->selected_tabs ?: config('excel_insights.allowed_sheets', []);

        if ($shouldTruncate) {
            $this->truncateSelected($tabs);
        }

        $summary = [];

        if (in_array('Database_Smart', $tabs, true)) {
            $summary['database_smart'] = $this->importDatabaseSmartSheet($spreadsheet->getSheetByName('Database_Smart'), $upload);
        }

        if (in_array('Suspension & Resumption', $tabs, true)) {
            $summary['suspension_resumption'] = $this->importSuspensionSheet($spreadsheet->getSheetByName('Suspension & Resumption'), $upload);
        }

        if (in_array('Pivot_Tables', $tabs, true)) {
            $summary['pivot_tables'] = $this->importPivotSheet($spreadsheet->getSheetByName('Pivot_Tables'), $upload);
        }

        return $summary;
    }

    public function truncateSelected(array $tabs): void
    {
        if (in_array('Database_Smart', $tabs, true)) {
            DatabaseSmartRecord::query()->truncate();
        }

        if (in_array('Suspension & Resumption', $tabs, true)) {
            SuspensionResumptionRecord::query()->truncate();
        }

        if (in_array('Pivot_Tables', $tabs, true)) {
            PivotTableEntry::query()->truncate();
        }
    }

    private function importDatabaseSmartSheet(?Worksheet $sheet, WorkbookUpload $upload): array
    {
        if (! $sheet) {
            return ['rows' => 0, 'status' => 'missing'];
        }

        $headerRow = $this->findHeaderRow($sheet, 'Ref.', 'Program Name');
        $highestDataRow = $sheet->getHighestDataRow();
        $rows = [];
        $count = 0;

        for ($row = $headerRow + 1; $row <= $highestDataRow; $row++) {
            $cells = $this->readRow($sheet, $row);
            if (! $this->rowHasData($cells, 0, 6)) {
                continue;
            }

            $rows[] = [
                'upload_batch_id' => $upload->id,
                'source_row' => $row,
                'ref' => $this->string($cells[0] ?? null),
                'program_name' => $this->string($cells[1] ?? null),
                'sap_program_wbs_name' => $this->string($cells[2] ?? null),
                'project_name' => $this->string($cells[3] ?? null),
                'sap_project_wbs_name' => $this->string($cells[4] ?? null),
                'business_case_no' => $this->string($cells[5] ?? null),
                'main_wbs' => $this->string($cells[6] ?? null),
                'sub_wbs' => $this->string($cells[7] ?? null),
                'cost_centre' => $this->string($cells[8] ?? null),
                'pr_no' => $this->string($cells[9] ?? null),
                'po_no' => $this->string($cells[10] ?? null),
                'allocated_project_amount' => $this->decimal($cells[11] ?? null),
                'approval_year' => $this->integer($cells[12] ?? null),
                'owner' => $this->string($cells[13] ?? null),
                'executor' => $this->string($cells[14] ?? null),
                'department' => $this->string($cells[15] ?? null),
                'strategic_driver' => $this->string($cells[16] ?? null),
                'level' => $this->string($cells[17] ?? null),
                'blank_1' => $this->string($cells[18] ?? null),
                'project_name_arabic' => $this->string($cells[19] ?? null),
                'contractor_designer_name' => $this->string($cells[20] ?? null),
                'contract_type' => $this->string($cells[21] ?? null),
                'original_contract_value' => $this->decimal($cells[22] ?? null),
                'vo_amount' => $this->decimal($cells[23] ?? null),
                'vo_pct' => $this->decimal($cells[24] ?? null),
                'advance_amount' => $this->decimal($cells[25] ?? null),
                'total_paid_amount' => $this->decimal($cells[26] ?? null),
                'invoiced_amount' => $this->decimal($cells[27] ?? null),
                'remaining_amount' => $this->decimal($cells[28] ?? null) ?? $this->calculateRemainingAmount($cells),
                'blank_2' => $this->string($cells[29] ?? null),
                'project_status' => $this->string($cells[30] ?? null),
                'stage_gate' => $this->string($cells[31] ?? null),
                'stage_gate_progress' => $this->string($cells[32] ?? null),
                'category_1_previous' => $this->string($cells[33] ?? null),
                'designer_category' => $this->string($cells[34] ?? null),
                'contractor_category' => $this->string($cells[35] ?? null),
                'program' => $this->string($cells[36] ?? null),
                'sub_program' => $this->string($cells[37] ?? null),
                'cm_category' => $this->string($cells[38] ?? null),
                'program_manager' => $this->string($cells[39] ?? null),
                'project_manager' => $this->string($cells[40] ?? null),
                'project_lead' => $this->string($cells[41] ?? null),
                'project_start_date' => $this->excelDate($cells[42] ?? null),
                'project_finish_date' => $this->excelDate($cells[43] ?? null),
                'original_duration' => $this->integer($cells[44] ?? null) ?? $this->calculateDuration($cells[42] ?? null, $cells[43] ?? null),
                'suspension_date' => $this->excelDate($cells[45] ?? null),
                'resumption_date' => $this->excelDate($cells[46] ?? null),
                'revised_finish_date' => $this->excelDate($cells[47] ?? null),
                'revised_duration' => $this->integer($cells[48] ?? null) ?? $this->calculateDuration($cells[42] ?? null, $cells[47] ?? null),
                'planned_pct' => $this->decimal($cells[49] ?? null),
                'actual_pct' => $this->decimal($cells[50] ?? null),
                'sv' => $this->decimal($cells[51] ?? null) ?? $this->difference($cells[50] ?? null, $cells[49] ?? null),
                'ev' => $this->decimal($cells[52] ?? null) ?? $this->multiply($cells[50] ?? null, $this->sum($cells[22] ?? null, $cells[23] ?? null)),
                'pv' => $this->decimal($cells[53] ?? null) ?? $this->multiply($cells[49] ?? null, $this->sum($cells[22] ?? null, $cells[23] ?? null)),
                'ac' => $this->decimal($cells[54] ?? null),
                'spi' => $this->decimal($cells[55] ?? null),
                'cpi' => $this->decimal($cells[56] ?? null),
                'applicable_finish_date' => $this->excelDate($cells[57] ?? null) ?? $this->excelDate($cells[47] ?? null) ?? $this->excelDate($cells[43] ?? null),
                'project_health' => $this->string($cells[58] ?? null) ?? $this->inferProjectHealth($cells),
                'blank_3' => $this->string($cells[59] ?? null),
                'brief_description' => $this->string($cells[60] ?? null),
                'engineering_pct' => $this->decimal($cells[61] ?? null),
                'procurement_pct' => $this->decimal($cells[62] ?? null),
                'construction_pct' => $this->decimal($cells[63] ?? null),
                'engineering_status_update' => $this->string($cells[64] ?? null),
                'procurement_status_update' => $this->string($cells[65] ?? null),
                'construction_status_update' => $this->string($cells[66] ?? null),
                'weekly_lookahead' => $this->string($cells[67] ?? null),
                'issues_concerns' => $this->string($cells[68] ?? null),
                'risks' => $this->string($cells[69] ?? null),
                'data_issue' => Str::limit((string) ($this->string($cells[70] ?? null) ?? ''), 255, ''),
                'key_check' => $this->string($cells[71] ?? null),
                'last_updated_at' => $this->excelDateTime($cells[72] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;

            if (count($rows) >= 100) {
                DatabaseSmartRecord::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DatabaseSmartRecord::query()->insert($rows);
        }

        return ['rows' => $count, 'status' => 'processed'];
    }

    private function importSuspensionSheet(?Worksheet $sheet, WorkbookUpload $upload): array
    {
        if (! $sheet) {
            return ['rows' => 0, 'status' => 'missing'];
        }

        $headerRow = $this->findHeaderRow($sheet, 'Project Name', 'PO');
        $highestDataRow = $sheet->getHighestDataRow();
        $rows = [];
        $count = 0;

        for ($row = $headerRow + 1; $row <= $highestDataRow; $row++) {
            $cells = $this->readRow($sheet, $row);
            if (! $this->rowHasData($cells, 1, 6)) {
                continue;
            }

            $rows[] = [
                'upload_batch_id' => $upload->id,
                'source_row' => $row,
                'project_name' => $this->string($cells[1] ?? null),
                'contractor_designer_name' => $this->string($cells[2] ?? null),
                'actual_pct' => $this->decimal($cells[3] ?? null),
                'type_of_suspension' => $this->string($cells[4] ?? null),
                'po' => $this->string($cells[5] ?? null),
                'project_start_date' => $this->excelDate($cells[6] ?? null),
                'suspension_date' => $this->excelDate($cells[7] ?? null),
                'suspension_reason' => $this->string($cells[8] ?? null),
                'resumption_date' => $this->excelDate($cells[9] ?? null),
                'revised_finish_date' => $this->excelDate($cells[10] ?? null),
                'suspension_duration_days' => $this->integer($cells[11] ?? null),
                'status_of_resumption' => $this->string($cells[12] ?? null),
                'remarks' => $this->string($cells[13] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;

            if (count($rows) >= 100) {
                SuspensionResumptionRecord::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            SuspensionResumptionRecord::query()->insert($rows);
        }

        return ['rows' => $count, 'status' => 'processed'];
    }

    private function importPivotSheet(?Worksheet $sheet, WorkbookUpload $upload): array
    {
        if (! $sheet) {
            return ['rows' => 0, 'status' => 'missing'];
        }

        $highestDataRow = $sheet->getHighestDataRow();
        $entries = [];
        $sectionTitle = null;
        $metricTitle = null;
        $columnLabels = [];

        for ($row = 1; $row <= $highestDataRow; $row++) {
            $cells = $this->readRow($sheet, $row);
            $first = $this->string($cells[0] ?? null);

            if ($first === 'Pivot Tables for Total Count and Values') {
                $sectionTitle = $first;
                continue;
            }

            if ($first && (str_starts_with($first, 'Sum of ') || str_starts_with($first, 'Count of '))) {
                $metricTitle = $first;
                $columnLabels = [];
                continue;
            }

            if ($first === 'Row Labels' && $metricTitle) {
                $columnLabels = array_map(fn ($value) => $this->string($value), array_slice($cells, 1));
                continue;
            }

            if (! $metricTitle || ! $first) {
                continue;
            }

            foreach (array_slice($cells, 1) as $index => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $entries[] = [
                    'upload_batch_id' => $upload->id,
                    'source_row' => $row,
                    'source_column' => $index + 2,
                    'cell_reference' => Coordinate::stringFromColumnIndex($index + 2) . $row,
                    'section_title' => $sectionTitle,
                    'metric_title' => $metricTitle,
                    'row_label' => $first,
                    'column_label' => $columnLabels[$index] ?? null,
                    'value_numeric' => $this->decimal($value),
                    'value_text' => is_scalar($value) ? (string) $value : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($entries, 200) as $chunk) {
            PivotTableEntry::query()->insert($chunk);
        }

        return ['rows' => count($entries), 'status' => 'processed'];
    }

    private function findHeaderRow(Worksheet $sheet, string $firstNeedle, string $secondNeedle): int
    {
        $highestDataRow = min($sheet->getHighestDataRow(), 30);
        for ($row = 1; $row <= $highestDataRow; $row++) {
            $cells = $this->readRow($sheet, $row);
            $joined = implode(' | ', array_filter(array_map(fn ($value) => $this->string($value), $cells)));
            if ($joined !== '' && str_contains($joined, $firstNeedle) && str_contains($joined, $secondNeedle)) {
                return $row;
            }
        }

        throw new \RuntimeException('Unable to locate worksheet headers for ' . $sheet->getTitle() . '.');
    }

    private function readRow(Worksheet $sheet, int $row): array
    {
        $highestColumn = $sheet->getHighestDataColumn();
        $highestIndex = Coordinate::columnIndexFromString($highestColumn);
        $cells = [];

        for ($column = 1; $column <= $highestIndex; $column++) {
            $cell = $sheet->getCell([$column, $row]);
            $value = $cell->getValue();
            if (is_string($value) && str_starts_with($value, '=')) {
                try {
                    $value = method_exists($cell, 'getOldCalculatedValue') ? $cell->getOldCalculatedValue() : $value;
                } catch (\Throwable) {
                }
            }
            $cells[] = $value;
        }

        return $cells;
    }

    private function rowHasData(array $cells, int $start, int $end): bool
    {
        for ($index = $start; $index <= $end; $index++) {
            if ($this->string($cells[$index] ?? null) !== null || $this->decimal($cells[$index] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    private function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    private function integer(mixed $value): ?int
    {
        $number = $this->decimal($value);
        return $number === null ? null : (int) round($number);
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return round((float) $value, 4);
        }
        $normalized = str_replace([',', '%', "\xc2\xa0"], '', trim((string) $value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 4);
    }

    private function excelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 1000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function excelDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 1000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function calculateRemainingAmount(array $cells): ?float
    {
        $contractValue = $this->decimal($cells[22] ?? null);
        $voAmount = $this->decimal($cells[23] ?? null) ?? 0;
        $paid = $this->decimal($cells[26] ?? null);
        if ($contractValue === null || $paid === null) {
            return null;
        }

        return round(($contractValue + $voAmount) - $paid, 2);
    }

    private function calculateDuration(mixed $start, mixed $finish): ?int
    {
        $startDate = $this->excelDate($start);
        $finishDate = $this->excelDate($finish);
        if (! $startDate || ! $finishDate) {
            return null;
        }
        return Carbon::parse($startDate)->diffInDays(Carbon::parse($finishDate)) + 1;
    }

    private function difference(mixed $left, mixed $right): ?float
    {
        $leftValue = $this->decimal($left);
        $rightValue = $this->decimal($right);
        if ($leftValue === null || $rightValue === null) {
            return null;
        }
        return round($leftValue - $rightValue, 4);
    }

    private function sum(mixed $left, mixed $right): ?float
    {
        $leftValue = $this->decimal($left);
        $rightValue = $this->decimal($right) ?? 0;
        if ($leftValue === null) {
            return null;
        }
        return round($leftValue + $rightValue, 2);
    }

    private function multiply(mixed $left, mixed $right): ?float
    {
        $leftValue = $this->decimal($left);
        $rightValue = is_numeric($right) ? (float) $right : $this->decimal($right);
        if ($leftValue === null || $rightValue === null) {
            return null;
        }
        return round($leftValue * $rightValue, 2);
    }

    private function inferProjectHealth(array $cells): ?string
    {
        $status = strtolower($this->string($cells[30] ?? null) ?? '');
        if (in_array($status, ['completed', 'cancelled', 'on hold', 'not started'], true)) {
            return ucfirst($status);
        }

        $finishDate = $this->excelDate($cells[47] ?? null) ?? $this->excelDate($cells[43] ?? null);
        $sv = $this->difference($cells[50] ?? null, $cells[49] ?? null);

        if ($finishDate && Carbon::parse($finishDate)->isPast()) {
            return 'Overdue';
        }
        if ($sv === null) {
            return null;
        }
        if ($sv > -0.1) {
            return 'On Track';
        }
        return $sv < -0.2 ? 'Troubled' : 'Delayed';
    }
}
