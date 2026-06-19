import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

async function run() {
    console.log("Connecting to Chrome on http://127.0.0.1:9222...");
    let browser;
    try {
        browser = await puppeteer.connect({
            browserURL: 'http://127.0.0.1:9222',
            defaultViewport: null
        });
        console.log("Successfully connected to existing Chrome browser!");
    } catch (err) {
        console.error("Failed to connect to Chrome on port 9222:", err.message);
        console.error("Please make sure Chrome is running with remote debugging enabled (--remote-debugging-port=9222)");
        process.exit(1);
    }

    const pages = await browser.pages();
    // Use an existing page if open, or open a new one
    const page = pages.length > 0 ? pages[0] : await browser.newPage();
    console.log("Using page: " + await page.title());

    // Navigate to register page
    console.log("Navigating to http://127.0.0.1:8000/register...");
    await page.goto('http://127.0.0.1:8000/register', { waitUntil: 'networkidle2' });

    // Check if we are on the register page or if we are already logged in
    const currentUrl = page.url();
    console.log("Current URL: " + currentUrl);

    if (currentUrl.includes('/dashboard')) {
        console.log("Already logged in and on dashboard. Logging out first...");
        // Clear cookies for 127.0.0.1:8000
        const client = await page.target().createCDPSession();
        await client.send('Network.clearBrowserCookies');
        console.log("Cookies cleared. Reloading register page...");
        await page.goto('http://127.0.0.1:8000/register', { waitUntil: 'networkidle2' });
    }

    // Try registration
    const email = 'ulc320@gmail.com';
    const password = '12345678';

    console.log("Checking if email field exists...");
    const emailInput = await page.$('#email');
    if (!emailInput) {
        console.error("Register page inputs not found. Check if the server is running on http://127.0.0.1:8000/");
        process.exit(1);
    }

    // Fill registration
    console.log("Filling registration form...");
    await page.type('#name', 'Test User');
    
    // Clear email field first if prefilled
    await page.focus('#email');
    await page.keyboard.down('Meta');
    await page.keyboard.press('KeyA');
    await page.keyboard.up('Meta');
    await page.keyboard.press('Backspace');
    await page.type('#email', email);

    await page.type('#password', password);
    await page.type('#password_confirmation', password);

    console.log("Submitting registration form...");
    await Promise.all([
        page.click('button[data-test="register-user-button"]'),
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(() => {})
    ]);

    let urlAfterRegister = page.url();
    console.log("URL after register attempt: " + urlAfterRegister);

    // If registration failed because email is taken, go to login page
    if (urlAfterRegister.includes('/register') || await page.$('.text-destructive') || await page.$('.text-rose-500')) {
        console.log("Registration failed or email already exists. Navigating to login page...");
        await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle2' });
        
        console.log("Filling login form...");
        await page.focus('#email');
        await page.keyboard.down('Meta');
        await page.keyboard.press('KeyA');
        await page.keyboard.up('Meta');
        await page.keyboard.press('Backspace');
        await page.type('#email', email);
        
        await page.type('#password', password);
        
        console.log("Submitting login form...");
        await Promise.all([
            page.click('button[data-test="login-button"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(() => {})
        ]);
        urlAfterRegister = page.url();
        console.log("URL after login: " + urlAfterRegister);
    }

    // Now we should be on /verify/otp page (since email verification is required)
    if (urlAfterRegister.includes('/verify/otp')) {
        console.log("OTP verification required. Fetching code from log...");
        
        // Wait a second for log to be written
        await new Promise(r => setTimeout(r, 2000));
        
        const logPath = '/Users/huzai/Documents/GitHub/laravel-subscription/storage/logs/laravel.log';
        if (!fs.existsSync(logPath)) {
            console.error("Laravel log not found at: " + logPath);
            process.exit(1);
        }
        
        const logContents = fs.readFileSync(logPath, 'utf8');
        const matches = [...logContents.matchAll(/Generated OTP for ulc320@gmail\.com: (\d{6})/g)];
        if (matches.length === 0) {
            console.error("No OTP code found in log file!");
            process.exit(1);
        }
        
        const otpCode = matches[matches.length - 1][1];
        console.log("Found OTP code: " + otpCode);
        
        console.log("Entering OTP code...");
        const otpInput = await page.$('input[aria-label^="Digit"], div[class*="InputOTP"]');
        if (otpInput) {
            await otpInput.click();
        } else {
            await page.click('body');
        }
        
        // Type the characters one by one
        for (const char of otpCode) {
            await page.keyboard.sendCharacter(char);
            await new Promise(r => setTimeout(r, 100));
        }
        
        console.log("Submitting OTP code...");
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(() => {})
        ]);
        
        urlAfterRegister = page.url();
        console.log("URL after OTP verification: " + urlAfterRegister);
    }

    // Now we should be on /pricing page
    if (urlAfterRegister.includes('/pricing')) {
        console.log("On pricing page. Selecting Free Starter plan...");
        
        await page.evaluate(() => {
            const cards = Array.from(document.querySelectorAll('.flex.flex-col.relative.overflow-hidden'));
            const freeCard = cards.find(card => card.textContent.includes('Free Starter'));
            if (freeCard) {
                const btn = freeCard.querySelector('button');
                if (btn) {
                    btn.click();
                } else {
                    throw new Error("Get Started button not found inside Free Starter card");
                }
            } else {
                throw new Error("Free Starter card not found");
            }
        });
        
        console.log("Waiting for redirection after plan selection...");
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(() => {});
        urlAfterRegister = page.url();
        console.log("URL after plan selection: " + urlAfterRegister);
    }

    // Verify if we reached dashboard
    if (urlAfterRegister.includes('/dashboard')) {
        console.log("SUCCESS: Flow verified! User registered, verified OTP, and subscribed to Free Starter plan. Currently on Dashboard.");
    } else {
        console.error("FAIL: Did not reach /dashboard. Current URL is: " + urlAfterRegister);
    }

    await browser.disconnect();
}

run().catch(console.error);
