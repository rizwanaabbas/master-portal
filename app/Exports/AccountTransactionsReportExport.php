<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountTransactionsReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $accounts;
    protected $transactions;

    public function __construct($accounts, $transactions)
    {
        $this->accounts = $accounts;
        $this->transactions = $transactions;
    }
    public function sheets(): array
    {
        $sheets = [];

        $transactionNatures = $this->transactions->pluck('transaction_nature')->unique();

        foreach ($transactionNatures as $nature) {
            $filteredTransactions = $this->transactions->where('transaction_nature', $nature);
            $sheets[] = new AccountTransactionsSheet($filteredTransactions, $nature);
        }

        return $sheets;
    }
}
