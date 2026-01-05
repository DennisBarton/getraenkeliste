// dialogs/entryDialog.js
import { runDialog } from "./runDialog.js";
import { extractFormValues } from "./formUtils.js";

export function confirmEntryDialog(form, appData) {
  return runDialog({
    collect: () => {
      const row = form.closest("tr");

      return {
        ...extractFormValues(form, ["Datum", "Person_ID", "Produkt_ID", "Menge"]),
        personId:
          form.querySelector("[name='Person_ID']")?.value ??
          row?.querySelector("select[name='Person_ID']")?.value ??
          null
      };
    },

    normalize: d => ({
      ...d,
      menge: parseInt(d.Menge, 10),
      datum: d.Datum || "Unbekannt"
    }),

    validate: d => {
      if (!d.personId) return "Bitte eine Person auswählen.";
      if (!d.Produkt_ID) return "Produkt fehlt.";
      if (!Number.isInteger(d.menge) || d.menge <= 0)
        return "Ungültige Menge.";
    },

    render: d => {
      const head = appData.isCorrectionMode
        ? "Eintrag abziehen:"
        : "Eintrag hinzufügen:";

      return (
        `${head}\n\n` +
        `${d.datum}\n` +
        `${appData.personNameById[d.personId]}\n` +
        `${d.menge} ${appData.produktNameById[d.Produkt_ID]}`
      );
    },

    onConfirm: d => {
      let input = form.querySelector("input[name='Person_ID']");
      if (!input) {
        input = document.createElement("input");
        input.type = "hidden";
        input.name = "Person_ID";
        form.appendChild(input);
      }
      input.value = d.personId;
    }
  });
}
