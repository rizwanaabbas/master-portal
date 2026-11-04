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

class DailyAllBanksExport implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $currencies;
    protected $data;

    public function __construct($currencies, $data)
    {
        $this->currencies = $currencies;
        $this->data = $data;
    }

    public function view(): View
    {
        return view('reports.partials.excel.daily-all-banks-excel', [
            'currencies' => $this->currencies,
            'data' => $this->data,
        ]);
    }

    public function title(): string
    {
        return 'Sheet1';
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
                $event->sheet->getPageSetup()->setFitToWidth(1);
                $event->sheet->getPageSetup()->setFitToHeight(0);
                $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);
            },
        ];
    }
}
