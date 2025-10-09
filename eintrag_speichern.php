<?php
include("./includes/connect.php");

$action = $_POST['action'] ?? null;
$personId = $_POST['Person_ID'] ?? $_POST['personId'] ?? null;
$date = $_POST['Datum'] ?? $_POST['date'] ?? null;

if (!$action || !$personId || !$date) {
    die("Fehler: Ungültige Eingabe");
}

// Handle new person creation
if ($personId === "__new__") {
    $vorname = trim($_POST['neueVorname'] ?? '');
    $nachname = trim($_POST['neueNachname'] ?? '');
    if ($vorname === '' || $nachname === '') {
        die("Fehler: Vorname oder Nachname fehlt");
    }
    $stmt = $pdo->prepare("INSERT INTO db_personen (vorname, nachname) VALUES (?, ?)");
    $stmt->execute([$vorname, $nachname]);
    $personId = $pdo->lastInsertId();
}

try {
    $pdo->beginTransaction();

    if ($action === "verkauf") {
        // ========================
        // 💰 HANDLE VERKAUF (NEW SALE)
        // ========================
        $stmt = $pdo->prepare("
            INSERT INTO db_eintrag (person, date, produkt, anzahl, bezahlt)
            VALUES (:person, :date, :produkt, :anzahl, 0)
            ON DUPLICATE KEY UPDATE anzahl = anzahl + VALUES(anzahl)
        ");

        if (isset($_POST['menge']) && is_array($_POST['menge'])) {
            foreach ($_POST['menge'] as $produktId => $anzahl) {
                $anzahl = (int)$anzahl;
                if ($anzahl > 0) {
                    $stmt->execute([
                        'person' => $personId,
                        'date' => $date,
                        'produkt' => $produktId,
                        'anzahl' => $anzahl
                    ]);
                }
            }
        } elseif (isset($_POST['Produkt_ID'], $_POST['Menge'])) {
            $produktId = (int)$_POST['Produkt_ID'];
            $anzahl = (int)$_POST['Menge'];
            if ($anzahl > 0) {
                $stmt->execute([
                    'person' => $personId,
                    'date' => $date,
                    'produkt' => $produktId,
                    'anzahl' => $anzahl
                ]);
            }
        } else {
            throw new Exception("Keine gültigen Mengenangaben gefunden.");
        }

    } elseif ($action === "bezahlen") {
        // ==========================
        // ✅ HANDLE PAYMENT
        // ==========================

        // Step 1: get all unpaid entries
        $stmtUnpaid = $pdo->prepare("
            SELECT produkt, anzahl
            FROM db_eintrag
            WHERE person = :person AND date = :date AND bezahlt = 0
        ");
        $stmtUnpaid->execute(['person' => $personId, 'date' => $date]);
        $unpaidEntries = $stmtUnpaid->fetchAll(PDO::FETCH_ASSOC);

        // Prepare reusable statements
        $updatePaid = $pdo->prepare("
            UPDATE db_eintrag
            SET anzahl = anzahl + :anzahl
            WHERE person = :person AND date = :date AND produkt = :produkt AND bezahlt = 1
        ");
        $insertPaid = $pdo->prepare("
            INSERT INTO db_eintrag (person, date, produkt, anzahl, bezahlt)
            VALUES (:person, :date, :produkt, :anzahl, 1)
        ");
        $deleteUnpaid = $pdo->prepare("
            DELETE FROM db_eintrag
            WHERE person = :person AND date = :date AND produkt = :produkt AND bezahlt = 0
        ");

        foreach ($unpaidEntries as $entry) {
            $produkt = $entry['produkt'];
            $anzahl = $entry['anzahl'];

            $updatePaid->execute([
                'person' => $personId,
                'date' => $date,
                'produkt' => $produkt,
                'anzahl' => $anzahl
            ]);

            if ($updatePaid->rowCount() === 0) {
                $insertPaid->execute([
                    'person' => $personId,
                    'date' => $date,
                    'produkt' => $produkt,
                    'anzahl' => $anzahl
                ]);
            }

            $deleteUnpaid->execute([
                'person' => $personId,
                'date' => $date,
                'produkt' => $produkt
            ]);
        }

    } else {
        throw new Exception("Ungültige Aktion: $action");
    }

    $pdo->commit();
    header("Location: abrechnung_anzeigen.php?date=today");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Fehler beim Speichern: " . htmlspecialchars($e->getMessage()));
}
