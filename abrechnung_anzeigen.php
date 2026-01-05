<?php
$site_name = "Abrechnung";
include("./includes/header.php");

// Correction mode banner
$isCorrectionMode = isset($_GET['correct']) && $_GET['correct'] == '1';
if ($isCorrectionMode) {
    echo '<div style="padding:1em;background:#ffeaea;color:#a00;border:2px solid #d00;margin-bottom:1em;font-weight:bold;border-radius:8px">
        <span style="font-size:1.5em">&#9888;&#65039;</span> Achtung: <strong>Korrekturmodus</strong> aktiv!
        Änderungen werden direkt übernommen.
    </div>';
}

// --------------------
// Get data
// --------------------
require_once __DIR__ . '/dataProvider.php';

$showPaid = isset($_GET['paid']) && $_GET['paid'] == 1 ? 1 : 0;
$dateFilter = $_GET['date'] ?? null;

$data = getAbrechnungData($pdo, $showPaid, $dateFilter);

$structuredData = $data['structuredData'];
$personById = $data['personById'];
$produktById = $data['produktById'];
$today = $data['today'];
?>


<?php foreach ($structuredData as $datum => $persons): ?>
<h3><?= $showPaid ? 'Bezahlte' : 'Offene' ?> Beträge vom <?= conv_date($datum) ?></h3>
<table class="styled-table">
    <thead>
        <tr>
            <th class="col-name">Name</th>
            <?php foreach ($produktById as $produkt): ?>
                <th><?= htmlspecialchars($produkt['name']) ?><br><?= number_format($produkt['preis'], 2) ?> &#8364;</th>
            <?php endforeach; ?>
            <th class="col-total">Betrag</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($persons as $personId => $personData): ?>
        <?php $betrag = 0; ?>
        <tr>
            <td class="name-cell">
                <?= htmlspecialchars($personById[$personId]['nachname']) ?>, 
                <?= htmlspecialchars($personById[$personId]['vorname']) ?>
            </td>
            <?php foreach ($produktById as $produktId => $produkt):
                $anzahl = $personData['produkte'][$produktId] ?? 0;
                $betrag += $anzahl * $produkt['preis']; ?>
                <td class="col-prod cell-clickable">
                    <?php if ($anzahl != 0): ?><span class="anzahl"><?= $anzahl ?></span><?php endif; ?>
                    <?php if (!$showPaid): ?>
                      <form action="eintrag_speichern.php" method="post" class="cell-click-form" onsubmit="return confirmEintragNeu(this)">
                          <input type="hidden" name="action" value="<?= $isCorrectionMode ? 'korrektur' : 'verkauf' ?>">
                          <input type="hidden" name="Datum" value="<?= $datum ?>">
                          <input type="hidden" name="Produkt_ID" value="<?= $produktId ?>">
                          <input type="hidden" name="Person_ID" value="<?= $personId ?>">
                          <input type="hidden" name="Menge" value="1">
                          <input type="hidden" name="Verkaufspreis" value="<?= $produkt['preis'] ?>">
                      </form>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
            <td class="col-total"><?= number_format($betrag, 2) ?> &#8364;</td>
            <td class="col-pay">
                <?php if (!$showPaid): ?>
                <form action="eintrag_speichern.php" method="post" class="formBezahlen" onsubmit="return confirmVerkauf(event)">
                    <input type="hidden" name="action" value="bezahlen">
                    <input type="hidden" name="date" value="<?= $datum ?>">
                    <input type="hidden" name="personId" value="<?= $personId ?>">
                    <input type="image" src="./includes/euro.svg" width="30" alt="Bezahlen" title="Bezahlen">
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <!-- New entry row: only allows one per product/person at a time. -->
        <?php
        $allPersonIds = array_keys($personById);
        $personenMitEintrag = array_keys($persons);
        $personenOhneEintrag = array_diff($allPersonIds, $personenMitEintrag);
        if (isset($_GET["date"]) && $_GET["date"]==="today"):
        ?>
        <tr class="new-entry-row">
            <td class="col-name">
                <select name="Person_ID" id="person-select-<?= $datum ?>" required>
                    <option value="">Person wählen</option>
                    <?php foreach ($personenOhneEintrag as $id):
                        $p = $personById[$id]; ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($p['nachname'] . ', ' . $p['vorname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <?php foreach ($produktById as $produktId => $produkt): ?>
            <td class="col-prod cell-clickable">
                <form action="eintrag_speichern.php" method="post" class="cell-click-form" onsubmit="return confirmEintragNeu(this)">
                    <input type="hidden" name="action" value="verkauf">
                    <input type="hidden" name="Datum" value="<?= $datum ?>">
                    <input type="hidden" name="Produkt_ID" value="<?= $produktId ?>">
                    <!-- Person_ID gets set by JS from select on submit -->
                    <input type="hidden" name="Menge" value="1">
                    <input type="hidden" name="Verkaufspreis" value="<?= $produkt['preis'] ?>">
                </form>
            </td>
            <?php endforeach; ?>
            <td></td><td></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endforeach; ?>

<?php if (isset($_GET["date"]) && $_GET["date"]==="today"): ?>
<!-- Add Person Button -->
<div class="add-person-container">
  <button id="addPersonBtn" class="add-person-btn" title="Neue Person hinzufügen">➕ Person</button>
</div>
<?php endif; ?>


<!------- TEMPLATES ------->
<!-- Hidden Popup for New Person -->
<div id="addPersonPopup" class="add-person-popup" style="display:none;">
  <div class="add-person-content">
    <h4>Neue Person hinzufügen</h4>
    <input type="text" id="vornameInput" placeholder="Vorname">
    <input type="text" id="nachnameInput" placeholder="Nachname">
    <div class="popup-actions">
      <button id="confirmAddPerson" class="confirm-btn">Bestätigen</button>
      <button id="cancelAddPerson" class="cancel-btn">Abbrechen</button>
    </div>
  </div>
</div>

<!------- Modal for confirmation  ------->
<div id="app-modal" class="modal hidden">
  <div class="modal-backdrop"></div>
  <div class="modal-box" role="dialog" aria-modal="true">
    <h2 class="modal-title"></h2>
    <h2 class="modal-content"></h2>
    <div class="modal-actions">
      <button class="modal-btn-ok" type="button" data-action="ok">OK</button>
      <button class="modal-btn-cncl" type="button" data-action="cancel">Abbrechen</button>
    </div>
  </div>
</div>


<script>
  window.APP_DATA = {
    produktNameById: <?= json_encode(array_column($produktById, 'name', 'produkt_id')) ?>,
    personNameById: <?= json_encode(array_map(
      fn($p) => $p['vorname'] . ' ' . $p['nachname'],
      $personById
    )) ?>,
    isCorrectionMode: <?= $isCorrectionMode ? 'true' : 'false' ?>
  };
</script>

<script type ="module" src="js/app.js"></script>


<?php include("./includes/footer.php"); ?>

