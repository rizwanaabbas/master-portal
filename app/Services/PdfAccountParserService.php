<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfAccountParserService
{
    public function parse($input, $isRaw = false): array
    {
        $parser = new Parser();

        // 🔥 Clean input if it's raw content
        if ($isRaw) {
            $input = $this->cleanPdfContent($input);
            $pdf = $parser->parseContent($input);
        } else {
            $pdf = $parser->parseFile($input);
        }

        $text = $pdf->getText();
        
        $data =  [
            'date' => $this->extract('/\d{1,2} \w+ \d{4}/', $text),

            'currency' => $this->extract('/Currency\s+([A-Z]+)/', $text, 1),

            'beneficiary_name' => $this->extract('/Beneficiary name\s+(.+)/', $text, 1),

            'beneficiary_address' => $this->extractBlock(
                'Beneficiary address',
                'Beneficiary account opened date',
                $text
            ),

            'account_opened_date' => $this->extract('/opened date\s+(.+)/', $text, 1),

            'bank_name' => $this->extract('/Beneficiary bank\s+(.+)/', $text, 1),

            //'bank_address' => $this->extract('/Beneficiary bank address\s+(.+)/', $text, 1),
            //'bank_address' => $this->extract('/Beneficiary bank address\s+(.+?)\s+Beneficiary bank SWIFT Code/s', $text, 1),
            'bank_address' => $this->extractBlock(
                'Beneficiary bank address',
                'Beneficiary bank SWIFT Code',
                $text
            ),
            // 'bank_address' => $this->extract(
            //     '/Beneficiary bank address\s+(.+?)(?:\n[A-Z][^:\n]+:|\n\n|$)/s',
            //     $text,
            //     1
            // ),
            'swift_code' => $this->extract('/SWIFT Code\s+([A-Z0-9]+)/', $text, 1),
            'intermediary_bank_swift_code' => $this->extract('/Intermediary Bank SWIFT Code\s+([A-Z0-9]+)/', $text, 1),

            'accounts' => $this->extractAccounts($text),
        ];
 
        return $data;
    }

    /**
     * 🔥 Clean PDF content (handles b"", base64, escaped strings)
     */
    private function cleanPdfContent($content)
    {
        if (!$content) {
            throw new \Exception('Empty PDF content');
        }

        // ✅ Case 1: Python-style b"..."
        if (preg_match('/^b[\'"]/', $content)) {
            $content = substr($content, 2, -1);
            $content = stripcslashes($content);
        }

        // ✅ Case 2: Triple quotes b""" """
        $content = preg_replace('/^b[\'"]{3}/', '', $content);
        $content = preg_replace('/[\'"]{3}$/', '', $content);

        // ✅ Case 3: Base64 encoded PDF
        if (strpos($content, '%PDF') === false) {
            $decoded = base64_decode($content, true);
            if ($decoded !== false && strpos($decoded, '%PDF') !== false) {
                $content = $decoded;
            }
        }

        // ✅ Final validation
        if (strpos($content, '%PDF') === false) {
            throw new \Exception('Invalid PDF format after cleaning');
        }

        return $content;
    }

    /**
     * Extract single value using regex
     */
    private function extract($pattern, $text, $group = 0)
    {
        preg_match($pattern, $text, $matches);

        return isset($matches[$group])
            ? trim($matches[$group])
            : null;
    }

    /**
     * Extract multi-line block (e.g., address)
     */
    private function extractBlock($start, $end, $text)
    {
        preg_match("/$start(.*?)$end/s", $text, $matches);

        return isset($matches[1])
            ? trim(preg_replace('/\s+/', ' ', $matches[1]))
            : null;
    }

    /**
     * Extract single or multiple account entries
     */
   private function extractAccounts($text)
    {
        // Extract block after "Account Numbers" up to "Lorum Finance" or end of text
        preg_match('/Account Numbers(.*?)(Lorum Finance|$)/s', $text, $matches);

        if (!isset($matches[1])) {
            return [];
        }

        $block = trim($matches[1]);
        $block = preg_replace('/\s+/', ' ', $block); // normalize whitespace

        $accounts = [];

        // Match optional type (SWIFT or IBAN), account number, optional IBAN, and routing number
        preg_match_all(
            '/(SWIFT|IBAN|Fedwire|CHIPS|ACH|FEDNOW|RTP)?\s*Account Number\s+([A-Z0-9]+)(?:.*?IBAN\s+([A-Z0-9]+))?.*?Routing Number\s+(\d+)/',
            $block,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $accounts[] = [
                'type' => $match[1] ?? null,
                'account_number' => $match[2] ?? null,
                'iban' => $match[3] ?? null,
                'routing_number' => $match[4] ?? null,
            ];
        }

        // Fallback if no matches
        if (empty($accounts)) {
            preg_match('/Account Number\s+([A-Z0-9]+)/', $block, $acc);
            preg_match('/IBAN\s+([A-Z0-9]+)/', $block, $iban);
            preg_match('/Routing Number\s+(\d+)/', $block, $route);

            if ($acc || $iban || $route) {
                $accounts[] = [
                    'type' => null,
                    'account_number' => $acc[1] ?? null,
                    'iban' => $iban[1] ?? null,
                    'routing_number' => $route[1] ?? null,
                ];
            }
        }

        return $accounts;
    }
}