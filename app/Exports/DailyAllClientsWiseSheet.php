<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;


class DailyAllClientsWiseSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $currency;
    protected $filteredData;


    public function __construct($currency, $filteredData)
    {
        $this->currency = $currency;
        $this->filteredData = $filteredData;
    }

    public function view(): View
    {
        return view('reports.partials.excel.daily-all-clients-wise-excel', [
            'currency' => $this->currency,
            'filteredData' => $this->filteredData,
        ]);
    }

    public function title(): string
    {
        return $this->currency->currency->short;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            3    => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);
            },
        ];
    }
}
