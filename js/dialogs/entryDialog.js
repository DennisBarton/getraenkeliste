// dialogs/entryDialog.js

function extractEntryData(form) {
  const datum =
    form.querySelector("input[name='Datum']")?.value || "Unbekannt";

  const personId =
    form.querySelector("input[name='Person_ID']")?.value ||
    form.closest("tr")
      ?.querySelector("select[name='Person_ID']")
      ?.value ||
    null;

  const produktId =
    form.querySelector("input[name='Produkt_ID']")?.value || null;

  const menge = parseInt(
    form.querySelector("input[name='Menge']")?.value,
    10
  );

  return { datum, personId, produktId, menge };
}

function validateEntryData(data) {
  if (!data.personId) {
    return "Bitte eine Person auswählen.";
  }
  if (!data.produktId) {
    return "Produkt fehlt.";
  }
  if (!Number.isInteger(data.menge) || data.menge <= 0) {
    return "Ungültige Menge.";
  }
  return null;
}

function ensurePersonInput(form, personId) {
  let input = form.querySelector("input[name='Person_ID']");
  if (!input) {
    input = document.createElement("input");
    input.type = "hidden";
    input.name = "Person_ID";
    form.appendChild(input);
  }
  input.value = personId;
}

function buildEntryConfirmText(data, appData) {
  const { produktNameById, personNameById, isCorrectionMode } = appData;

  const head = isCorrectionMode
    ? "Eintrag abziehen:"
    : "Eintrag hinzufügen:";

  const message = 
    head + "\n\n" +
    data.datum + "\n" +
    personNameById[data.personId] + "\n" +
    data.menge + " " + produktNameById[data.produktId];

  return (message);
}

import { showAlert, showConfirm } from "./modal.js";

export async function confirmEntryDialog(form, appData) {
  const data = extractEntryData(form);
  const error = validateEntryData(data);

  if (error) {
    await showAlert(error);
    return false;
  }

  ensurePersonInput(form, data.personId);

  const text = buildEntryConfirmText(data, appData);
  return await showConfirm(text, "");
}

