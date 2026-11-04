<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClientWiseExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{

    protected $currencies;
    protected $users;
    protected $clients;

    public function __construct($currencies, $users, $clients)
    {
        $this->currencies = $currencies;
        $this->users = $users;
        $this->clients = $clients;
    }

    public function view(): View
    {
        return view('reports.partials.excel.user-client-wise-excel', [
            'currencies' => $this->currencies,
            'users' => $this->users,
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
        return 'Sheet1';
    }
}
