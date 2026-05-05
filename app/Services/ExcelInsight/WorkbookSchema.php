<?php

namespace App\Services\ExcelInsight;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkbookSchema
{
    public static function mapDatabaseSmartRow(array $row, int $uploadId, int $sourceRow): ?array
    {
        if (! filled($row['ref'] ?? null) && ! filled($row['project_name'] ?? null)) {
            return null;
        }

        return [
            'upload_batch_id' => $uploadId,
            'source_row' => $sourceRow,
            'ref' => self::string($row['ref'] ?? null),
            'program_name' => self::string($row['program_name'] ?? null),
            'sap_program_wbs_name' => self::string($row['sap_program_wbs_name'] ?? null),
            'project_name' => self::string($row['project_name'] ?? null),
            'sap_project_wbs_name' => self::string($row['sap_project_wbs_name'] ?? null),
            'business_case_no' => self::string($row['business_case_no'] ?? null),
            'main_wbs' => self::string($row['main_wbs'] ?? null),
            'sub_wbs' => self::string($row['sub_wbs'] ?? null),
            'cost_centre' => self::string($row['cost_centre'] ?? null),
            'pr_no' => self::string($row['pr_no'] ?? null),
            'po_no' => self::string($row['po_no'] ?? null),
            'allocated_project_amount' => self::decimal($row['allocated_project_amount'] ?? null),
            'approval_year' => self::integer($row['approval_year'] ?? null),
            'owner' => self::string($row['owner'] ?? null),
            'executor' => self::string($row['executor'] ?? null),
            'department' => self::string($row['department'] ?? null),
            'strategic_driver' => self::string($row['strategic_driver'] ?? null),
            'level' => self::string($row['level'] ?? null),
            'blank_1' => self::string($row['blank_1'] ?? null),
            'project_name_arabic' => self::string($row['project_name_arabic'] ?? null),
            'contractor_designer_name' => self::string($row['contractordesigner_name'] ?? null),
            'contract_type' => self::string($row['contract_type'] ?? null),
            'original_contract_value' => self::decimal($row['original_contract_value'] ?? null),
            'vo_amount' => self::decimal($row['vo_amount'] ?? null),
            'vo_pct' => self::decimal($row['vo'] ?? $row['vo_1'] ?? null),
            'advance_amount' => self::decimal($row['advance_amount'] ?? null),
            'total_paid_amount' => self::decimal($row['total_paid_amount'] ?? null),
            'invoiced_amount' => self::decimal($row['invoiced_amount'] ?? null),
            'remaining_amount' => self::decimal($row['remaining_amount'] ?? null),
            'blank_2' => self::string($row['blank_2'] ?? null),
            'project_status' => self::string($row['project_status'] ?? null),
            'stage_gate' => self::string($row['stage_gate'] ?? null),
            'stage_gate_progress' => self::string($row['stage_gate_progress'] ?? null),
            'category_1_previous' => self::string($row['category_1_previous'] ?? null),
            'designer_category' => self::string($row['designer_category'] ?? null),
            'contractor_category' => self::string($row['contractor_category'] ?? null),
            'program' => self::string($row['program'] ?? null),
            'sub_program' => self::string($row['sub_program'] ?? null),
            'cm_category' => self::string($row['cm_category'] ?? null),
            'program_manager' => self::string($row['program_manager'] ?? null),
            'project_manager' => self::string($row['project_manager'] ?? null),
            'project_lead' => self::string($row['project_lead'] ?? null),
            'project_start_date' => self::date($row['project_start_date'] ?? null),
            'project_finish_date' => self::date($row['project_finish_date'] ?? null),
            'original_duration' => self::integer($row['original_duration'] ?? null),
            'suspension_date' => self::date($row['suspension_date'] ?? null),
            'resumption_date' => self::date($row['resumption_date'] ?? null),
            'revised_finish_date' => self::date($row['revised_finish_date'] ?? null),
            'revised_duration' => self::integer($row['revised_duration'] ?? null),
            'planned_pct' => self::decimal($row['planned'] ?? null),
            'actual_pct' => self::decimal($row['actual'] ?? null),
            'sv' => self::decimal($row['sv'] ?? null),
            'ev' => self::decimal($row['ev'] ?? null),
            'pv' => self::decimal($row['pv'] ?? null),
            'ac' => self::decimal($row['ac'] ?? null),
            'spi' => self::decimal($row['spi'] ?? null),
            'cpi' => self::decimal($row['cpi'] ?? null),
            'applicable_finish_date' => self::date($row['applicable_finish_date'] ?? null),
            'project_health' => self::string($row['project_health'] ?? null),
            'blank_3' => self::string($row['blank_3'] ?? null),
            'brief_description' => self::string($row['brief_description'] ?? null),
            'engineering_pct' => self::decimal($row['engineering'] ?? null),
            'procurement_pct' => self::decimal($row['procurement'] ?? null),
            'construction_pct' => self::decimal($row['constuction'] ?? null),
            'engineering_status_update' => self::string($row['engineering_status_update'] ?? null),
            'procurement_status_update' => self::string($row['procurement_status_update'] ?? null),
            'construction_status_update' => self::string($row['construction_status_update'] ?? null),
            'weekly_lookahead' => self::string($row['weekly_lookahead'] ?? null),
            'issues_concerns' => self::string($row['issues_concerns'] ?? null),
            'risks' => self::string($row['risks'] ?? null),
            'data_issue' => self::string($row['data_issue'] ?? null),
            'key_check' => self::string($row['key_check'] ?? null),
            'last_updated_at' => self::datetime($row['last_updated'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public static function mapSuspensionRow(array $row, int $uploadId, int $sourceRow): ?array
    {
        if (! filled($row['project_name'] ?? null)) {
            return null;
        }

        return [
            'upload_batch_id' => $uploadId,
            'source_row' => $sourceRow,
            'project_name' => self::string($row['project_name'] ?? null),
            'contractor_designer_name' => self::string($row['contractordesigner_name'] ?? null),
            'actual_pct' => self::decimal($row['actual'] ?? null),
            'type_of_suspension' => self::string($row['type_of_suspension'] ?? null),
            'po' => self::string($row['po'] ?? null),
            'project_start_date' => self::date($row['project_start_date'] ?? null),
            'suspension_date' => self::date($row['suspension_date'] ?? null),
            'suspension_reason' => self::string($row['suspension_reason'] ?? null),
            'resumption_date' => self::date($row['resumption_date'] ?? null),
            'revised_finish_date' => self::date($row['revised_finish_date'] ?? null),
            'suspension_duration_days' => self::integer($row['suspension_duration_days'] ?? null),
            'status_of_resumption' => self::string($row['status_of_resumption'] ?? null),
            'remarks' => self::string($row['remarks'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public static function mapPivotRows(Collection $rows, int $uploadId): array
    {
        $entries = [];
        $currentSectionTitle = null;
        $currentMetricTitle = null;
        $currentHeaders = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = array_values($row->toArray());
            $nonEmpty = array_filter($cells, fn ($value) => filled(self::string($value)));

            if ($nonEmpty === []) {
                continue;
            }

            if (count($nonEmpty) === 1 && filled($cells[0] ?? null)) {
                $currentSectionTitle = self::string($cells[0]);
                $currentMetricTitle = null;
                $currentHeaders = [];
                continue;
            }

            if (self::string($cells[0] ?? null) && filled($cells[1] ?? null) && Str::contains((string) $cells[1], 'Column Labels')) {
                $currentMetricTitle = self::string($cells[0]);
                continue;
            }

            if (self::string($cells[0] ?? null) === 'Row Labels') {
                $currentHeaders = $cells;
                continue;
            }

            if ($currentHeaders !== []) {
                $rowLabel = self::string($cells[0] ?? null);
                foreach ($cells as $columnIndex => $value) {
                    if ($columnIndex === 0 || ! filled($value)) {
                        continue;
                    }

                    $entries[] = [
                        'upload_batch_id' => $uploadId,
                        'source_row' => $rowIndex + 1,
                        'source_column' => $columnIndex + 1,
                        'cell_reference' => self::columnLetter($columnIndex + 1) . ($rowIndex + 1),
                        'section_title' => $currentSectionTitle,
                        'metric_title' => $currentMetricTitle,
                        'row_label' => $rowLabel,
                        'column_label' => self::string($currentHeaders[$columnIndex] ?? null),
                        'value_numeric' => is_numeric($value) ? round((float) $value, 2) : null,
                        'value_text' => self::string($value),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        return $entries;
    }

    public static function date(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return now()->parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public static function datetime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }

        try {
            return now()->parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public static function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 6);
        }

        $normalized = preg_replace('/[^0-9\.\-]/', '', (string) $value);

        return is_numeric($normalized) ? round((float) $normalized, 6) : null;
    }

    public static function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    public static function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    public static function columnLetter(int $column): string
    {
        $letter = '';

        while ($column > 0) {
            $mod = ($column - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $column = intdiv($column - $mod, 26) - 1;
        }

        return $letter;
    }
}
