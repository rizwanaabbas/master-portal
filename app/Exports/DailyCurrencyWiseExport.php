<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyCurrencyWiseExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->data as $date => $values) {
            $sheets[] = new DailyCurrencyWiseSheet($date, $values);
        }
        return $sheets;
    }
}
