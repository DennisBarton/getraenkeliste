// app.js
import { initQuantityKeypad } from "./keypadMod.js";
import { confirmEntryDialog } from "./dialogs/entryDialog.js";
import { confirmPaymentDialog } from "./dialogs/paymentDialog.js";
import { initAddPersonPopup } from "./addPersonPopup.js";

const appData = window.APP_DATA;

// expose only what HTML needs
window.confirmVerkauf = confirmPaymentDialog;

initQuantityKeypad({
  ...appData,
  confirmEintragNeu: form => confirmEntryDialog(form, appData)
});

initAddPersonPopup();

