import { test, expect } from '@playwright/test';

test('verify landing and login pages', async ({ page }) => {
    // Check Landing Page (Login Form)
    await page.goto('http://localhost:8080');
    await page.screenshot({ path: '/home/jules/verification/login_form.png' });

    // Check Register Page
    await page.goto('http://localhost:8080/register');
    await page.screenshot({ path: '/home/jules/verification/register.png' });
});

test('verify student dashboard', async ({ page }) => {
    // Login as student
    await page.goto('http://localhost:8080');
    await page.fill('input[id="email"]', 'student@example.com');
    await page.fill('input[id="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for dashboard and screenshot
    await page.waitForURL('**/student/dashboard');
    await page.screenshot({ path: '/home/jules/verification/dashboard.png', fullPage: true });
});
