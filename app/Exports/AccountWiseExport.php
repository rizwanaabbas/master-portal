<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountWiseExport implements WithMultipleSheets
{
    use Exportable;

    protected $currencies;
    protected $clients;

    public function __construct($currencies, $clients)
    {
        $this->currencies = $currencies;
        $this->clients = $clients;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->currencies as $currency) {
            $sheets[] = new AccountWiseSheet($currency, $this->clients);
        }
        return $sheets;
    }
}
