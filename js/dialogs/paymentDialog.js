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
    `Wollen Sie diesen Eintrag bezahlen:\n\n` +
    `Name:\t${name}\n` +
    `Datum:\t${date}\n` +
    `Betrag:\t${amount}`
  );
}

export function confirmPaymentDialog(event) {
  event.preventDefault();

  const form = event.target;
  const data = extractPaymentData(form);

  if (confirm(buildPaymentConfirmText(data))) {
    form.submit();
  }
}
