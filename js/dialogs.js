// dialogs.js

export function confirmEintragNeu(form, appData) {
  const { produktNameById, personNameById, isCorrectionMode } = appData;

  const datum =
    form.querySelector("input[name='Datum']")?.value || "Unbekannt";

  let personId =
    form.querySelector("input[name='Person_ID']")?.value ||
    form.closest("tr")?.querySelector("select[name='Person_ID']")?.value;

  if (!personId) {
    alert("Bitte eine Person auswählen.");
    return false;
  }

  const mengeInput = form.querySelector("input[name='Menge']");
  const produktIdInput = form.querySelector("input[name='Produkt_ID']");

  if (!mengeInput || !produktIdInput) {
    alert("Produkt oder Menge fehlt.");
    return false;
  }

  const menge = parseInt(mengeInput.value, 10);
  if (!Number.isInteger(menge) || menge <= 0) {
    alert("Ungültige Menge.");
    return false;
  }

  const pid = produktIdInput.value;
  const pname = produktNameById[pid] || "Unbekannt";
  const personLabel = personNameById[personId] || "Unbekannt";

  let personInput = form.querySelector("input[name='Person_ID']");
  if (!personInput) {
    personInput = document.createElement("input");
    personInput.type = "hidden";
    personInput.name = "Person_ID";
    form.appendChild(personInput);
  }
  personInput.value = personId;

  const head = isCorrectionMode
    ? "Eintrag abziehen:"
    : "Eintrag hinzufügen:";

  return confirm(
    `${head}\n\nDatum: ${datum}\nPerson: ${personLabel}\n${menge} ${pname}`
  );
}

export function confirmVerkauf(event) {
  event.preventDefault();

  const form = event.target;
  const row = form.closest("tr");

  const name = row.cells[0].textContent.trim();
  const date = form.querySelector("input[name='date']")?.value || "";
  const amount = row.cells[row.cells.length - 2].textContent.trim();

  if (
    confirm(
      `Wollen Sie diesen Eintrag bezahlen:\n\n` +
      `Name:\t${name}\nDatum:\t${date}\nBetrag:\t${amount}`
    )
  ) {
    form.submit();
  }
}
