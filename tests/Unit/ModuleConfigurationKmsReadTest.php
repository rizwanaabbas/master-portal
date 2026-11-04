<?php

namespace Tests\Unit;

use App\Models\ModuleConfiguration;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ModuleConfigurationKmsReadTest extends TestCase
{
    public function test_detects_plain_serialized_without_calling_unserialize(): void
    {
        $model = new ModuleConfiguration();
        $method = new ReflectionMethod($model, 'isPlainSerializedConfiguration');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($model, 'a:0:{}'));
        $this->assertTrue($method->invoke($model, 'a:9999:{'));
        $this->assertFalse($method->invoke($model, 'AQIDBA=='));
        $this->assertTrue($method->invoke($model, 'b:0;'));
    }
}
