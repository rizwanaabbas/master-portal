<?php

namespace App\Exports;

use App\Models\Currency;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountTransactionsReportExport6Months implements WithMultipleSheets
{
    use Exportable;

    protected $transactions;
    protected $transactionType;

    public function __construct($transactions, $transactionType)
    {
        $this->transactions = $transactions;
        $this->transactionType = $transactionType;
    }
    public function sheets(): array
    {
        $sheets = [];
        $currencies = Currency::where('is_active', 1)->where('name', '<>', 'Fixed Deposit')->get();

        foreach ($currencies as $currency) {
            $filteredTransactions = $this->transactions->where('currency_id', $currency->id);
            $sheets[] = new AccountTransactionsSheet6Months($filteredTransactions, $currency->short, $this->transactionType);
        }

        return $sheets;
    }
}
