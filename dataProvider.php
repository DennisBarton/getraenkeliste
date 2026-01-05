<?php
// ============================================================
// dataProvider.php
// Fetch and structure Abrechnung data
// ============================================================

function getAbrechnungData(PDO $pdo, int $showPaid = 0, ?string $dateFilter = null): array {
    $queries = include(__DIR__ . '/queries.php');

    $today = date('Y-m-d');
    $dateClause = " ";
    $date = $today;

    if ($dateFilter) {
        if ($dateFilter === 'today') {
            $dateClause = " AND date='$today' ";
        }
    } else {
        $dateClause = " AND NOT date='$today' ";
    }
    if ($showPaid) {
        $dateClause = " ";
    }

    // --------------------
    // Fetch data
    // --------------------
    $stmt = $pdo->prepare(str_replace(':dateClause', $dateClause, $queries['entries']));
    $stmt->execute([':showPaid' => $showPaid]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $persons = $pdo->query($queries['persons'])->fetchAll(PDO::FETCH_ASSOC);
    $products = $pdo->query($queries['products'])->fetchAll(PDO::FETCH_ASSOC);

    // --------------------
    // Index for easy lookup
    // --------------------
    $personById = array_column($persons, null, 'person_id');
    $produktById = array_column($products, null, 'produkt_id');

    // --------------------
    // Structure entries by date and person
    // --------------------
    $structuredData = [];
    foreach ($entries as $row) {
        $d = $row['date'];
        $person = $row['person'];
        $produkt = $row['produkt'];
        $structuredData[$d][$person]['produkte'][$produkt] = $row['sum'];
        $structuredData[$d][$person]['bezahlt'] = $row['bezahlt'];
    }

    // Ensure today exists if filtering today
    if ($dateFilter === 'today' && !isset($structuredData[$today])) {
        $structuredData[$today] = [];
    }

    return [
        'structuredData' => $structuredData,
        'personById' => $personById,
        'produktById' => $produktById,
        'today' => $today
    ];
}
