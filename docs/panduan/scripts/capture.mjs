/**
 * capture.mjs — ARKARA PM screenshot pipeline.
 *
 * Drives the running Laravel app with Playwright (system Edge) and writes
 * authentic screenshots to docs/panduan/screens/, plus an inventory.md that
 * maps every figure back to its route, role and permission.
 *
 * Usage:
 *   php artisan serve --host=127.0.0.1 --port=8000     # terminal 1
 *   node docs/panduan/scripts/capture.mjs              # terminal 2
 *
 * Env: APP_URL (default http://127.0.0.1:8000)
 */
import { chromium } from 'file:///D:/school-book/node_modules/playwright-core/index.mjs';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const OUT = 'D:/school-book/docs/panduan/screens';
const VIEWPORT = { width: 1440, height: 900 };
const PASSWORD = 'password';

mkdirSync(OUT, { recursive: true });

const figures = [];
const failures = [];

const record = (file, meta) => figures.push({ file: `${file}.png`, ...meta, capturedAt: new Date().toISOString() });

async function shot(page, name, meta = {}, { full = true, clip = null } = {}) {
  const target = join(OUT, `${name}.png`);
  try {
    await page.waitForTimeout(meta.settle ?? 220);
    const options = { path: target, fullPage: clip ? false : full };
    if (clip) options.clip = clip;
    await page.screenshot(options);
    record(name, meta);
    console.log('  ok  ', name);
  } catch (error) {
    failures.push({ file: `${name}.png`, reason: error.message, ...meta });
    console.log('  FAIL', name, '-', error.message);
  }
}

async function start(client) {
  const page = await client.newPage();
  await page.addInitScript(() => {
    const css = '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}';
    const inject = () => {
      const style = document.createElement('style');
      style.textContent = css;
      (document.head || document.documentElement).appendChild(style);
    };
    if (document.head) inject();
    else document.addEventListener('DOMContentLoaded', inject);
  });
  return page;
}

async function go(page, url, settle = 250) {
  await page.goto(BASE + url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.waitForFunction(() => !!window.Alpine, { timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(settle);
}

async function login(page, email) {
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', PASSWORD);
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 25000 }),
    page.click('button[type=submit]'),
  ]);
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.waitForTimeout(200);
}

async function openModal(page, name) {
  await page.evaluate((n) => window.dispatchEvent(new CustomEvent('open-modal', { detail: n })), name);
  await page.waitForTimeout(400);
}

/**
 * Find the first record link for a resource on a list page. Record IDs depend
 * on the demo data, so the guide must never hard-code them.
 */
async function findRecord(page, listPath, segment) {
  await go(page, listPath);
  const href = await page.evaluate((seg) => {
    const pattern = new RegExp(`/${seg}/\\d+`);
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => pattern.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href') : null;
  }, segment);

  return href ? href.replace(/^https?:\/\/[^/]+/, '') : null;
}

async function gotoRecord(page, listPath, segment) {
  const href = await findRecord(page, listPath, segment);
  if (!href) throw new Error(`Tidak ada tautan /${segment}/… pada ${listPath}`);
  await go(page, href);
  return href;
}

/**
 * Like findRecord(), but only looks inside the first table row whose text
 * contains a given label. Used when a list has no status filter and the
 * wanted record is not simply the first row (for example, receipts).
 */
async function findRecordByText(page, listPath, segment, needle) {
  await go(page, listPath);
  const href = await page.evaluate(({ seg, text }) => {
    const pattern = new RegExp(`/${seg}/\\d+`);
    const row = [...document.querySelectorAll('tr')]
      .find((tr) => (tr.textContent || '').includes(text));
    if (!row) return null;
    const link = [...row.querySelectorAll('a[href]')]
      .find((anchor) => pattern.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href') : null;
  }, { seg: segment, text: needle });

  return href ? href.replace(/^https?:\/\/[^/]+/, '') : null;
}

/**
 * Bounding box for the shipment page header plus its confirm form, so the
 * "proof photo is mandatory" figure shows the title and action together
 * instead of a 40px-tall strip containing only the form controls.
 */
async function confirmBandBox(page) {
  return page.evaluate(() => {
    const form = document.querySelector('form[action$="/confirm"]');
    const title = document.querySelector('h1');
    if (!form || !title) return null;

    const f = form.getBoundingClientRect();
    const t = title.getBoundingClientRect();
    const left = Math.max(0, t.left - 56);
    const top = Math.max(0, t.top - 16);
    const right = Math.min(window.innerWidth, Math.max(f.right, t.right) + 16);
    const bottom = Math.max(f.bottom, t.bottom) + 16;

    return { x: left, y: top, width: right - left, height: bottom - top };
  });
}

const browser = await chromium.launch({ channel: 'msedge', headless: true });

// ─────────────────────────────────────────────────────────────── A. Auth (public)
{
  const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
  const page = await start(client);

  await go(page, '/login');
  await shot(page, '01-login', { section: 'Bagian II — Masuk', route: 'GET /login', role: 'publik' }, { full: false });

  await page.fill('input[name=email]', 'admin@sekolah.test');
  await page.fill('input[name=password]', 'kata-sandi-salah');
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(300);
  await shot(page, '02-login-error', { section: 'Bagian II, VII — Masuk', route: 'POST /login', role: 'publik' }, { full: false });

  await client.close();
}

// ─────────────────────────────────────────────────────────────── B. Role dashboards (6 logins)
// Non-Admin accounts are blocked outside working hours by EnsureWorkingHours.
// Run this pipeline between 08:00 and 17:00, or temporarily widen the
// work.default_start / work.default_end settings, otherwise those dashboards
// are captured as the 403 "Akses Ditolak" page.
const ROLES = [
  { key: 'admin', email: 'admin@sekolah.test', file: '20-dashboard-admin' },
  { key: 'operator', email: 'operator@sekolah.test', file: '21-dashboard-operator' },
  { key: 'gudang', email: 'gudang@sekolah.test', file: '22-dashboard-gudang' },
  { key: 'keuangan', email: 'keuangan@sekolah.test', file: '23-dashboard-keuangan' },
  { key: 'sales', email: 'sales@sekolah.test', file: '24-dashboard-sales' },
  { key: 'pimpinan', email: 'pimpinan@sekolah.test', file: '25-dashboard-pimpinan' },
];

for (const role of ROLES) {
  const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
  const page = await start(client);
  try {
    await login(page, role.email);
    const title = await page.locator('h1').first().textContent().catch(() => '');
    // Dashboards are long; capture the viewport so the figure stays legible in
    // print instead of becoming a page-tall image.
    await shot(page, role.file, { section: `Bagian III/IV — Dashboard ${role.key}`, route: 'GET /dashboard', role: role.key, heading: (title || '').trim() }, { full: false });

    if (role.key === 'admin') {
      const sidebarWidth = await page.evaluate(() => {
        const el = document.querySelector('aside > div');
        return el ? Math.round(el.getBoundingClientRect().width) : 272;
      });

      // Open one navigation group so the shell/sidebar figures show how a
      // grouped menu looks. The dashboard itself (20-dashboard-admin) keeps
      // every group closed, so the two figures no longer show the same pixels.
      const ordersGroup = page.locator('nav[aria-label="Navigasi utama"] button:has-text("Pesanan")').first();
      const openedGroup = await ordersGroup.count() > 0;
      if (openedGroup) {
        await ordersGroup.click();
        await page.waitForTimeout(250);
      }

      // Full shell (sidebar + topbar) and the sidebar alone.
      await shot(page, '10-shell', { section: 'Bagian I/II — Kerangka aplikasi', route: 'GET /dashboard', role: role.key }, { full: false });
      await shot(page, '10-sidebar', { section: 'Bagian I/II — Sidebar', route: 'partials/sidebar.blade.php', role: role.key }, { clip: { x: 0, y: 0, width: sidebarWidth, height: VIEWPORT.height } });

      if (openedGroup) {
        await ordersGroup.click();
        await page.waitForTimeout(200);
      }

      await shot(page, '11-topbar', { section: 'Bagian II — Topbar', route: 'partials/topbar.blade.php', role: role.key }, { clip: { x: sidebarWidth, y: 0, width: VIEWPORT.width - sidebarWidth, height: 64 } });

      // Global search (Ctrl+K) with live results.
      await page.keyboard.press('Control+k');
      await page.waitForTimeout(200);
      await page.locator('input[placeholder^="Cari pesanan"]').fill('TK');
      await page.waitForTimeout(1200);
      await shot(page, '12-global-search', { section: 'Bagian II — Pencarian global', route: 'GET /search?q=', role: role.key }, { full: false });
      await page.keyboard.press('Escape');
      await page.waitForTimeout(200);

      // Notifications panel.
      await page.click('button[aria-label="Notifikasi"]');
      await page.waitForTimeout(900);
      await shot(page, '13-notifications', { section: 'Bagian II — Notifikasi', route: 'GET /notifications', role: role.key }, { full: false });
      await page.keyboard.press('Escape');
      await page.waitForTimeout(200);

      // Account menu.
      await page.click('button[aria-label="Menu akun"]');
      await page.waitForTimeout(300);
      await shot(page, '14-user-menu', { section: 'Bagian II — Menu akun', route: 'partials/topbar.blade.php', role: role.key }, { full: false });
      await page.keyboard.press('Escape');

      // Dark theme sample.
      await page.evaluate(() => localStorage.setItem('bookflow.theme', 'dark'));
      await go(page, '/reports?type=sales_by_book');
      await shot(page, '15-dark-sample', { section: 'Bagian II — Tema gelap', route: 'GET /reports', role: role.key }, { full: false });
      await page.evaluate(() => localStorage.setItem('bookflow.theme', 'light'));
    }
  } catch (error) {
    failures.push({ file: role.file + '.png', reason: error.message, role: role.key });
    console.log('  FAIL', role.key, '-', error.message);
  }
  await client.close();
}

// ─────────────────────────────────────────────────────────────── C. Admin deep dive
const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
const page = await start(client);
await login(page, 'admin@sekolah.test');

const ADMIN = { role: 'admin' };

// C1. Master data — every resource currently registered in the sidebar.
const MASTER = [
  ['schools', '30-master-schools'],
  ['publishers', '32-master-publishers'],
  ['categories', '34-master-categories'],
  ['books', '36-master-books'],
  ['barang-lainnya', '38-master-goods'],
  ['order-sources', '42-master-order-sources'],
  ['couriers', '44-master-couriers'],
  ['shipping-methods', '46-master-shipping-methods'],
  ['payment-methods', '48-master-payment-methods'],
  ['settings', '52-master-settings'],
  ['cash-loans', '54-master-cash-loans'],
];

for (const [resource, file] of MASTER) {
  await go(page, `/master/${resource}`);
  const heading = (await page.locator('h1').first().textContent().catch(() => '')) || '';
  await shot(page, file, { section: 'Bagian IV — Master Data', route: `GET /master/${resource}`, ...ADMIN, heading: heading.trim() });
}

// C2. Master data — create forms (Sekolah, Buku, Barang Lainnya, Penerbit, Pinjaman Tunai)
const FORMS = [
  ['schools', '31-master-schools-form'],
  ['books', '37-master-books-form'],
  ['barang-lainnya', '39-master-goods-form'],
  ['publishers', '33-master-publishers-form'],
  ['cash-loans', '55-master-cash-loans-form'],
];

for (const [resource, file] of FORMS) {
  await go(page, `/master/${resource}/create`);
  await shot(page, file, { section: 'Bagian IV — Master Data', route: `GET /master/${resource}/create`, ...ADMIN });
}

// C2b. Delete blocked because the record is referenced (publisher that still
// has books; the FK is RESTRICT). Never hard-code the publisher id — pick the
// publisher of the first book, so a reseed or an extra publisher cannot turn
// this into a real deletion.
try {
  await go(page, '/master/books');
  const publisherName = await page.evaluate(() => {
    const names = [...document.querySelectorAll('table tbody tr')]
      .map((row) => (row.cells[2]?.textContent || '').trim())
      .filter(Boolean);
    return names[0] || null;
  });
  if (!publisherName) throw new Error('Tidak ada penerbit pada daftar buku.');

  await go(page, '/master/publishers');
  const deleteAction = await page.evaluate((name) => {
    const row = [...document.querySelectorAll('table tbody tr')]
      .find((tr) => (tr.textContent || '').includes(name));
    if (!row) return null;
    const form = row.querySelector('form[action*="/master/publishers/"]');
    if (!form) return null;
    const action = form.getAttribute('action') || '';
    form.submit();
    return action.replace(/^https?:\/\/[^/]+/, '');
  }, publisherName);
  if (!deleteAction) throw new Error(`Baris penerbit "${publisherName}" tidak ditemukan.`);

  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(500);
  const rejected = (await page.locator('body').innerText()).includes('tidak dapat dihapus');
  if (!rejected) throw new Error('Pesan penolakan penghapusan tidak muncul.');

  await shot(page, '57-master-delete-error', { section: 'Bagian IV, VII — Master Data', route: `DELETE ${deleteAction}`, ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '57-master-delete-error.png', reason: error.message });
}

// C3. Orders
await go(page, '/orders');
await shot(page, '60-orders-index', { section: 'Bagian IV — Pesanan', route: 'GET /orders', ...ADMIN });

await go(page, '/orders?source=SIPLAH&status=fully_shipped');
await shot(page, '61-orders-filter', { section: 'Bagian IV — Pesanan', route: 'GET /orders?source=&status=', ...ADMIN });

await go(page, '/orders/create');
await shot(page, '62-orders-create', { section: 'Bagian IV — Pesanan', route: 'GET /orders/create', ...ADMIN });

// Item combobox open.
try {
  await page.locator('input[placeholder="Cari/pilih barang..."]').first().click();
  await page.locator('input[placeholder="Cari/pilih barang..."]').first().fill('matematika');
  await page.waitForTimeout(600);
  await shot(page, '63-orders-item-picker', { section: 'Bagian IV — Pesanan', route: 'orders/partials/item-book.blade.php', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '63-orders-item-picker.png', reason: error.message });
}

// Validation error state (submit the blank form).
try {
  await go(page, '/orders/create');
  await page.click('button[type=submit]:has-text("Simpan Pesanan")');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(500);
  await shot(page, '64-orders-validation', { section: 'Bagian IV, VII — Pesanan', route: 'POST /orders', ...ADMIN });
} catch (error) {
  failures.push({ file: '64-orders-validation.png', reason: error.message });
}

try {
  const orderShow = await gotoRecord(page, '/orders', 'orders');
  await shot(page, '65-orders-show', { section: 'Bagian IV — Pesanan', route: 'GET /orders/{order}', ...ADMIN });
  await go(page, `${orderShow.replace(/\/$/, '')}/print`);
  await shot(page, '69-orders-print', { section: 'Bagian V — Dokumen cetak', route: 'GET /orders/{order}/print', ...ADMIN });
  await go(page, `${orderShow.replace(/\/$/, '')}/print-surat`);
  await shot(page, '69b-orders-print-surat', { section: 'Bagian V — Dokumen cetak', route: 'GET /orders/{order}/print-surat', ...ADMIN });
} catch (error) {
  failures.push({ file: '65-orders-show.png', reason: error.message });
}

try {
  await go(page, '/orders?status=fully_shipped');
  const shipped = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => /\/orders\/\d+$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (shipped) {
    await go(page, shipped);
    await shot(page, '66-orders-show-shipped', { section: 'Bagian IV — Pesanan', route: 'GET /orders/{order}', ...ADMIN });
  } else {
    failures.push({ file: '66-orders-show-shipped.png', reason: 'Tidak ada pesanan berstatus terkirim penuh pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '66-orders-show-shipped.png', reason: error.message });
}

// C4. Goods receipts
await go(page, '/receipts');
await shot(page, '70-receipts-index', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts', ...ADMIN });

await go(page, '/receipts/create');
await shot(page, '71-receipts-create', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/create', ...ADMIN });

try {
  const confirmedReceipt = await findRecordByText(page, '/receipts', 'receipts', 'Dikonfirmasi');
  if (!confirmedReceipt) throw new Error('Tidak ada penerimaan berstatus Dikonfirmasi pada data demo.');
  await go(page, confirmedReceipt);
  await shot(page, '72-receipts-show', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/{receipt}', ...ADMIN });

  const draftReceipt = await findRecordByText(page, '/receipts', 'receipts', 'Draft');
  if (!draftReceipt) throw new Error('Tidak ada penerimaan berstatus Draft pada data demo.');
  await go(page, draftReceipt);
  await shot(page, '72b-receipts-show-draft', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/{receipt}', ...ADMIN });
} catch (error) {
  failures.push({ file: '72-receipts-show.png', reason: error.message });
}

// C5. Stock
await go(page, '/stock-summary');
await shot(page, '80-stock-summary', { section: 'Bagian IV — Stok', route: 'GET /stock-summary', ...ADMIN });

await go(page, '/stock-mutations');
await shot(page, '81-stock-mutations', { section: 'Bagian IV — Stok', route: 'GET /stock-mutations', ...ADMIN });

try {
  const mutationPrint = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => /\/stock-mutations\/\d+\/print$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (mutationPrint) {
    await go(page, mutationPrint);
    await shot(page, '82-stock-mutation-print', { section: 'Bagian IV/V — Stok', route: 'GET /stock-mutations/{mutation}/print', ...ADMIN });
  } else {
    failures.push({ file: '82-stock-mutation-print.png', reason: 'Tidak ada mutasi stok untuk dicetak pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '82-stock-mutation-print.png', reason: error.message });
}

// C6. Picking & packing
await go(page, '/pickings');
await shot(page, '90-pickings-index', { section: 'Bagian IV — Picking', route: 'GET /pickings', ...ADMIN });

try {
  await go(page, '/pickings?status=draft');
  const draftPicking = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => /\/pickings\/\d+$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (draftPicking) {
    await go(page, draftPicking);
    await shot(page, '91-pickings-show-draft', { section: 'Bagian IV — Picking', route: 'GET /pickings/{picking}', ...ADMIN });
  } else {
    failures.push({ file: '91-pickings-show-draft.png', reason: 'Tidak ada picking berstatus draft pada data demo.' });
  }

  await go(page, '/pickings?status=picked');
  const pickedPicking = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => /\/pickings\/\d+$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (pickedPicking) {
    await go(page, pickedPicking);
    await shot(page, '92-pickings-show-picked', { section: 'Bagian IV — Picking', route: 'GET /pickings/{picking}', ...ADMIN });
  } else {
    failures.push({ file: '92-pickings-show-picked.png', reason: 'Tidak ada picking berstatus picked pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '91-pickings-show-draft.png', reason: error.message });
}

// C6b. Back orders (Pengadaan)
await go(page, '/backorders');
await shot(page, '85-backorders-index', { section: 'Bagian IV — Pengadaan', route: 'GET /backorders', ...ADMIN });

await go(page, '/backorders/unavailable');
await shot(page, '86-backorders-unavailable', { section: 'Bagian IV — Pengadaan', route: 'GET /backorders/unavailable', ...ADMIN });

try {
  const backorderOrder = await findRecord(page, '/backorders', 'backorders/orders');
  if (backorderOrder) {
    await go(page, backorderOrder);
    await shot(page, '87-backorders-order', { section: 'Bagian IV — Pengadaan', route: 'GET /backorders/orders/{backorderOrder}', ...ADMIN });
    await go(page, `${backorderOrder.replace(/\/$/, '')}/print`);
    await shot(page, '88-backorders-print', { section: 'Bagian IV/V — Pengadaan', route: 'GET /backorders/orders/{backorderOrder}/print', ...ADMIN });
  } else {
    failures.push({ file: '87-backorders-order.png', reason: 'Belum ada pesanan ke penerbit pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '87-backorders-order.png', reason: error.message });
}

// C7. Shipments
await go(page, '/shipments');
await shot(page, '100-shipments-index', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments', ...ADMIN });

await go(page, '/shipments/create');
await shot(page, '101-shipments-create-1', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create', ...ADMIN });

try {
  await page.selectOption('select[name=order_id]', { index: 1 });
  await page.click('button[type=submit]:has-text("Lanjutkan")');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(400);
  await shot(page, '102-shipments-create-2', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create?order_id=', ...ADMIN });

  // Filled example of the same step (nothing is submitted). The form is
  // intentionally completed so this figure differs from the blank step above.
  const courier = page.locator('select[name=courier_id]');
  const method = page.locator('select[name=shipping_method_id]');
  for (const select of [courier, method]) {
    if (await select.count()) {
      const value = await select.locator('option').nth(1).getAttribute('value');
      if (value) await select.selectOption(value);
    }
  }

  const fill = async (selector, value) => {
    const field = page.locator(selector);
    if (await field.count()) await field.fill(value);
  };
  await fill('input[name=tracking_number]', 'JNE-2026-001234');
  await fill('input[name=recipient_name]', 'Petugas Sekolah');
  await fill('input[name=address]', 'Jl. Pendidikan No. 1, Makassar');
  await fill('textarea[name=notes]', 'Dikirim pagi, mohon diterima petugas sekolah.');

  // Send only half of the first line to show a partial shipment example.
  const firstQty = page.locator('input[name^="items["][name$="[quantity]"]').first();
  if (await firstQty.count()) {
    const max = Number(await firstQty.getAttribute('max'));
    if (Number.isFinite(max) && max > 1) await firstQty.fill(String(Math.max(1, Math.floor(max / 2))));
  }

  await shot(page, '102b-shipments-create-2-filled', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create?order_id=', ...ADMIN });
} catch (error) {
  failures.push({ file: '102-shipments-create-2.png', reason: error.message });
}

let shipmentShow = null;
try {
  const shipped = await findRecord(page, '/shipments?status=shipped', 'shipments');
  if (!shipped) throw new Error('Tidak ada pengiriman berstatus Terkirim pada data demo.');
  await go(page, shipped);
  shipmentShow = shipped;
  await shot(page, '103-shipments-show', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/{shipment}', ...ADMIN });
} catch (error) {
  failures.push({ file: '103-shipments-show.png', reason: error.message });
}

// Draft detail plus the mandatory proof-photo confirm form.
let draftShipmentCaptured = false;
try {
  const draftShipment = await findRecord(page, '/shipments?status=draft', 'shipments');
  if (!draftShipment) throw new Error('Tidak ada pengiriman berstatus Draft pada data demo.');
  await go(page, draftShipment);
  await shot(page, '103b-shipments-show-unconfirmed', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/{shipment}', ...ADMIN });

  const confirmForm = page.locator('form[action$="/confirm"]').first();
  if (await confirmForm.count()) {
    const band = await confirmBandBox(page);
    if (!band) throw new Error('Bilah judul pengiriman tidak dapat diukur.');
    await page.screenshot({ path: join(OUT, '104-shipments-confirm-form.png'), clip: band });
    record('104-shipments-confirm-form', { section: 'Bagian IV — Pengiriman', route: 'POST /shipments/{shipment}/confirm', ...ADMIN });
    console.log('  ok   104-shipments-confirm-form');
    draftShipmentCaptured = true;
  } else {
    failures.push({ file: '104-shipments-confirm-form.png', reason: 'Pengiriman draft tidak menampilkan form konfirmasi.' });
  }
} catch (error) {
  failures.push({ file: '103b-shipments-show-unconfirmed.png', reason: error.message });
}

if (shipmentShow) {
  await go(page, `${shipmentShow.replace(/\/$/, '')}/print`);
  await shot(page, '105-shipments-print', { section: 'Bagian IV/V — Dokumen cetak', route: 'GET /shipments/{shipment}/print', ...ADMIN });
}

// C8. Invoices
await go(page, '/invoices');
await shot(page, '110-invoices-index', { section: 'Bagian IV — Invoice', route: 'GET /invoices', ...ADMIN });

try {
  const invoiceShow = await gotoRecord(page, '/invoices', 'invoices');
  await shot(page, '111-invoices-show', { section: 'Bagian IV — Invoice', route: 'GET /invoices/{invoice}', ...ADMIN });
  await go(page, `${invoiceShow.replace(/\/$/, '')}/print`);
  await shot(page, '112-invoices-print', { section: 'Bagian IV/VI — Dokumen cetak', route: 'GET /invoices/{invoice}/print', ...ADMIN });
} catch (error) {
  failures.push({ file: '111-invoices-show.png', reason: error.message });
}

// C9. Receivables / hutang
await go(page, '/receivables');
await shot(page, '120-receivables', { section: 'Bagian IV — Piutang', route: 'GET /receivables', ...ADMIN });

await go(page, '/finance/hutang');
await shot(page, '121-hutang', { section: 'Bagian IV — Piutang', route: 'GET /finance/hutang', ...ADMIN });

// C10. Payments
await go(page, '/payments');
await shot(page, '130-payments-index', { section: 'Bagian IV — Pembayaran', route: 'GET /payments', ...ADMIN });

await openModal(page, 'record-payment');
await shot(page, '131-payments-record', { section: 'Bagian IV — Pembayaran', route: 'POST /payments', ...ADMIN }, { full: false });

try {
  const editHref = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href*="/payments/"]')]
      .find((anchor) => /\/payments\/\d+\/edit$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (editHref) {
    await go(page, editHref);
    await shot(page, '132-payments-edit', { section: 'Bagian IV — Pembayaran', route: 'GET /payments/{payment}/edit', ...ADMIN });
  } else {
    failures.push({ file: '132-payments-edit.png', reason: 'Tidak ada pembayaran draft untuk diubah pada data demo.' });
  }

  await go(page, '/payments');
  const receiptHref = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href*="/receipt"]')]
      .find((anchor) => /\/payments\/\d+\/allocations\/\d+\/receipt$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (receiptHref) {
    await go(page, receiptHref);
    await shot(page, '133-payments-receipt', { section: 'Bagian IV/V — Pembayaran', route: 'GET /payments/{payment}/allocations/{allocation}/receipt', ...ADMIN });
  } else {
    failures.push({ file: '133-payments-receipt.png', reason: 'Tidak ada kwitansi pembayaran pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '132-payments-edit.png', reason: error.message });
}

// C11. Rebates — a recap of the automatic school rebate, no manual approval.
await go(page, '/rebates');
await shot(page, '140-rebates-index', { section: 'Bagian IV — Rebate', route: 'GET /rebates', ...ADMIN });

// C12. Reports
await go(page, '/reports');
await shot(page, '149-reports-index', { section: 'Bagian VI — Laporan', route: 'GET /reports', ...ADMIN });

const REPORTS = [
  ['sales_by_school', '150-reports-sales-by-school'],
  ['sales_by_publisher', '151-reports-sales-by-publisher'],
  ['sales_by_book', '152-reports-sales-by-book'],
  ['order_summary', '153-reports-orders-summary'],
  ['receivables', '154-reports-receivables'],
  ['overdue_invoices', '155-reports-overdue'],
  ['backorders', '158-reports-backorders'],
];

for (const [type, file] of REPORTS) {
  await go(page, `/reports?type=${type}`);
  await shot(page, file, { section: 'Bagian VI — Laporan', route: `GET /reports?type=${type}`, ...ADMIN }, { full: false });
}

// C13. Settings & integrations
await go(page, '/settings/company');
await shot(page, '160-settings-company', { section: 'Bagian IV — Pengaturan', route: 'GET /settings/company', ...ADMIN });

await go(page, '/settings/users');
await shot(page, '161-settings-users', { section: 'Bagian IV — Pengaturan', route: 'GET /settings/users', ...ADMIN });

await openModal(page, 'create-user');
await shot(page, '162-settings-user-create', { section: 'Bagian IV — Pengaturan', route: 'POST /settings/users', ...ADMIN }, { full: false });

await go(page, '/settings/roles');
await shot(page, '163-settings-roles', { section: 'Bagian III/IV — Peran & Izin', route: 'GET /settings/roles', ...ADMIN });

try {
  await page.locator('button:has-text("Gudang")').first().click();
  await page.waitForTimeout(500);
  await shot(page, '164-settings-role-edit', { section: 'Bagian IV — Peran & Izin', route: 'PUT /settings/roles/{role}', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '164-settings-role-edit.png', reason: error.message });
}

await go(page, '/settings/audit');
await shot(page, '165-settings-audit', { section: 'Bagian IV/VIII — Audit Log', route: 'GET /settings/audit', ...ADMIN });

await go(page, '/settings/overtimes');
await shot(page, '167-settings-overtimes', { section: 'Bagian IV — Pengaturan', route: 'GET /settings/overtimes', ...ADMIN });

await go(page, '/integrations');
await shot(page, '166-integrations', { section: 'Bagian IV — Integrasi', route: 'GET /integrations', ...ADMIN });

// C14. Catalog
await go(page, '/catalogs');
await shot(page, '170-catalogs-index', { section: 'Bagian IV — Katalog', route: 'GET /catalogs', ...ADMIN });

await go(page, '/catalogs/create');
await shot(page, '171-catalogs-create', { section: 'Bagian IV — Katalog', route: 'GET /catalogs/create', ...ADMIN });

try {
  await go(page, '/catalogs');
  const catalogEdit = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href]')]
      .find((anchor) => /\/catalogs\/\d+\/edit$/.test(anchor.getAttribute('href') || ''));
    return link ? link.getAttribute('href').replace(/^https?:\/\/[^/]+/, '') : null;
  });
  if (catalogEdit) {
    await go(page, catalogEdit);
    await shot(page, '172-catalogs-edit', { section: 'Bagian IV — Katalog', route: 'GET /catalogs/{catalog}/edit', ...ADMIN });
  } else {
    failures.push({ file: '172-catalogs-edit.png', reason: 'Tidak ada katalog pada data demo.' });
  }
} catch (error) {
  failures.push({ file: '172-catalogs-edit.png', reason: error.message });
}

// Delete blocked — admin cannot delete their own account (403, non-destructive).
try {
  await go(page, '/settings/users');
  const blocked = await page.evaluate(() => {
    const row = [...document.querySelectorAll('table tbody tr')]
      .find((tr) => (tr.textContent || '').includes('admin@sekolah.test'));
    if (!row) return false;
    const form = row.querySelector('form[action*="/settings/users/"]');
    if (!form) return false;
    form.submit();
    return true;
  });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(400);
  if (blocked) await shot(page, '57b-user-delete-blocked', { section: 'Bagian IV, VII — Pengaturan', route: 'DELETE /settings/users/{user}', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '57b-user-delete-blocked.png', reason: error.message });
}

// ─────────────────────────────────────────────────────────────── D. Optional: create a shipment (MUTATES demo data)
// Guarded by CAPTURE_MUTATIONS=1 so a plain re-run stays read-only. The
// shipment created here is demo data; roll it back with
// `php artisan db:seed --class=DemoDataResetSeeder`.
const mutations = [];

if (process.env.CAPTURE_MUTATIONS === '1') {
  try {
    // Prefer a draft shipment that already exists so re-runs stay idempotent;
    // only create one when the demo data has none. 103b/104 were already
    // captured above when a draft existed.
    let target = null;
    await go(page, '/shipments?status=draft');
    const existing = page.locator('table tbody tr a[href*="/shipments/"]').first();
    if (await existing.count()) {
      // Hrefs may be absolute (app URL); reduce to a path before navigating.
      target = (await existing.getAttribute('href')).replace(/^https?:\/\/[^/]+/, '');
    }

    if (!target) {
      await go(page, '/shipments/create');
      const orderValue = await page.locator('select[name=order_id] option').nth(1).getAttribute('value');
      const orderLabel = await page.locator('select[name=order_id] option').nth(1).textContent();

      if (orderValue) {
        await page.selectOption('select[name=order_id]', orderValue);
        await page.click('button[type=submit]:has-text("Lanjutkan")');
        await page.waitForLoadState('networkidle').catch(() => {});
        await page.waitForTimeout(400);

        const submit = page.locator('button[type=submit]:has-text("Simpan Pengiriman")');
        if ((await submit.count()) && (await submit.isEnabled())) {
          await Promise.all([page.waitForLoadState('networkidle').catch(() => {}), submit.click()]);
          await page.waitForTimeout(700);
          target = page.url().replace(BASE, '');
          mutations.push(`Membuat pengiriman baru dari pesanan ${orderLabel?.trim()} → ${target}`);
        }
      }
    }

    if (target && !draftShipmentCaptured) {
      await go(page, target);
      await shot(page, '103b-shipments-show-unconfirmed', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/{shipment}', ...ADMIN });

      const confirmForm = page.locator('form[action$="/confirm"]').first();
      if (await confirmForm.count()) {
        const band = await confirmBandBox(page);
        if (band) {
          await page.screenshot({ path: join(OUT, '104-shipments-confirm-form.png'), clip: band });
          record('104-shipments-confirm-form', { section: 'Bagian IV — Pengiriman', route: 'POST /shipments/{shipment}/confirm', ...ADMIN });
          console.log('  ok   104-shipments-confirm-form');
        } else {
          failures.push({ file: '104-shipments-confirm-form.png', reason: 'Bilah judul pengiriman tidak dapat diukur.' });
        }
      } else {
        failures.push({ file: '104-shipments-confirm-form.png', reason: 'Pengiriman tidak menampilkan form konfirmasi (sudah dikonfirmasi).' });
      }
    }
  } catch (error) {
    failures.push({ file: 'bonus-shipment.png', reason: error.message });
    console.log('  FAIL bonus shipment -', error.message);
  }

  writeFileSync(
    join(OUT, 'mutations.md'),
    [
      '# Mutasi Data Demo saat Penangkapan Gambar',
      '',
      ...(mutations.length
        ? mutations.map((m) => `- ${m}`)
        : ['- Tidak ada perubahan data: seluruh tangkapan layar memakai data demo yang sudah ada.']),
      '',
      ...(mutations.length ? ['Pulihkan dengan: `php artisan db:seed --class=DemoDataResetSeeder`', ''] : ['']),
    ].join('\n'),
  );
}

await client.close();
await browser.close();

// ─────────────────────────────────────────────────────────────── Inventory
// Inventory date uses the local calendar day (the capture runs during the
// workday in Asia/Makassar), not the UTC day, so it matches the QA report.
const today = new Date();
const now = [
  today.getFullYear(),
  String(today.getMonth() + 1).padStart(2, '0'),
  String(today.getDate()).padStart(2, '0'),
].join('-');
const lines = [
  '# Inventaris Screenshot — ARKARA PM',
  '',
  `Dibuat otomatis oleh \`scripts/capture.mjs\` pada ${now}. Semua gambar diambil dari aplikasi`,
  'yang berjalan pada data demo (akun `@sekolah.test`), bukan mockup.',
  '',
  `Total gambar: **${figures.length}**. Gagal/tidak tersedia: **${failures.length}**.`,
  '',
  '| # | Berkas | Bagian buku | Route | Peran |',
  '|---|---|---|---|---|',
];

figures.forEach((figure, index) => {
  lines.push(`| ${index + 1} | \`${figure.file}\` | ${figure.section || ''} | \`${figure.route || ''}\` | ${figure.role || ''} |`);
});

// A figure that failed once may succeed later (e.g. the optional mutation
// pass); only report it as missing if it never landed on disk.
const captured = new Set(figures.map((figure) => figure.file));
const missing = failures.filter((failure) => !captured.has(failure.file));

lines.push('', '## Gambar yang tidak dapat diambil', '', '| Berkas | Alasan |', '|---|---|');
if (missing.length === 0) lines.push('| — | Semua gambar berhasil diambil. |');
missing.forEach((failure) => lines.push(`| \`${failure.file}\` | ${String(failure.reason).split('\n')[0]} |`));

writeFileSync(join(OUT, 'inventory.md'), lines.join('\n') + '\n');

console.log(`\nSelesai. ${figures.length} gambar, ${failures.length} gagal.`);
console.log(`Inventaris: ${join(OUT, 'inventory.md')}`);
