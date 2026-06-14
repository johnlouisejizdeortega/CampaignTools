import { expect, test } from '@playwright/test';

/**
 * Smoke coverage of the real user-facing flows against a running app.
 */

test('health endpoint responds ok', async ({ request }) => {
    const res = await request.get('/health');
    expect(res.ok()).toBeTruthy();
    expect((await res.json()).status).toBe('ok');
});

test('dashboard renders the three tools', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText('Show a report')).toBeVisible();
    await expect(page.getByText('Pause a campaign')).toBeVisible();
    await expect(page.getByText('Optimization suggestions')).toBeVisible();
});

test('pausing a campaign asks for confirmation first', async ({ page }) => {
    await page.goto('/');
    await page.locator('#pauseCustomerId').fill('1234567890');
    await page.locator('#campaignId').fill('1234567890');
    await page.getByRole('button', { name: 'Pause campaign' }).click();
    // The confirmation prompt must appear before anything is submitted.
    await expect(page.getByText(/It will stop spending immediately/)).toBeVisible();
    await expect(page.getByRole('button', { name: 'Yes, pause it' })).toBeVisible();
});

test('client-side validation blocks an invalid customer id', async ({ page }) => {
    await page.goto('/');
    // A too-short id keeps us on the page (server-side regex also guards this).
    await page.locator('#reportCustomerId').fill('123');
    await page.getByRole('button', { name: 'Show report' }).click();
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
});
