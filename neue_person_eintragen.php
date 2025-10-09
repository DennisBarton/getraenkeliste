<?php
// DO NOT include any headers or footers here!
require_once('./includes/connect.php'); // Only DB connection

header('Content-Type: application/json');
if (ob_get_level()) {
    ob_clean();
}

$vorname = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');

if ($vorname === '' || $nachname === '') {
  echo json_encode(['success' => false, 'message' => 'Ungültige Eingabe']);
  exit;
}

try {
  $stmt = $pdo->prepare("INSERT INTO db_personen (vorname, nachname) VALUES (?, ?)");
  $stmt->execute([$vorname, $nachname]);
  $person_id = $pdo->lastInsertId();

  echo json_encode(['success' => true, 'person_id' => $person_id]);
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
