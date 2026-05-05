<?php

namespace App\Imports;

use App\Models\WorkbookUpload;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkbookSheetsImport implements WithMultipleSheets
{
    public function __construct(
        private readonly WorkbookUpload $upload,
        private readonly array $selectedTabs
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        if (in_array('database_smart', $this->selectedTabs, true)) {
            $sheets['Database_Smart'] = new DatabaseSmartSheetImport($this->upload);
        }

        if (in_array('suspension_resumption', $this->selectedTabs, true)) {
            $sheets['Suspension & Resumption'] = new SuspensionResumptionSheetImport($this->upload);
        }

        if (in_array('pivot_tables', $this->selectedTabs, true)) {
            $sheets['Pivot_Tables'] = new PivotTablesSheetImport($this->upload);
        }

        return $sheets;
    }
}
