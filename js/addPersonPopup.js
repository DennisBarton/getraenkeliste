// addPersonPopup.js

export function initAddPersonPopup() {
  document.addEventListener("DOMContentLoaded", () => {
    const addBtn = document.getElementById("addPersonBtn");
    const popup = document.getElementById("addPersonPopup");
    const confirmBtn = document.getElementById("confirmAddPerson");
    const cancelBtn = document.getElementById("cancelAddPerson");
    const vornameInput = document.getElementById("vornameInput");
    const nachnameInput = document.getElementById("nachnameInput");

    const close = () => {
      popup.style.display = "none";
      vornameInput.value = "";
      nachnameInput.value = "";
    };

    addBtn.addEventListener("click", () => {
      popup.style.display = "flex";
      vornameInput.focus();
    });

    cancelBtn.addEventListener("click", close);

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
        body:
          `vorname=${encodeURIComponent(vor)}` +
          `&nachname=${encodeURIComponent(nach)}`
      })
        .then(r => r.json())
        .then(data => {
          if (!data.success || !data.person_id) {
            throw new Error();
          }

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

          close();
        })
        .catch(() => alert("Fehler beim Hinzufügen der Person."));
    });

    popup.addEventListener("click", e => {
      if (e.target === popup) close();
    });
  });
}
