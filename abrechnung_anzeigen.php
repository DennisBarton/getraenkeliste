<?php
$site_name = "Abrechnung";
include("./includes/header.php");

// Determine date filter
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
$showPaid=0;
if (isset($_GET['paid']) && $_GET['paid'] == 1) {
  $showPaid=1;
  $dateClause = " ";
}


// Multi-query to fetch entries, persons, and products
$sql = "
    SELECT
      e.date,
      e.person,
      e.produkt,
      SUM(e.anzahl) AS sum,
      e.bezahlt
    FROM db_eintrag AS e
    WHERE bezahlt = $showPaid $dateClause
    GROUP BY e.date, e.person, e.produkt
    ORDER BY e.date, e.person;

    SELECT person_id, nachname, vorname FROM db_personen;

    SELECT produkt_id, name, preis FROM db_produkte_standard;
";

$data_query = $pdo->query($sql);

// Fetch all result sets
$data = [];
do {
    $data[] = $data_query->fetchAll(PDO::FETCH_ASSOC);
} while ($data_query->nextRowset());

// Index persons and products by ID for quick lookup
$personById = array_column($data[1], null, 'person_id');
$produktById = array_column($data[2], null, 'produkt_id');

// Structure data grouped by date -> person -> products
$structuredData = [];
foreach ($data[0] as $row) {
    $date = $row['date'];
    $person = $row['person'];
    $produkt = $row['produkt'];

    if (!isset($structuredData[$date])) {
        $structuredData[$date] = [];
    }
    if (!isset($structuredData[$date][$person])) {
        $structuredData[$date][$person] = [
            'produkte' => [],
            'bezahlt' => $row['bezahlt'],
        ];
    }
    $structuredData[$date][$person]['produkte'][$produkt] = $row['sum'];
}
if (isset($_GET['date']) && $_GET['date'] === 'today') {
    if (!isset($structuredData[$today])) {
        $structuredData[$today] = []; // empty person list
    }
}
?>


<?php foreach ($structuredData as $datum => $persons): ?>
    <h3><?= $showPaid ? 'Bezahlte' : 'Offene' ?> Beträge vom <?= conv_date($datum) ?></h3>
    <table class="styled-table">
        <thead>
            <tr>
                <th style="text-align:left; width:100px;">Name</th>
                <?php foreach ($produktById as $produkt): ?>
                    <th style="text-align:left">
                        <?= htmlspecialchars($produkt['name']) ?> <?= number_format($produkt['preis'], 2) ?> &#8364;
                    </th>
                <?php endforeach; ?>
                <th style="text-align:left">Betrag</th>
                <th style="text-align:left"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($persons as $personId => $personData): ?>
                <?php
                $betrag = 0;
                ?>
                <tr>
                    <td style="text-align:left">
                        <?= htmlspecialchars($personById[$personId]['vorname']) ?>
                        <?= htmlspecialchars($personById[$personId]['nachname']) ?>
                    </td>
                    <?php foreach ($produktById as $produktId => $produkt): 
                        $anzahl = $personData['produkte'][$produktId] ?? 0;
                        $betrag += $anzahl * $produkt['preis'];
                    ?>
                        <td style="text-align:center; font-size:20px;">
                            <?php if ($anzahl > 0): ?>
                                <?= $anzahl ?>
                            <?php endif;
                                  if (!$showPaid):
                            ?>
                            <form action="eintrag_speichern.php" method="post" style="display:inline-block;" onsubmit="return confirmEintragNeu(this)">
                                <input type="hidden" name="action" value="verkauf">
                                <input type="hidden" name="Datum" value="<?= $datum ?>">
                                <input type="hidden" name="Produkt_ID" value="<?= $produktId ?>">
                                <input type="hidden" name="Person_ID" value="<?= $personId ?>">
                                <input type="hidden" name="Menge" value="1">
                                <button type="button" class="open-qty-inline" title="Menge wählen">➕</button>
                                <input type="hidden" name="Verkaufspreis" value="<?= $produkt['preis'] ?>">
                            </form>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td><?= number_format($betrag, 2) ?> &#8364;</td>
                    <td>
                        <?php if (!$showPaid): ?>
                        <form action="eintrag_speichern.php" method="post" class="formBezahlen" onsubmit="return confirmVerkauf(event)">
                            <input type="hidden" name="action" value="bezahlen">
                            <input type="hidden" name="date" value="<?= $datum ?>">
                            <input type="hidden" name="personId" value="<?= $personId ?>">
                            <input type="image" src="./includes/check.svg" width="33px" alt="Submit" title="Bezahlen">
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php
            // Persons without entries for this date
            $allPersonIds = array_keys($personById);
            $personenMitEintrag = array_keys($persons);
            $personenOhneEintrag = array_diff($allPersonIds, $personenMitEintrag);
            if (isset($_GET["date"])) {
            ?>
            <tr class="new-entry-row" style="background-color:#f9f9f9">
              <td colspan="<?= count($produktById) + 3 ?>">
                <form action="eintrag_speichern.php" method="post" onsubmit="return confirmEintragNeu(this)" style="display:flex; align-items:center; gap:1em;">
                  <select name="Person_ID" id="person-select-<?= $datum ?>" onchange="handleNeuePersonChange('<?= $datum ?>')" style="width:80px;">
                    <option value="">Neu</option> 
                    <?php foreach ($personenOhneEintrag as $id): ?>
                      <option value="<?= $id ?>">
                        <?= htmlspecialchars($personById[$id]['vorname']) ?> <?= htmlspecialchars($personById[$id]['nachname']) ?>
                      </option>
                    <?php endforeach; ?>
                    <option value="__new__">➕ Neue Person</option>
                  </select>
                  <div id="neue-person-form-<?= $datum ?>" style="display:none; margin-top:0.5em">
                    <input type="text" name="neueVorname" placeholder="Vorname" />
                    <input type="text" name="neueNachname" placeholder="Nachname" />
                  </div>

                  <?php foreach ($produktById as $produktId => $produkt): ?>
                    <div>
                      <label>
                        <?= htmlspecialchars($produkt['name']) ?>:
                        <input type="number" name="menge[<?= $produktId ?>]" min="0" max="999" style="width:40px"/>
                      </label>
                      <input type="hidden" name="Verkaufspreis[<?= $produktId ?>]" value="<?= $produkt['preis'] ?>" />
                    </div>
                  <?php endforeach; ?>

                  <input type="hidden" name="Datum" value="<?= $datum ?>">
                  <input type="hidden" name="action" value="verkauf">
                  <input type="image" src="./includes/plus.svg" width="33px" alt="Submit" title="Eintragen">
                </form>
              </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php endforeach; ?>

<script>
    // These map objects are injected by PHP
    const produktNameById = <?= json_encode(array_column($produktById, 'name', 'produkt_id')) ?>;
    const personNameById = <?= json_encode(array_map(fn($p) => $p['vorname'] . ' ' . $p['nachname'], $personById)) ?>;

    function confirmEintragNeu(form) {
        const datum = form.querySelector("input[name='Datum']").value;

        // figure out personId from select or hidden input
        let personId = null;
        const sel = form.querySelector("select[name='Person_ID']");
        if (sel) {
            personId = sel.value;
        } else {
            const inp = form.querySelector("input[name='Person_ID']");
            if (inp) personId = inp.value;
        }

        if (!personId) {
            alert("Person nicht ausgewählt.");
            return false;
        }

        // Debug: log personId and current map
        console.log("confirmEintragNeu: personId", personId, "personNameById:", personNameById);

        let produktDetails = [];

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

        let personLabel = "Unbekannt";
        if (personId === "__new__") {
            const vor = form.querySelector("input[name='neueVorname']")?.value?.trim() || "";
            const nach = form.querySelector("input[name='neueNachname']")?.value?.trim() || "";
            if (vor && nach) personLabel = `${vor} ${nach}`;
        } else if (personNameById[personId]) {
            personLabel = personNameById[personId];
        }
        const confirmMessage =
            "Eintrag hinzufügen:\n\n" +
            "Person: " + personLabel + "\n" +
            "Datum: " + datum + "\n" +
            "Produkte und Mengen:\n" + produktDetails.join("\n") + "\n";

        return confirm(confirmMessage);
    }

    function handleNeuePersonChange(datum) {
        const sel = document.getElementById(`person-select-${datum}`);
        const div = document.getElementById(`neue-person-form-${datum}`);
        if (sel && div) {
            div.style.display = sel.value === "__new__" ? "block" : "none";
        }
    }

    function neuePersonSpeichernInline(button, datum) {
        const formDiv = button.closest("div");
        const vornameInput = formDiv.querySelector("input[name='neueVorname']");
        const nachnameInput = formDiv.querySelector("input[name='neueNachname']");
        const select = document.getElementById(`person-select-${datum}`);

        const vor = vornameInput?.value.trim() ?? "";
        const nach = nachnameInput?.value.trim() ?? "";
        if (!vor || !nach) {
            alert("Bitte Vorname und Nachname eingeben.");
            return;
        }

        fetch('neue_person_eintragen.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `vorname=${encodeURIComponent(vor)}&nachname=${encodeURIComponent(nach)}`
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success && data.person_id) {
                // Insert new option into select
                const newOpt = document.createElement("option");
                newOpt.value = data.person_id;
                newOpt.textContent = `${vor} ${nach}`;
                newOpt.selected = true;
                const beforeOpt = select.querySelector('option[value="__new__"]');
                select.insertBefore(newOpt, beforeOpt);

                // **Key line: update global map**
                personNameById[data.person_id] = `${vor} ${nach}`;

                // Hide the inline new-person form
                formDiv.style.display = "none";
                vornameInput.value = "";
                nachnameInput.value = "";
                nachnameInput.blur(); // optional
            } else {
                alert("Fehler: " + (data.message ?? "kein person_id zurückgegeben"));
                console.log("Data from server:", data);
            }
        })
        .catch(err => {
            console.error("Fehler beim neuePersonSpeichernInline:", err);
            alert("Netzwerkfehler: " + err);
        });
    }

    function confirmVerkauf(event) {
        event.preventDefault();
        const form = event.target;
        const row = form.closest("tr");
        const name = row.cells[0].textContent.trim();
        const date = form.querySelector("input[name='date']").value;
        const amount = row.cells[row.cells.length - 2].textContent.trim();
        if (confirm(`Wollen Sie diesen Eintrag bezahlen:\n\nName:\t${name}\nDatum:\t${date}\nBetrag:\t${amount}`)) {
            form.submit();
        }
    }
    // ----------------------
// Inline quantity popup with confirmEintragNeu integration
// ----------------------
document.addEventListener("DOMContentLoaded", () => {
  let activePopup = null;

  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest(".open-qty-inline");
    const clickedInsidePopup = e.target.closest(".qty-inline-popup");

    // Close popup if clicking anywhere else
    if (activePopup && !btn && !clickedInsidePopup) {
      activePopup.remove();
      activePopup = null;
      return;
    }

    // Only continue if a "+" button was clicked
    if (!btn) return;

    e.preventDefault();

    // Remove existing popup first
    if (activePopup) {
      activePopup.remove();
      activePopup = null;
    }

    const form = btn.closest("form");

    // Create popup
    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <input type="number" min="1" max="999" value="1" class="qty-input">
      <button type="button" class="ok-btn">OK</button>
    `;
    document.body.appendChild(popup);

    // Position popup next to button
    const rect = btn.getBoundingClientRect();
    const popupRect = popup.getBoundingClientRect();
    const margin = 6;
    let left = rect.right + margin;
    let top = rect.top;

    // Adjust horizontally if near right edge
    if (left + popupRect.width > window.innerWidth - margin) {
      left = rect.left - popupRect.width - margin;
    }
    // Adjust vertically if near bottom
    if (top + popupRect.height > window.innerHeight - margin) {
      top = window.innerHeight - popupRect.height - margin;
    }

    popup.style.position = "fixed";
    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;

    const input = popup.querySelector(".qty-input");
    const okBtn = popup.querySelector(".ok-btn");
    input.focus();

    activePopup = popup;

    // Confirm + submit handler
    const confirmAndSubmit = () => {
      const qty = parseInt(input.value, 10);
      if (isNaN(qty) || qty <= 0) {
        popup.remove();
        activePopup = null;
        return;
      }

      // Update the hidden Menge field
      const mengeField = form.querySelector("input[name='Menge']");
      if (mengeField) mengeField.value = qty;

      // ✅ Call confirmEintragNeu() before submitting
      if (typeof confirmEintragNeu === "function") {
        if (!confirmEintragNeu(form)) {
          popup.remove();
          activePopup = null;
          return; // user cancelled
        }
      }

      popup.remove();
      activePopup = null;
      form.submit();
    };

    // OK button
    okBtn.addEventListener("click", confirmAndSubmit);

    // Keyboard shortcuts
    input.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") {
        ev.preventDefault();
        confirmAndSubmit();
      } else if (ev.key === "Escape") {
        popup.remove();
        activePopup = null;
      }
    });
  });
});
</script>

<?php
include("./includes/footer.php");
?>
