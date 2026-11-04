<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;

class ImportClient implements ToArray
{
    use Importable;

    public function mapping(): array
    {
        return [
            'code'  => 'A1',
            'member_since' => 'B1',
            'client_name' => 'C1',
            'type' => 'D1',
            'contact_name' => 'E1',
            'contact_email' => 'F1',
            'contact_number' => 'G1',
            'eur' => 'H1',
            'usd' => 'I1',
            'cny' => 'J1',
            'aed' => 'K1',
        ];
    }

    public function array(array $row)
    {
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
