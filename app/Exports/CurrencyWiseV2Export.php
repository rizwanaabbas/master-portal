<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CurrencyWiseV2Export implements FromView, ShouldAutoSize, WithStyles,WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {   
        return view('reports.partials.excel.currency-wise-v2-excel', [
            'data' => $this->data,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3    => ['font' => ['bold' => true]],
        ];
    }
    
    public function title(): string
    {
        return 'Sheet1';
    }
}
