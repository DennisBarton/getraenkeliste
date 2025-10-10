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

/* ---------- Popup logic: click the TD (cell), not a small button ---------- */
document.addEventListener("DOMContentLoaded", () => {
  let activePopup = null;

  document.body.addEventListener("click", (e) => {
    const clickedInsidePopup = e.target.closest(".qty-inline-popup");
    const td = e.target.closest("td.cell-clickable");

    // If popup open and click is outside cells and popup → close popup
    if (activePopup && !td && !clickedInsidePopup) {
      activePopup.remove();
      activePopup = null;
      return;
    }

    // Only act when clicking a clickable td
    if (!td) return;
    e.preventDefault();

    // find the form inside the clicked cell (each clickable cell must contain its form)
    const form = td.querySelector("form");
    if (!form) return; // nothing to submit

    // remove previous popup if present
    if (activePopup) {
      activePopup.remove();
      activePopup = null;
    }

    // create popup element
    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <span class="popup-plus">➕</span>
      <input type="number" min="1" max="999" value="1" class="qty-input" />
      <button type="button" class="ok-btn">OK</button>
    `;
    document.body.appendChild(popup);

    // position popup centered over the clicked td; clamp to viewport
    const rect = td.getBoundingClientRect();
    const popupRect = popup.getBoundingClientRect();
    const margin = 8;
    let left = rect.left + (rect.width / 2) - (popupRect.width / 2);
    let top  = rect.top  + (rect.height / 2) - (popupRect.height / 2);

    if (left < margin) left = margin;
    if (left + popupRect.width > window.innerWidth - margin) left = window.innerWidth - popupRect.width - margin;
    if (top < margin) top = margin;
    if (top + popupRect.height > window.innerHeight - margin) top = window.innerHeight - popupRect.height - margin;

    popup.style.position = "fixed";
    popup.style.left = `${left}px`;
    popup.style.top  = `${top}px`;
    popup.style.zIndex = 9999;

    // focus
    const input = popup.querySelector(".qty-input");
    const okBtn = popup.querySelector(".ok-btn");
    input.focus();
    activePopup = popup;

    // ALWAYS copy the current row's Person selection into the form's hidden Person_ID
    // (this fixes the bug where the hidden Person_ID kept a previous value)
    const personField = form.querySelector("input[name='Person_ID']");
    const row = form.closest("tr");
    const select = row ? row.querySelector("select[name='Person_ID']") : null;
    if (personField) {
      // copy even if personField already had a value; override with current select if found
      personField.value = select ? select.value : (personField.value || "");
    }

    // OK handler: validate, confirm and submit
    const confirmAndSubmit = () => {
      const qty = parseInt(input.value, 10);
      if (isNaN(qty) || qty <= 0) {
        alert("Bitte eine Menge größer als 0 eingeben.");
        input.focus();
        return;
      }

      // set the Menge field inside the form
      const mengeField = form.querySelector("input[name='Menge']") || form.querySelector("input[name^='menge']");
      if (mengeField) {
        // if the form uses a hidden 'Menge' input (single-product form)
        if (mengeField.name === 'Menge') mengeField.value = qty;
        else mengeField.value = qty; // handles single menge[...] hidden input as well
      }

      // call confirm (shows summary and asks user)
      if (typeof confirmEintragNeu === "function") {
        if (!confirmEintragNeu(form)) {
          popup.remove();
          activePopup = null;
          return;
        }
      }

      // submit form
      popup.remove();
      activePopup = null;
      form.submit();
    };

    okBtn.addEventListener("click", confirmAndSubmit);

    // keyboard support
    input.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") { ev.preventDefault(); confirmAndSubmit(); }
      else if (ev.key === "Escape") { popup.remove(); activePopup = null; }
    });
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
