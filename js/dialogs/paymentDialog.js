// dialogs/paymentDialog.js

function extractPaymentData(form) {
  const row = form.closest("tr");

  return {
    name: row.cells[0].textContent.trim(),
    date: form.querySelector("input[name='date']")?.value || "",
    amount: row.cells[row.cells.length - 2].textContent.trim()
  };
}

function buildPaymentConfirmText({ name, date, amount }) {
  return (
    `${name}\n` +
    `${date}\n` +
    `${amount}`
  );
}

import { showConfirm } from "./modal.js";

export async function confirmPaymentDialog(event) {
  event.preventDefault();

  const form = event.target;
  const data = extractPaymentData(form);

  const ok = await showConfirm(
    buildPaymentConfirmText(data),
    "Zahlung bestätigen"
  );

  if (ok) form.submit();
}

