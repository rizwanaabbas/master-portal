<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllBankWiseReportExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{

    protected $currencies;
    protected $banks;

    public function __construct($currencies, $banks)
    {
        $this->currencies = $currencies;
        $this->banks = $banks;
    }

    public function view(): View
    {
        return view('reports.partials.excel.all-bank-wise-report-excel', [
            'currencies' => $this->currencies,
            'banks' => $this->banks,
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
