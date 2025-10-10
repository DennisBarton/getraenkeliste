
<?php
use PHPUnit\Framework\TestCase;

final class AbrechnungTest extends TestCase {

    public function testBasicDateFormat() {
        $today = date('Y-m-d');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $today);
    }

    public function testStructuredDataExample() {
        $entries = [
            ['date' => '2025-10-10', 'person' => 1, 'produkt' => 2, 'sum' => 3, 'bezahlt' => 0]
        ];
        $structuredData = [];

        foreach ($entries as $row) {
            $date = $row['date'];
            $pid = $row['person'];
            $prod = $row['produkt'];
            if (!isset($structuredData[$date])) $structuredData[$date] = [];
            if (!isset($structuredData[$date][$pid])) {
                $structuredData[$date][$pid] = [
                    'produkte' => [],
                    'bezahlt' => $row['bezahlt'],
                ];
            }
            $structuredData[$date][$pid]['produkte'][$prod] = $row['sum'];
        }

        $this->assertArrayHasKey('2025-10-10', $structuredData);
        $this->assertEquals(3, $structuredData['2025-10-10'][1]['produkte'][2]);
    }
}
