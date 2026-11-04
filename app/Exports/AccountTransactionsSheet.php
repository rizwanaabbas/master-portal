<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountTransactionsSheet implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $transactions;
    protected $nature;

    public function __construct($transactions, $nature)
    {
        $this->transactions = $transactions;
        $this->nature = $nature;
    }

    public function view(): View
    {
        return view('reports.partials.excel.account-transactions-report-excel', [
            'transactions' => $this->transactions
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
        return $this->nature;;
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
