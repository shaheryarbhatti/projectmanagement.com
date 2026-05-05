<?php

namespace App\Imports;

use App\Models\PivotTableEntry;
use App\Models\WorkbookUpload;
use App\Services\ExcelInsight\WorkbookSchema;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class PivotTablesSheetImport implements ToCollection, WithCalculatedFormulas
{
    public function __construct(
        private readonly WorkbookUpload $upload
    ) {
    }

    public function collection(Collection $rows): void
    {
        $entries = WorkbookSchema::mapPivotRows($rows, $this->upload->id);

        foreach (array_chunk($entries, 300) as $chunk) {
            PivotTableEntry::insert($chunk);
        }
    }
}
