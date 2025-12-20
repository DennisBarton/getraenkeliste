<?php
// ============================================================
// Abrechnung Anzeigen - Cleaned Version (Single-value submissions only)
// Popup is placed next to the clicked cell and cell is highlighted
// ============================================================

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
// Date filter setup
// --------------------
$today = get_today();
$dateClause = " ";
if (isset($_GET["date"])) {
    if ($_GET["date"] === 'today') {
        $date = $today;
        $dateClause = " AND date='$date' ";
    }
} else {
    $date = $today;
    $dateClause = " AND NOT date='$date' ";
}
$showPaid = isset($_GET['paid']) && $_GET['paid'] == 1 ? 1 : 0;
if ($showPaid) $dateClause = " ";

// --------------------
// Fetch entries, persons, and products
// --------------------
$sql = "
    SELECT e.date, e.person, e.produkt, SUM(e.anzahl) AS sum, e.bezahlt, p.nachname, p.vorname
    FROM db_eintrag AS e
    JOIN db_personen AS p ON e.person = p.person_id
    WHERE bezahlt = $showPaid $dateClause
    GROUP BY e.date, e.person, e.produkt
    ORDER BY e.date DESC, p.nachname ASC, p.vorname ASC;

    SELECT person_id, nachname, vorname FROM db_personen;

    SELECT produkt_id, name, preis FROM db_produkte_standard;
";
$data_query = $pdo->query($sql);
$data = [];
do {
    $data[] = $data_query->fetchAll(PDO::FETCH_ASSOC);
} while ($data_query->nextRowset());

$personById  = array_column($data[1], null, 'person_id');
$produktById = array_column($data[2], null, 'produkt_id');

// --------------------
// Structure entries by date and person
// --------------------
$structuredData = [];
foreach ($data[0] as $row) {
    $date = $row['date'];
    $person = $row['person'];
    $produkt = $row['produkt'];
    $structuredData[$date][$person]['produkte'][$produkt] = $row['sum'];
    $structuredData[$date][$person]['bezahlt'] = $row['bezahlt'];
}
if (isset($_GET['date']) && $_GET['date'] === 'today' && !isset($structuredData[$today])) {
    $structuredData[$today] = [];
}
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
            <td class="col-name">
                <?= htmlspecialchars($personById[$personId]['vorname']) ?>
                <?= htmlspecialchars($personById[$personId]['nachname']) ?>
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
                        <option value="<?= $id ?>"><?= htmlspecialchars($p['vorname'] . ' ' . $p['nachname']) ?></option>
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

<script>
const produktNameById = <?= json_encode(array_column($produktById, 'name', 'produkt_id')) ?>;
const personNameById = <?= json_encode(array_map(fn($p) => $p['vorname'] . ' ' . $p['nachname'], $personById)) ?>;
window.isCorrectionMode = <?= $isCorrectionMode ? "true" : "false" ?>;

// Per-entry confirm dialog
function confirmEintragNeu(form) {
  const datum = form.querySelector("input[name='Datum']").value || "Unbekannt";
  let personId = form.querySelector("input[name='Person_ID']")?.value || null;
  if (!personId) {
    // Try select in new-entry row
    const row = form.closest("tr");
    const sel = row ? row.querySelector("select[name='Person_ID']") : null;
    if (sel) personId = sel.value;
  }
  if (!personId) {
    alert("Bitte eine Person auswählen.");
    return false;
  }
  // Must have exactly one product and one quantity
  const mengeInput = form.querySelector("input[name='Menge']");
  const produktIdInput = form.querySelector("input[name='Produkt_ID']");
  if (!mengeInput || !produktIdInput) {
    alert("Produkt oder Menge fehlt.");
    return false;
  }
  const menge = parseInt(mengeInput.value, 10);
  const pid = produktIdInput.value;
  const pname = produktNameById[pid] || "Unbekannt";
  const personLabel = personNameById[personId] || "Unbekannt";
  const confirmHead = window.isCorrectionMode ? "Eintrag abziehen:" : "Eintrag hinzufügen:";
  const confirmMessage =
    confirmHead + "\n\n" +
    "Person: " + personLabel + "\n" +
    "Datum: " + datum + "\n" +
    "Produkte und Mengen:\n" + pname + ": " + menge + "\n";
  // Attach resolved Person_ID to the form if coming from the select
  let personInput = form.querySelector("input[name='Person_ID']");
  if (!personInput) {
    personInput = document.createElement("input");
    personInput.type = "hidden";
    personInput.name = "Person_ID";
    form.appendChild(personInput);
  }
  personInput.value = personId;
  return confirm(confirmMessage);
}

// Confirm dialog for payment (unchanged)
function confirmVerkauf(event) {
  event.preventDefault();
  const form = event.target;
  const row = form.closest("tr");
  const name = row.cells[0].textContent.trim();
  const date = form.querySelector("input[name='date']")?.value || '';
  const amount = row.cells[row.cells.length - 2].textContent.trim();
  if (confirm(`Wollen Sie diesen Eintrag bezahlen:\n\nName:\t${name}\nDatum:\t${date}\nBetrag:\t${amount}`)) {
    form.submit();
  }
}

// Popup placement: places next to the target cell and fits in the viewport
function placePopup(popup, rect) {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const popupW = popup.offsetWidth || 180;
  const popupH = popup.offsetHeight || 200;
  const margin = 8;
  let left = rect.left + rect.width / 2 - popupW / 2;
  let top = rect.bottom + 10;
  if (top + popupH > vh - margin) top = rect.top - popupH - 10;
  if (top < margin) top = margin;
  if (left < margin) left = margin;
  if (left + popupW > vw - margin) left = vw - popupW - margin;
  popup.style.left = `${left}px`;
  popup.style.top = `${top}px`;
}

document.addEventListener("DOMContentLoaded", () => {
  let activePopup = null;
  let activeCell = null;

  function closePopup() {
    if (!activePopup) return;
    activePopup.remove();
    if (activeCell) activeCell.classList.remove('active-keypad-cell');
    activePopup = null;
    activeCell = null;
  }

  const table = document.querySelector(".styled-table");
  const clickContainer = table || document.body;

  clickContainer.addEventListener("click", function(e) {
    if (e.target.closest("select, input, button, a, label, textarea")) return;
    const td = e.target.closest("td");
    if (!td) return;
    let form = td.querySelector("form");
    if (!form) return;

    e.stopPropagation();
    e.preventDefault();

    if (activeCell === td) {
      closePopup();
      return;
    }
    closePopup();

    td.classList.add('active-keypad-cell');
    activeCell = td;

    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <div class="qty-display" aria-live="polite">0</div>
      <div class="qty-grid" role="grid">
        ${[1,2,3,4,5,6,7,8,9].map(n => `<button type="button" class="num-btn" data-num="${n}">${n}</button>`).join('')}
        <button type="button" class="num-btn" data-action="clear">C</button>
        <button type="button" class="num-btn" data-num="0">0</button>
        <button type="button" class="num-btn" data-action="ok">${window.isCorrectionMode ? '-' : '+'}</button>
      </div>
    `;
    document.body.appendChild(popup);

    popup.style.position = "fixed";
    popup.style.zIndex = 9999;

    // POPUP PLACEMENT
    placePopup(popup, td.getBoundingClientRect());

    activePopup = popup;

    popup.addEventListener("click", (ev) => ev.stopPropagation());

    let mengeField = form.querySelector("input[name='Menge']");
    if (!mengeField) {
      mengeField = document.createElement("input");
      mengeField.type = "hidden";
      mengeField.name = "Menge";
      mengeField.value = "";
      form.appendChild(mengeField);
    }
    const display = popup.querySelector(".qty-display");

    popup.addEventListener("click", (ev) => {
      const btn = ev.target.closest(".num-btn");
      if (!btn) return;
      const num = btn.dataset.num;
      const action = btn.dataset.action;

      if (num !== undefined) {
        display.textContent = display.textContent === "0" ? num : display.textContent + num;
      } else if (action === "clear") {
        display.textContent = "0";
      } else if (action === "ok") {
        let qty = parseInt(display.textContent, 10);
        if (isNaN(qty) || qty <= 0) {
          closePopup();
          return;
        }
        mengeField.value = qty;
        if (!confirmEintragNeu(form)) {
          closePopup();
          return;
        }
        closePopup();
        form.submit();
      }
    });
  });

  document.addEventListener("click", () => {
    if (!activePopup) return;
    closePopup();
  });
});

// Add Person popup, unchanged
document.addEventListener("DOMContentLoaded", () => {
  const addBtn = document.getElementById("addPersonBtn");
  const popup = document.getElementById("addPersonPopup");
  const confirmBtn = document.getElementById("confirmAddPerson");
  const cancelBtn = document.getElementById("cancelAddPerson");
  const vornameInput = document.getElementById("vornameInput");
  const nachnameInput = document.getElementById("nachnameInput");

  addBtn.addEventListener("click", () => {
    popup.style.display = "flex";
    vornameInput.focus();
  });

  cancelBtn.addEventListener("click", () => {
    popup.style.display = "none";
    vornameInput.value = "";
    nachnameInput.value = "";
  });

  confirmBtn.addEventListener("click", () => {
    const vor = vornameInput.value.trim();
    const nach = nachnameInput.value.trim();
    if (!vor || !nach) {
      alert("Bitte Vorname und Nachname eingeben.");
      return;
    }

    fetch("neue_person_eintragen.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `vorname=${encodeURIComponent(vor)}&nachname=${encodeURIComponent(nach)}`
    })
    .then(resp => resp.json())
    .then(data => {
      if (data.success && data.person_id) {
        const select = document.querySelector(
          ".new-entry-row select[name='Person_ID']"
        );
        if (select) {
          const opt = document.createElement("option");
          opt.value = data.person_id;
          opt.textContent = `${vor} ${nach}`;
          opt.selected = true;
          select.appendChild(opt);
        }
        popup.style.display = "none";
        vornameInput.value = "";
        nachnameInput.value = "";
      } else {
        alert("Fehler beim Hinzufügen der Person.");
      }
    })
    .catch(err => {
      console.error("Fehler:", err);
      alert("Netzwerkfehler.");
    });
  });

  popup.addEventListener("click", e => {
    if (e.target === popup) cancelBtn.click();
  });
});
</script>
<?php include("./includes/footer.php"); ?>

