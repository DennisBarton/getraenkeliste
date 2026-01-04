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

    const personId =
      form.querySelector("input[name='Person_ID']")?.value ||
      form.closest("tr")?.querySelector("select[name='Person_ID']")?.value;

    const produktId = form.querySelector("input[name='Produkt_ID']")?.value;

    const personName = personNameById[personId] || "Unbekannte Person";
    const produktName = produktNameById[produktId] || "Unbekanntes Produkt";


    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <div class="keypad-info">
        <div><strong>${personName}</strong></div>
        <div><strong>${produktName}</strong></div>
      </div>
      <div class="qty-display" aria-live="polite">0</div>
      <div class="qty-grid" role="grid">
        ${[1,2,3,4,5,6,7,8,9].map(n => `<button type="button" class="num-btn" data-num="${n}">${n}</button>`).join('')}
        <button type="button" class="num-btn" data-action="clear">C</button>
        <button type="button" class="num-btn" data-num="0">0</button>
        <button type="button" class="num-btn" data-action="ok">${window.APP_DATA.isCorrectionMode ? '-' : '+'}</button>
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

