<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportTransaction implements ToArray
{
    use Importable;

    public function array(array $row)
    {
        return $row;
        return [
            "code" => $row[0],
            "member_since" => $row[1],
            "client_name" => $row[2],
            "type" => $row[3],
            "contact_name" => $row[4],
            "contact_email" => $row[5],
            "contact_number" => $row[5],
            "eur" => $row[6],
            "usd" => $row[7],
            "cny" => $row[8],
            "aed" => $row[9]
        ];
    }
}
