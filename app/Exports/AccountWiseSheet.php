<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountWiseSheet implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $currency;
    protected $clients;

    public function __construct($currency, $clients)
    {
        $this->currency  = $currency;
        $this->clients = $clients;
    }

    public function view(): View
    {
        return view('reports.partials.excel.account-wise-excel', [
            'currency' => $this->currency,
            'clients' => $this->clients,
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
        return $this->currency->short;
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
