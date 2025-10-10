<?php
// ============================================================
// Abrechnung Anzeigen - Cleaned Version
// Generates overview of open/paid amounts with popup quantity entry
// ============================================================

$site_name = "Abrechnung";
include("./includes/header.php");

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
    SELECT
      e.date,
      e.person,
      e.produkt,
      SUM(e.anzahl) AS sum,
      e.bezahlt,
      p.nachname,
      p.vorname
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
                    <?php if ($anzahl > 0): ?><span class="anzahl"><?= $anzahl ?></span><?php endif; ?>
                    <?php if (!$showPaid): ?>
                      <form action="eintrag_speichern.php" method="post" class="cell-click-form" onsubmit="return confirmEintragNeu(this)">
                          <input type="hidden" name="action" value="verkauf">
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

        <!-- New entry row -->
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
                          <input type="hidden" name="Person_ID" value="<?= $personId ?>">
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
/* -----------------------------
   Popup + confirmation logic
   Click the whole product cell (td.cell-clickable) to open popup.
   ----------------------------- */

/* These are injected by PHP in your page — keep them as-is */
const produktNameById = <?= json_encode(array_column($produktById, 'name', 'produkt_id')) ?>;
const personNameById = <?= json_encode(array_map(fn($p) => $p['vorname'] . ' ' . $p['nachname'], $personById)) ?>;

/* ---------- Confirmation helper ---------- */
function confirmEintragNeu(form) {
  const datum = form.querySelector("input[name='Datum']")?.value || "Unbekannt";

  // prefer hidden Person_ID in the form; if not present try a select in the same row
  let personId = form.querySelector("input[name='Person_ID']")?.value || null;
  if (!personId) {
    const row = form.closest("tr");
    const sel = row ? row.querySelector("select[name='Person_ID']") : null;
    if (sel) personId = sel.value;
  }
  if (!personId) {
    alert("Bitte eine Person auswählen.");
    return false;
  }

  // gather products + quantities
  const produktDetails = [];

  // case 1: forms using multiple menge[...] fields (rare here)
  const mengeInputs = form.querySelectorAll("input[name^='menge']");
  if (mengeInputs.length > 0) {
    mengeInputs.forEach(input => {
      const menge = parseInt(input.value, 10);
      if (menge > 0) {
        const m = input.name.match(/\[(\d+)\]/);
        if (m) {
          const pid = m[1];
          const pname = produktNameById[pid] || "Unbekannt";
          produktDetails.push(`${pname}: ${menge}`);
        }
      }
    });
  } else {
    // case 2: single-cell form with input[name='Menge'] and input[name='Produkt_ID']
    const mengeInput = form.querySelector("input[name='Menge']");
    const produktIdInput = form.querySelector("input[name='Produkt_ID']");
    if (!mengeInput || !produktIdInput) {
      alert("Produkt oder Menge fehlt.");
      return false;
    }
    const menge = parseInt(mengeInput.value, 10);
    if (isNaN(menge) || menge <= 0) {
      alert("Bitte eine Menge größer als 0 eingeben.");
      return false;
    }
    const pid = produktIdInput.value;
    const pname = produktNameById[pid] || "Unbekannt";
    produktDetails.push(`${pname}: ${menge}`);
  }

  if (produktDetails.length === 0) {
    alert("Bitte mindestens eine Menge größer als 0 eingeben.");
    return false;
  }

  const personLabel = personNameById[personId] || "Unbekannt";
  const confirmMessage =
    "Eintrag hinzufügen:\n\n" +
    "Person: " + personLabel + "\n" +
    "Datum: " + datum + "\n" +
    "Produkte und Mengen:\n" + produktDetails.join("\n") + "\n";

  return confirm(confirmMessage);
}

/* ---------- Payment confirm (unchanged) ---------- */
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

/* Numeric keypad popup — robust & stable
   - opens by clicking the table cell (only cells that belong to forms)
   - stops click propagation so document-level close handlers don't race
   - supports single-product forms (input[name="Menge"]) and new-entry multi-product forms (menge[ID])
   - copies current row select[name="Person_ID"] into the form before confirm
*/
document.addEventListener("DOMContentLoaded", () => {
  let activePopup = null;
  let activeCell = null;

  // Helper to close active popup
  function closePopup() {
    if (!activePopup) return;
    activePopup.remove();
    activePopup = null;
    activeCell = null;
  }

  // Open popup when clicking inside the table — delegate to the table element
  const table = document.querySelector(".styled-table");
  const clickContainer = table || document.body;

  clickContainer.addEventListener("click", function tableClickHandler(e) {
    // Ignore clicks on interactive controls (selects, inputs, buttons, anchors)
    if (e.target.closest("select, input, button, a, label, textarea")) return;

    const td = e.target.closest("td");
    if (!td) return;

    // Find the form for this cell: either inside the td or in parent tr (new-entry row)
    let form = td.querySelector("form") || td.closest("tr")?.querySelector("form");
    if (!form) return;

    // Only open if the form can accept a quantity (either has 'Menge' or accepts menge[...] fields)
    const acceptsSingle = !!form.querySelector("input[name='Menge']");
    const acceptsArray  = !!form.querySelector("input[name^='menge']") || td.dataset.newentry === "true" || td.closest("tr")?.classList.contains("new-entry-row");
    if (!acceptsSingle && !acceptsArray) return;

    // Stop propagation so document handler won't immediately close the popup
    e.stopPropagation();
    e.preventDefault();

    // If clicked same cell that's already open -> toggle close
    if (activeCell === td) {
      closePopup();
      return;
    }

    // Close any previous popup
    closePopup();

    // create keypad popup element
    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <div class="qty-display" aria-live="polite">0</div>
      <div class="qty-grid" role="grid">
        ${[1,2,3,4,5,6,7,8,9].map(n => `<button type="button" class="num-btn" data-num="${n}">${n}</button>`).join('')}
        <button type="button" class="num-btn" data-action="clear">C</button>
        <button type="button" class="num-btn" data-num="0">0</button>
        <button type="button" class="num-btn" data-action="ok">+</button>
      </div>
    `;
    document.body.appendChild(popup);

    // Position popup: try above the cell; clamp to viewport
    const rect = td.getBoundingClientRect();
    // ensure we measure after element attached
    const pRect = popup.getBoundingClientRect();
    const width = Math.max(pRect.width, 160);
    const height = pRect.height;
    const margin = 8;
    let left = rect.left + (rect.width / 2) - (width / 2);
    let top  = rect.top - height - 10;

    // If not enough space above, show below
    if (top < margin) {
      top = rect.bottom + 10;
    }
    // Clamp to viewport horizontally
    if (left < margin) left = margin;
    if (left + width > window.innerWidth - margin) left = window.innerWidth - width - margin;

    popup.style.position = "fixed";
    popup.style.left = `${left}px`;
    popup.style.top  = `${top}px`;
    popup.style.zIndex = 9999;

    // Prevent clicks inside the popup from bubbling up to document
    popup.addEventListener("click", (ev) => {
      ev.stopPropagation();
    });

    // Save active refs
    activePopup = popup;
    activeCell = td;

    // Ensure the form has a Menge field (hidden) or we'll add array-style later on OK
    if (!form.querySelector("input[name='Menge']")) {
      const hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "Menge";
      hidden.value = "";
      form.appendChild(hidden);
    }

    // Copy current row's select[name='Person_ID'] into form hidden Person_ID (always override)
    const row = td.closest("tr");
    const selectPerson = row ? row.querySelector("select[name='Person_ID']") : null;
    if (selectPerson) {
      let personInput = form.querySelector("input[name='Person_ID']");
      if (!personInput) {
        personInput = document.createElement("input");
        personInput.type = "hidden";
        personInput.name = "Person_ID";
        form.appendChild(personInput);
      }
      personInput.value = selectPerson.value;
    }

    // Handle keypad clicks
    const display = popup.querySelector(".qty-display");

    popup.addEventListener("click", (ev) => {
      const btn = ev.target.closest(".num-btn");
      if (!btn) return;
      const num = btn.dataset.num;
      const action = btn.dataset.action;

      if (num !== undefined) {
        // append digit (leading zero -> replace)
        display.textContent = (display.textContent === "0") ? num : (display.textContent + num);
      } else if (action === "clear") {
        display.textContent = "0";
      } else if (action === "ok") {
        let qty = parseInt(display.textContent, 10);
        if (isNaN(qty) || qty <= 0) qty = 1; // default to 1

        // Decide which field to set:
        const produktId = td.dataset.produkt || td.getAttribute("data-produkt") || null;
        const isNewEntryRow = !!td.dataset.newentry || !!td.closest("tr")?.classList.contains("new-entry-row");
        const existingArrayField = produktId ? form.querySelector(`input[name="menge[${produktId}]"]`) : null;

        if (existingArrayField) {
          existingArrayField.value = qty;
        } else if (isNewEntryRow && produktId) {
          // Add or update menge[produktId]
          let mf = form.querySelector(`input[name="menge[${produktId}]"]`);
          if (!mf) {
            mf = document.createElement("input");
            mf.type = "hidden";
            mf.name = `menge[${produktId}]`;
            form.appendChild(mf);
          }
          mf.value = qty;
        } else {
          // fallback to single Menge field
          const mengeField = form.querySelector("input[name='Menge']");
          if (mengeField) mengeField.value = qty;
          else {
            const mf = document.createElement("input");
            mf.type = "hidden";
            mf.name = "Menge";
            mf.value = qty;
            form.appendChild(mf);
          }
        }

        // Run confirmation (will validate person etc.)
        if (typeof confirmEintragNeu === "function") {
          if (!confirmEintragNeu(form)) {
            // user cancelled
            closePopup();
            return;
          }
        }

        // submit and cleanup
        closePopup();
        form.submit();
      }
    });
  }); // end table click handler

  // Close popup when clicking anywhere outside popup (document-level listener)
  document.addEventListener("click", (e) => {
    if (!activePopup) return;
    // if clicked inside popup we already stopPropagation; so here it's outside -> close
    closePopup();
  });
});
// ----------------------
// Neue Person hinzufügen (Green Plus Button)
// ----------------------
document.addEventListener("DOMContentLoaded", () => {
  const addBtn = document.getElementById("addPersonBtn");
  const popup = document.getElementById("addPersonPopup");
  const confirmBtn = document.getElementById("confirmAddPerson");
  const cancelBtn = document.getElementById("cancelAddPerson");

  const vornameInput = document.getElementById("vornameInput");
  const nachnameInput = document.getElementById("nachnameInput");

  // Show popup
  addBtn.addEventListener("click", () => {
    popup.style.display = "flex";
    vornameInput.focus();
  });

  // Cancel popup
  cancelBtn.addEventListener("click", () => {
    popup.style.display = "none";
    vornameInput.value = "";
    nachnameInput.value = "";
  });

  // Confirm and save new person
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
        alert(`Neue Person hinzugefügt: ${vor} ${nach}`);
        location.reload(); // reloads to update selector
      } else {
        alert("Fehler beim Hinzufügen der Person.");
      }
    })
    .catch(err => {
      console.error("Fehler:", err);
      alert("Netzwerkfehler.");
    });
  });

  // Close popup on outside click
  popup.addEventListener("click", e => {
    if (e.target === popup) cancelBtn.click();
  });
});
</script>
<?php include("./includes/footer.php"); ?>
