// dialogs/paymentDialog.js
import { runDialog } from "./runDialog.js";

export function confirmPaymentDialog(event) {
  event.preventDefault();
  const form = event.target;

  return runDialog({
    collect: () => {
      const row = form.closest("tr");
      return {
        name: row.cells[0].textContent.trim(),
        date: form.querySelector("[name='date']")?.value || "",
        amount: row.cells[row.cells.length - 2].textContent.trim()
      };
    },

    render: d =>
      `${d.name}\n${d.date}\n${d.amount}`,

    title: "Zahlung bestätigen",

    onConfirm: () => form.submit()
  });
}

