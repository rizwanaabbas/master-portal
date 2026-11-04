<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsReportSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $currency;
    protected $clients;

    public function __construct($currency, $clients)
    {
        $this->currency = $currency;
        $this->clients = $clients;
    }

    public function view(): View
    {
        return view('reports.partials.excel.clients-report-excel', [
            'currency' => $this->currency,
            'clients' => $this->clients,
        ]);
    }

    public function title(): string
    {
        return $this->currency->short;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            2    => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
            },
        ];
    }
}
