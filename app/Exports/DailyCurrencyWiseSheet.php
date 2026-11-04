<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyCurrencyWiseSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $date;
    protected $values;

    public function __construct($date, $values)
    {
        $this->date = $date;
        $this->values = $values;
    }

    public function view(): View
    {
        return view('reports.partials.excel.daily-currency-wise-excel', [
            'date' => $this->date,
            'values' => $this->values,
        ]);
    }

    public function title(): string
    {
        return $this->date;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3    => ['font' => ['bold' => true]],
        ];
    }
}
