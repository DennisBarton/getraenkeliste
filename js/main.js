import { initQuantityKeypad } from "./keypadMod.js";

const { produktNameById, personNameById, isCorrectionMode } = window.APP_DATA;
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
  const confirmHead = isCorrectionMode ? "Eintrag abziehen:" : "Eintrag hinzufügen:";
  const confirmMessage =
    confirmHead + "\n\n" +
    "Datum: " + datum + "\n" +
    "Person: " + personLabel + "\n" +
    menge + " " + pname + "\n";
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

initQuantityKeypad({
  produktNameById,
  personNameById,
  isCorrectionMode,
  confirmEintragNeu
});
window.confirmVerkauf = confirmVerkauf;
