<?php

namespace App\Services\ExcelInsight;

use App\Models\DatabaseSmartRecord;
use App\Models\PivotTableEntry;
use App\Models\SuspensionResumptionRecord;
use App\Models\WorkbookUpload;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function latestUpload(): ?WorkbookUpload
    {
        return WorkbookUpload::query()->latest('processed_at')->latest('id')->first();
    }

    public function dashboardData(): array
    {
        $latestUpload = $this->latestUpload();
        $records = DatabaseSmartRecord::query();
        $suspensions = SuspensionResumptionRecord::query();
        $pivotEntries = PivotTableEntry::query();

        $statusDistribution = (clone $records)->selectRaw('COALESCE(project_status, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->get();
        $healthDistribution = (clone $records)->selectRaw('COALESCE(project_health, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->get();
        $approvalTrend = (clone $records)->selectRaw('approval_year as label, COUNT(*) as total')->whereNotNull('approval_year')->groupBy('approval_year')->orderBy('approval_year')->get();
        $stageGateBreakdown = (clone $records)->selectRaw('COALESCE(stage_gate, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(6)->get();
        $departmentBreakdown = (clone $records)->selectRaw('COALESCE(department, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(6)->get();
        $suspensionStatus = (clone $suspensions)->selectRaw('COALESCE(status_of_resumption, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->get();
        $pivotSummary = (clone $pivotEntries)->selectRaw('metric_title, COUNT(*) as total_rows, SUM(value_numeric) as total_value')->groupBy('metric_title')->orderBy('metric_title')->get();

        $financialsByDept = (clone $records)->selectRaw('COALESCE(department, "Unknown") as label, SUM(allocated_project_amount) as total')->groupBy('label')->orderByDesc('total')->limit(8)->get();
        $contractorDistribution = (clone $records)->selectRaw('COALESCE(executor, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(8)->get();
        $strategicDriverAlignment = (clone $records)->selectRaw('COALESCE(strategic_driver, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(8)->get();
        $suspensionReasons = (clone $suspensions)->selectRaw('COALESCE(suspension_reason, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(6)->get();
        $ownerDistribution = (clone $records)->selectRaw('COALESCE(owner, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(8)->get();

        return [
            'latestUpload' => $latestUpload,
            'stats' => [
                'total_records' => $records->count(),
                'active_records' => (clone $records)->whereIn('project_status', ['Ongoing', 'Active'])->count(),
                'completed_records' => (clone $records)->where('project_status', 'Completed')->count(),
                'suspended_records' => (clone $suspensions)->where('status_of_resumption', 'Pending')->count(),
                'resumed_records' => (clone $suspensions)->where('status_of_resumption', 'Resumed')->count(),
                'allocated_total' => (float) (clone $records)->sum('allocated_project_amount'),
            ],
            'charts' => [
                'statusDistribution' => $this->chartPayload($statusDistribution),
                'healthDistribution' => $this->chartPayload($healthDistribution),
                'approvalTrend' => $this->chartPayload($approvalTrend),
                'stageGateBreakdown' => $this->chartPayload($stageGateBreakdown),
                'departmentBreakdown' => $this->chartPayload($departmentBreakdown),
                'suspensionStatus' => $this->chartPayload($suspensionStatus),
                'financialsByDept' => $this->chartPayload($financialsByDept),
                'contractorDistribution' => $this->chartPayload($contractorDistribution),
                'strategicDriverAlignment' => $this->chartPayload($strategicDriverAlignment),
                'suspensionReasons' => $this->chartPayload($suspensionReasons),
                'ownerDistribution' => $this->chartPayload($ownerDistribution),
            ],
            'tables' => [
                'pivotSummary' => $pivotSummary,
                'recentProjects' => (clone $records)->select(['project_name', 'project_status', 'project_health', 'project_manager', 'approval_year', 'applicable_finish_date'])->latest('updated_at')->limit(8)->get(),
            ],
        ];
    }

    public function summaryText(): string
    {
        $stats = $this->dashboardData()['stats'];
        $topStatuses = DatabaseSmartRecord::query()->selectRaw('COALESCE(project_status, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(5)->get()->map(fn ($row) => "{$row->label}: {$row->total}")->implode(', ');
        $topDepartments = DatabaseSmartRecord::query()->selectRaw('COALESCE(department, "Unknown") as label, COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(5)->get()->map(fn ($row) => "{$row->label}: {$row->total}")->implode(', ');

        return trim(implode("\n", array_filter([
            "Total records: {$stats['total_records']}",
            "Active records: {$stats['active_records']}",
            "Completed records: {$stats['completed_records']}",
            "Suspended records: {$stats['suspended_records']}",
            "Resumed records: {$stats['resumed_records']}",
            'Top statuses: ' . $topStatuses,
            'Top departments: ' . $topDepartments,
            'Summary of first 5 projects: ' . DatabaseSmartRecord::query()->limit(5)->get()->map(fn ($p) => "{$p->project_name} ({$p->project_status}): " . Str::limit($p->brief_description, 100))->implode(' | '),
        ])));
    }

    private function chartPayload(Collection $rows): array
    {
        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (float) $value)->all(),
        ];
    }
}
