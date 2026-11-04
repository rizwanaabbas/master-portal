<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportClientProfilesReport implements FromView
{
    public function view(): View
    {
        $clients = Client::whereNotIn('status', ['Draft', 'In Progress', 'AAA'])->orderBy("created_at", "desc")->get();
        return view('pdfs.reports.client-profile-report-excel', compact('clients'));
    }
}
