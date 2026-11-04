<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Tests\TestCase;

class SweepMathTest extends TestCase
{
    public function test_for_transaction_amount_caps_float_dust_above_available(): void
    {
        $available = '0.027';
        $amount = '0.027000000000000007';

        $this->assertSame(0, SweepMath::comp(SweepMath::forTransactionAmount($amount, $available), $available, 18));
    }

    public function test_for_transaction_amount_snaps_near_full_sweep_to_available(): void
    {
        $available = '0.22';
        $amount = '0.220000000000000001';

        $this->assertSame(0, SweepMath::comp(SweepMath::forTransactionAmount($amount, $available), $available, 18));
    }

    public function test_trim_excess_decimal_digits_on_float_string(): void
    {
        $this->assertSame(0, SweepMath::comp(
            SweepMath::trimExcessDecimalDigits('0.027000000000000007'),
            '0.027',
            18
        ));
    }

    public function test_from_api_decimal_strips_float_tail_for_comparisons(): void
    {
        $normalized = SweepMath::fromApiDecimal(0.027000000000000007);

        $this->assertSame(0, SweepMath::comp($normalized, '0.027', 18));
    }

    public function test_sanitize_when_amount_and_available_both_carry_float_dust(): void
    {
        $dusty = '0.025000000000000001';

        $this->assertSame('0.025', SweepMath::forTransactionAmount($dusty, $dusty));
    }

    public function test_sanitize_transaction_amount_exact_staging_case(): void
    {
        $this->assertSame('0.025', SweepMath::sanitizeTransactionAmount('0.025000000000000001'));
    }
}
