<?php
use PHPUnit\Framework\TestCase;

final class AbrechnungTest extends TestCase {
    public function testBasicDateFormat() {
        $today = date('Y-m-d');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $today);
    }
}
