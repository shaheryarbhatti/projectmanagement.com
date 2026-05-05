<?php

namespace App\Imports;

use App\Models\DatabaseSmartRecord;
use App\Models\WorkbookUpload;
use App\Services\ExcelInsight\WorkbookSchema;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DatabaseSmartSheetImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    public function __construct(
        private readonly WorkbookUpload $upload
    ) {
    }

    public function headingRow(): int
    {
        return 10;
    }

    public function collection(Collection $rows): void
    {
        $payload = [];
        $rowNumber = 11;

        foreach ($rows as $row) {
            $mapped = WorkbookSchema::mapDatabaseSmartRow($row->toArray(), $this->upload->id, $rowNumber);
            if ($mapped !== null) {
                $payload[] = $mapped;
            }
            $rowNumber++;
        }

        foreach (array_chunk($payload, 200) as $chunk) {
            DatabaseSmartRecord::insert($chunk);
        }
    }
}
