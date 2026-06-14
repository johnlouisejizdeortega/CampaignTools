import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end test configuration. Playwright boots the app with `php artisan
 * serve` (TEAM_ACCESS_PASSWORD left empty so the dashboard is reachable without
 * logging in) and runs the specs in tests/e2e against it.
 */
const PORT = 8123;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'list',
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'on-first-retry',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
    webServer: {
        command: `php artisan serve --host=127.0.0.1 --port=${PORT}`,
        url: `http://127.0.0.1:${PORT}/health`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
        env: {
            APP_ENV: 'local',
            APP_DEBUG: 'true',
            TEAM_ACCESS_PASSWORD: '',
        },
    },
});
