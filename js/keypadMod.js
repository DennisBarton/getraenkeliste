export function initQuantityKeypad({
  produktNameById,
  personNameById,
  isCorrectionMode,
  confirmEintragNeu
}) {

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
    const form = td.querySelector("form");
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

    const produktId =
      form.querySelector("input[name='Produkt_ID']")?.value;

    const popup = document.createElement("div");
    popup.className = "qty-inline-popup";
    popup.innerHTML = `
      <div class="keypad-info">
        <div><strong>${personNameById[personId] || "Unbekannte Person"}</strong></div>
        <div><strong>${produktNameById[produktId] || "Unbekanntes Produkt"}</strong></div>
      </div>
      <div class="qty-display" aria-live="polite">0</div>
      <div class="qty-grid" role="grid">
        ${[1,2,3,4,5,6,7,8,9].map(n =>
          `<button type="button" class="num-btn" data-num="${n}">${n}</button>`
        ).join("")}
        <button type="button" class="num-btn" data-action="clear">C</button>
        <button type="button" class="num-btn" data-num="0">0</button>
        <button type="button" class="num-btn" data-action="ok">
          ${isCorrectionMode ? "-" : "+"}
        </button>
      </div>
    `;
    document.body.appendChild(popup);

    popup.style.position = "fixed";
    popup.style.zIndex = 9999;

    placePopup(popup, td.getBoundingClientRect());
    activePopup = popup;

    popup.addEventListener("click", ev => ev.stopPropagation());

    let mengeField = form.querySelector("input[name='Menge']");
    if (!mengeField) {
      mengeField = document.createElement("input");
      mengeField.type = "hidden";
      mengeField.name = "Menge";
      form.appendChild(mengeField);
    }

    const display = popup.querySelector(".qty-display");

    // 🔑 THIS is the important change
    popup.addEventListener("click", async (ev) => {
      const btn = ev.target.closest(".num-btn");
      if (!btn) return;

      if (btn.dataset.num !== undefined) {
        display.textContent =
          display.textContent === "0"
            ? btn.dataset.num
            : display.textContent + btn.dataset.num;

      } else if (btn.dataset.action === "clear") {
        display.textContent = "0";

      } else if (btn.dataset.action === "ok") {
        const qty = parseInt(display.textContent, 10);
        if (!qty) {
          closePopup();
          return;
        }

        mengeField.value = qty;

        console.log("Ask for confirmation");
        const confirmed = await confirmEintragNeu(form);
        closePopup();

        if (confirmed) {
          form.submit();
        }
      }
    });
  });

  document.addEventListener("click", closePopup);
});
}
