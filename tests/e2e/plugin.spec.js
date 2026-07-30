const { test, expect } = require("@playwright/test");

async function login(page) {
  await page.goto("/wp-login.php");
  await page.locator('input[name="log"]').fill("admin");
  await page.locator('input[name="pwd"]').fill("password");
  await page.getByRole("button", { name: "Log In" }).click();
  await expect(page).toHaveURL(/wp-admin/);
}

test.beforeEach(async ({ page }) => {
  await login(page);
});

test("connects a site and displays the generated preview", async ({ page }) => {
  await page.goto("/wp-admin/options-general.php?page=mosaicora");
  await page.locator('input[name="mosaicora_settings[site_id]"]').fill("site-123");
  await page.locator('input[name="mosaicora_settings[enabled]"]').check();
  await page.getByRole("button", { name: "Save Mosaicora settings" }).click();

  await expect(page.getByText("Mosaicora is ready to publish social image metadata.")).toBeVisible();
  await expect(page.locator(".mosaicora-preview")).toHaveAttribute("src", /cdn\.mosaicora\.io\/s\/site-123/);
});

test("adds a typed exact value in the block editor", async ({ page }) => {
  await page.goto("/wp-admin/post-new.php");
  const welcome = page.getByRole("dialog", { name: "Welcome to the editor" });
  if (await welcome.isVisible()) {
    await welcome.getByRole("button", { name: "Close" }).click();
  }
  const metaBoxesToggle = page.getByRole("button", { name: "Meta Boxes", exact: true });
  await expect(metaBoxesToggle).toBeVisible();
  if ((await metaBoxesToggle.getAttribute("aria-expanded")) !== "true") {
    await metaBoxesToggle.evaluate((button) => button.click());
  }
  await expect(metaBoxesToggle).toHaveAttribute("aria-expanded", "true");
  await expect(page.getByText("Mosaicora social image", { exact: true })).toBeVisible();
  await page.getByRole("button", { name: "Add exact value" }).click();
  const row = page.locator(".mosaicora-role-row").last();
  await row.locator(".mosaicora-role-select").selectOption("content.title");
  await row.locator('textarea[name="mosaicora_semantic_value[]"]').fill("Exact article title");
  await expect(row.locator(".mosaicora-role-type")).toHaveText("text");
});

test("renders the same guided panel in the classic editor", async ({ page }) => {
  await page.goto("/wp-admin/post-new.php?mosaicora_classic=1");
  await expect(page.locator("#mosaicora-page-settings")).toBeVisible();
  await expect(page.getByRole("button", { name: "Add exact value" })).toBeVisible();
});
