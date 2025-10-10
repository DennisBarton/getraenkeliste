
const { test, expect } = require('@playwright/test');

test('Page loads and shows table', async ({ page }) => {
  await page.goto('http://host.docker.internal:8080/abrechnung_anzeigen.php?date=today', { waitUntil: 'networkidle' });
  await expect(page.locator('table.styled-table')).toBeVisible();
});

test('Quantity popup appears on cell click', async ({ page }) => {
  await page.goto('http://host.docker.internal:8080/abrechnung_anzeigen.php?date=today');
  const firstCell = page.locator('td.product-cell').first();
  await firstCell.click();
  await expect(page.locator('.qty-inline-popup')).toBeVisible();
});
