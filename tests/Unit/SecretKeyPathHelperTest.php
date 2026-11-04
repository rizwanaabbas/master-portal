<?php

namespace Tests\Unit;

use App\Helpers\SecretKeyPathHelper;
use Tests\TestCase;

class SecretKeyPathHelperTest extends TestCase
{
    public function test_resolve_absolute_path_unchanged(): void
    {
        $this->assertSame('/etc/keys/public.pem', SecretKeyPathHelper::resolve('/etc/keys/public.pem'));
    }

    public function test_resolve_relative_path_against_project_root(): void
    {
        $this->assertSame(
            base_path('storage/app/keys/test.pem'),
            SecretKeyPathHelper::resolve('storage/app/keys/test.pem')
        );
    }

    public function test_read_prefers_inline_key_over_file(): void
    {
        $pem = "-----BEGIN PUBLIC KEY-----\ntest\n-----END PUBLIC KEY-----";

        $this->assertSame($pem, SecretKeyPathHelper::read('/missing/path.pem', $pem));
    }

    public function test_read_returns_null_when_path_missing_and_no_inline(): void
    {
        $this->assertNull(SecretKeyPathHelper::read('/path/does/not/exist.pem'));
    }
}
