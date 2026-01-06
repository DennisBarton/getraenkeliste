// dialogs/runDialog.js
export async function runDialog({
  collect,
  validate,
  normalize = d => d,
  render,
  title = "",
  onConfirm
}) {
  console.log("call to runDialog.js");
  const data = normalize(collect());

  const error = validate?.(data);
  if (error) {
    await import("./modal.js").then(m => m.showAlert(error));
    return false;
  }

  const modal = await import("./modal.js");
  const ok = await modal.showConfirm(render(data), title);

  if (ok && onConfirm) onConfirm(data);

  return ok;
}
