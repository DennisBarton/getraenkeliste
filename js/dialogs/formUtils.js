// dialogs/formUtils.js
export function extractFormValues(form, fields) {
  return Object.fromEntries(
    fields.map(name => [
      name,
      form.querySelector(`[name="${name}"]`)?.value ?? null
    ])
  );
}
