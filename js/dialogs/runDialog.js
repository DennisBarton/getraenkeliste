// dialogs/runDialog.js
export async function runDialog({
  collect,
  validate,
  normalize = d => d,
  render,
  title = "",
  onConfirm
}) {
  const data = normalize(collect());

  const error = validate?.(data);
  if (error) {
    await import("./modal.js").then(m => m.showAlert(error));
    return false;
  }

  const ok = await import("./modal.js")
    .then(m => m.showConfirm(render(data), title));

  if (ok && onConfirm) onConfirm(data);

  return ok;
}
