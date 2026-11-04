<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyAllClientsWiseExport implements WithMultipleSheets
{
    use Exportable;

    protected $currencies;
    protected $data;

    public function __construct($currencies, $data)
    {
        $this->currencies = $currencies;
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->currencies as $currency) {
            $filteredData = [];
            foreach ($this->data as $rows) {
                foreach ($rows as $row) {
                    if (isset($row[$currency->account_currency])) {
                        $filteredData[] = $row;
                    }
                }
            }

            if (count($filteredData) > 0) {
                $sheets[] = new DailyAllClientsWiseSheet($currency, $filteredData);
            }
        }
        return $sheets;
    }
}
