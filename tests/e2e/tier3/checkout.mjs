/**
 * Drives a real shopper through checkout in a real browser.
 *
 * This is the question Tier 2 cannot answer. Tier 2 calls process_payment()
 * directly, so it proves the gateway works but never that a shopper can reach
 * it. WooCommerce's default checkout is block-based and this plugin registers
 * for it through a separate integration class, so a gateway can pass every
 * Tier 2 check and still be invisible at checkout.
 *
 * Prints "PASS <name>" / "FAIL <name>" lines for the shell wrapper to count,
 * and exits non-zero if anything failed.
 */

import { chromium } from 'playwright';

const SHOP = process.env.SHOP_URL || 'http://shop.test';
const PRODUCT_URL = process.env.PRODUCT_URL;
const TITLE = 'Pay with SpectroCoin';

let failed = 0;
const pass = (m) => console.log(`PASS ${m}`);
const fail = (m) => { failed++; console.log(`FAIL ${m}`); };

const browser = await chromium.launch();
// The stub answers as spectrocoin.com with a certificate from a CA generated
// by the harness, which the browser has no reason to trust.
const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await ctx.newPage();
page.setDefaultTimeout(30000);

const shot = async (name) => {
  try { await page.screenshot({ path: `/work/artifacts/${name}.png`, fullPage: true }); } catch {}
};

try {
  // ---- add to cart ----------------------------------------------------
  await page.goto(PRODUCT_URL, { waitUntil: 'domcontentloaded' });
  const addToCart = page.locator('button[name="add-to-cart"], .single_add_to_cart_button').first();
  if (await addToCart.count()) {
    await addToCart.click();
    pass('product can be added to the cart');
  } else {
    fail('no add-to-cart button on the product page');
    await shot('product');
  }

  // ---- checkout -------------------------------------------------------
  await page.goto(`${SHOP}/checkout/`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000); // the block checkout hydrates client-side

  const isBlockCheckout = await page.locator('.wc-block-checkout, [data-block-name="woocommerce/checkout"]').count() > 0;
  console.log(`INFO checkout flavour: ${isBlockCheckout ? 'blocks' : 'shortcode'}`);

  // The assertion this tier exists for.
  const offered = await page.getByText(TITLE, { exact: false }).count();
  if (offered > 0) {
    pass(`the gateway is offered at ${isBlockCheckout ? 'block' : 'shortcode'} checkout`);
  } else {
    fail(`the gateway is NOT offered at ${isBlockCheckout ? 'block' : 'shortcode'} checkout`);
    await shot('checkout-no-gateway');
  }

  // ---- fill in the shopper's details ----------------------------------
  const fill = async (selectors, value) => {
    for (const sel of selectors) {
      const el = page.locator(sel).first();
      if (await el.count()) { await el.fill(value); return true; }
    }
    return false;
  };

  await fill(['#email', '#billing-email', '#billing_email'], 'tier3@example.com');
  await fill(['#billing-first_name', '#billing_first_name'], 'Tier');
  await fill(['#billing-last_name', '#billing_last_name'], 'Three');
  await fill(['#billing-address_1', '#billing_address_1'], '1 Test Street');
  await fill(['#billing-city', '#billing_city'], 'Vilnius');
  await fill(['#billing-postcode', '#billing_postcode'], '01100');
  await fill(['#billing-phone', '#billing_phone'], '0000000');

  // ---- choose SpectroCoin ---------------------------------------------
  const radio = page.locator('label', { hasText: TITLE }).first();
  if (await radio.count()) {
    await radio.click();
    pass('the gateway can be selected');
  } else {
    fail('the gateway could not be selected');
    await shot('checkout-select');
  }

  await page.waitForTimeout(1500);

  // ---- place the order ------------------------------------------------
  const placeOrder = page.locator(
    'button.wc-block-components-checkout-place-order-button, #place_order, button[type="submit"]:has-text("Place")'
  ).first();

  if (!(await placeOrder.count())) {
    fail('no place-order button');
    await shot('checkout-no-button');
  } else {
    await Promise.all([
      page.waitForURL(/spectrocoin\.com\/pay\//, { timeout: 45000 }).catch(() => {}),
      placeOrder.click(),
    ]);
    await page.waitForTimeout(3000);

    const url = page.url();
    console.log(`INFO landed on: ${url}`);
    if (/spectrocoin\.com\/pay\//.test(url)) {
      pass('placing the order redirects the shopper to SpectroCoin');
    } else {
      fail(`placing the order did not redirect to SpectroCoin (landed on ${url})`);
      await shot('after-place-order');
    }
  }
} catch (err) {
  fail(`browser run threw: ${err.message.split('\n')[0]}`);
  await shot('threw');
} finally {
  await browser.close();
}

process.exit(failed === 0 ? 0 : 1);
