<?php

namespace App\Modules\Reports\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportService
{
    /**
     * Export collection to Excel.
     */
    public function export(Collection $data, string $filename, array $headings): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // For now, we'll implement a simple callback-based export
        // In a real scenario, we might create dedicated Export classes for each report
        return Excel::download(new class($data, $headings) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
        {
            public function __construct(protected Collection $data, protected array $headings) {}

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        }, $filename);
    }
}
