<?php
require_once('./includes/connect.php'); // Only DB connection

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$person_id = intval($data["person_id"] ?? 0);
$datum = $data["datum"] ?? null;
$produkt_id = intval($data["produkt_id"] ?? 0);
$anzahl = intval($data["anzahl"] ?? -1);

if ($person_id <= 0 || !$datum || $produkt_id <= 0 || $anzahl < 0) {
  echo json_encode(["status" => "error", "message" => "Ungültige Eingabe"]);
  exit;
}

try {
  $pdo = new PDO("mysql:host=localhost;dbname=deine_datenbank", "username", "passwort", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);

  // Check if entry exists
  $stmt = $pdo->prepare("
    SELECT id FROM db_eintrag 
    WHERE person = ? AND date = ? AND produkt = ? AND bezahlt = 0
  ");
  $stmt->execute([$person_id, $datum, $produkt_id]);

  if ($row = $stmt->fetch()) {
    // Update existing entry
    $update = $pdo->prepare("
      UPDATE db_eintrag 
      SET anzahl = ? 
      WHERE person = ? AND date = ? AND produkt = ? AND bezahlt = 0
    ");
    $update->execute([$anzahl, $person_id, $datum, $produkt_id]);
  } else {
    // Insert if not found
    $insert = $pdo->prepare("
      INSERT INTO db_eintrag (person, date, produkt, anzahl, bezahlt)
      VALUES (?, ?, ?, ?, 0)
    ");
    $insert->execute([$person_id, $datum, $produkt_id, $anzahl]);
  }

  echo json_encode(["status" => "ok"]);
} catch (PDOException $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
