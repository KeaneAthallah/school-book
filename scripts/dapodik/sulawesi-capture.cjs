#!/usr/bin/env node
/**
 * Dapodik school capture for Sulawesi Tengah + Sulawesi Barat (one-time, resumable, no scheduler).
 *
 * Based on mjcc's scripts/dapodik/dapodik-capture.cjs but:
 *  - sweeps every kabupaten/kota of the two target provinces Sulawesi Tengah
 *    and Sulawesi Barat (not one region),
 *  - writes one snapshot per kabupaten (resumable),
 *  - captures ALL jenjang (PAUD included) — no bentuk_pendidikan whitelist,
 *  - is meant to be driven by `php artisan schools:import-dapodik`.
 *
 * Why a browser: dapo.kemendikdasmen.go.id shields its school API behind a SafeLine
 * WAF (TLS fingerprinting + JS challenge). A plain HTTP client is rejected with
 * 502/400, but a real browser passes, so Playwright (MS Edge chromium) is required.
 *
 * Prereqs: Node.js >= 18, MS Edge installed, and `npm i --no-save playwright-core`.
 *
 * Env:
 *   DAPODIK_PACE_MS   rate per HTTP request in the browser page (default 500)
 *   DAPODIK_TYPES     optional comma list of bentuk_pendidikan to keep (default: all)
 */

const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const BASE = 'https://dapo.kemendikdasmen.go.id';
const SLOW_BY = Number(process.env.DAPODIK_PACE_MS || 500);

// Target provinces (Sulawesi Tengah, Sulawesi Barat). Only these are swept, even
// if the seed file lists more, so the capture can never expand past this scope.
// Codes are the portal's own (see /api/progress-pengiriman/provinsi): 180000 =
// Sulawesi Tengah, 330000 = Sulawesi Barat. Do not confuse 220000 (that is Bali).
const PROVINCES = ['180000', '330000'];

// Resolve project root from this file's location (scripts/dapodik/<file> -> project root).
const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const OUT_DIR = path.join(
    PROJECT_ROOT,
    'storage',
    'app',
    'data',
    'dapodik',
    'sulawesi',
);
const PROVINCES_SEED = path.join(
    PROJECT_ROOT,
    'storage',
    'app',
    'data',
    'dapodik',
    'sulawesi-kabupaten.json',
);
const CACHE_FILE = path.join(
    PROJECT_ROOT,
    'storage',
    'app',
    'data',
    'dapodik',
    'sulawesi-kabupaten-cache.json',
);

const TYPES = process.env.DAPODIK_TYPES
    ? process.env.DAPODIK_TYPES.split(',')
          .map((t) => t.trim().toUpperCase())
          .filter(Boolean)
    : null; // null = capture everything (PAUD included)

function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

function args() {
    const out = { region: null, force: false };
    process.argv.slice(2).forEach((a) => {
        if (a === '--force') out.force = true;
        else if (a.startsWith('--region='))
            out.region = a.slice('--region='.length);
    });
    return out;
}

async function fetchJson(page, pathname, token) {
    const r = await page.evaluate(
        async ({ path: p, tok }) => {
            const res = await fetch(p, {
                headers: {
                    Authorization: `Bearer ${tok}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
            });
            return { status: res.status, text: await res.text() };
        },
        { path: pathname, tok: token },
    );
    if (r.status !== 200) {
        throw new Error(`${pathname} -> HTTP ${r.status}`);
    }
    return JSON.parse(r.text);
}

function readJson(file) {
    if (!fs.existsSync(file)) return null;
    try {
        return JSON.parse(fs.readFileSync(file, 'utf8'));
    } catch {
        return null;
    }
}

function writeJson(file, data) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, JSON.stringify(data, null, 2));
}

async function discoverKabupaten(page, token, province) {
    // Try the public kabupaten roster by province (analogous to the per-kecamatan
    // endpoint mjcc already relies on). The existence of this endpoint is verified
    // at runtime inside the captured browser session where the bearer token is valid.
    const url = `/api/progress-pengiriman/kabupaten?kode_provinsi=${province.kode_provinsi}`;
    try {
        const body = await fetchJson(page, url, token);
        const rows = (body && body.data ? body.data : body || []).filter(
            (r) => String(r.kode_kabupaten || '').length >= 4,
        );
        // The endpoint filters by province, but guard against a stale/wrong province
        // code silently sweeping another province's kabupaten (rows carry provinsi).
        const own = rows.filter(
            (r) =>
                !r.kode_provinsi ||
                String(r.kode_provinsi) === String(province.kode_provinsi),
        );
        if (own.length !== rows.length) {
            console.log(
                `  discover ${province.nama}: dropped ${rows.length - own.length} kabupaten from another province`,
            );
        }
        return own.map((r) => ({
            kode_kabupaten: String(r.kode_kabupaten),
            kabupaten: String(r.kabupaten || r.nama || ''),
            kode_provinsi: province.kode_provinsi,
            provinsi: province.nama,
        }));
    } catch (e) {
        console.log(`  discover ${province.nama}: ${e.message.slice(0, 120)}`);
        return [];
    }
}

async function captureKabupaten(page, token, kab, force) {
    const outFile = path.join(OUT_DIR, `${kab.kode_kabupaten}.json`);
    if (!force && fs.existsSync(outFile)) {
        const snap = readJson(outFile);
        console.log(
            `skip ${kab.kabupaten} (${kab.kode_kabupaten}) — snapshot exists (${snap ? (snap.schools?.length ?? 0) : '?'} schools)`,
        );
        return false;
    }

    let kecamatan = [];
    try {
        const body = await fetchJson(
            page,
            `/api/progress-pengiriman/kecamatan?kode_kabupaten=${kab.kode_kabupaten}`,
            token,
        );
        let rows = body && body.data ? body.data : body || [];
        // Keep only kecamatan that actually belong to this kabupaten (filters stray rows).
        kecamatan = rows.filter((r) =>
            String(r.kode_kabupaten).startsWith(kab.kode_kabupaten.slice(0, 4)),
        );
    } catch (e) {
        console.log(
            `  kecamatan ${kab.kabupaten} failed: ${e.message.slice(0, 120)}`,
        );
        return false;
    }

    console.log(
        `${kab.kabupaten} (${kab.kode_kabupaten}): ${kecamatan.length} kecamatan`,
    );
    if (!kecamatan.length) return false;

    const all = [];
    for (const kec of kecamatan) {
        const schools =
            (
                await fetchJson(
                    page,
                    `/api/progress-pengiriman/kecamatan/school?kode_kecamatan=${kec.kode_kecamatan}`,
                    token,
                )
            )?.data || [];
        const keep = TYPES
            ? schools.filter((s) =>
                  TYPES.includes(
                      String(s.bentuk_pendidikan || '').toUpperCase(),
                  ),
              )
            : schools;
        all.push(
            ...keep.map((s) => ({ ...s, kode_kecamatan: kec.kode_kecamatan })),
        );
        console.log(
            `    ${kec.kecamatan}: ${schools.length} sekolah, ${keep.length} dipertahikan`,
        );
        await sleep(SLOW_BY);
    }

    console.log(`  expand: ${all.length} schools for ${kab.kabupaten}`);
    const details = [];
    let failed = 0;
    for (const s of all) {
        try {
            const rows =
                (
                    await fetchJson(
                        page,
                        `/api/detail-sekolah?npsn=${s.npsn}`,
                        token,
                    )
                )?.data || [];
            if (!rows.length) throw new Error(`empty detail for ${s.npsn}`);
            details.push(rows[0]);
        } catch (e) {
            failed++;
            console.log(
                `    detail fail ${s.npsn}: ${e.message.slice(0, 120)}`,
            );
        }
        await sleep(SLOW_BY);
    }

    writeJson(outFile, {
        captured_at: new Date().toISOString(),
        source: 'dapodik',
        region_code: kab.kode_kabupaten,
        region_name: kab.kabupaten,
        province_code: kab.kode_provinsi,
        province: kab.provinsi,
        semester:
            details[0] && 'semester' in details[0] ? details[0].semester : null,
        schools: details,
    });
    console.log(
        `    wrote ${outFile} (${details.length} schools, ${failed} failed)`,
    );

    return true;
}

(async () => {
    const { region, force } = args();
    console.log(
        `capture region ${region ? region + ' only' : 'Sulawesi Tengah + Sulawesi Barat'}`,
    );

    let snapshotsWritten = 0;

    const provinces = readJson(PROVINCES_SEED);
    if (!provinces || !provinces.length) {
        throw new Error(`provinsi seed not found: ${PROVINCES_SEED}`);
    }

    const browser = await chromium.launch({
        channel: 'msedge',
        headless: false,
    });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'id-ID',
    });
    const page = await context.newPage();
    page.setDefaultTimeout(60000);

    const token = await page.evaluate(async (base) => {
        const res = await fetch(base + '/env.js');
        const text = await res.text();
        const m = text.match(/([a-f0-9]{64,})/i);
        return m ? m[1] : '';
    }, BASE);
    if (!token) throw new Error('no VITE_API_TOKEN found in env.js');
    console.log('token ok');

    await page
        .goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 })
        .catch(() => {});
    await sleep(4000);
    await page
        .goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 })
        .catch(() => {});
    await sleep(3000);
    console.log('waf challenge ok');

    // Build / refresh the kabupaten cache for the provinces we will sweep.
    const cache = readJson(CACHE_FILE) || {};
    let cacheDirty = false;
    const provincesToSweep = provinces
        .filter((p) => PROVINCES.includes(p.kode_provinsi))
        .filter(
            (p) => !region || p.kode_provinsi.startsWith(region.slice(0, 2)),
        );

    for (const prov of provincesToSweep) {
        if (!cache[prov.kode_provinsi]) {
            cache[prov.kode_provinsi] = await discoverKabupaten(
                page,
                token,
                prov,
            );
            cacheDirty = true;
        }
        const kabs = cache[prov.kode_provinsi];
        if (!kabs.length) {
            console.log(
                `WARN ${prov.nama}: no kabupaten discovered (seed sulawesi-kabupaten-cache.json)`,
            );
            continue;
        }
        const target = region
            ? kabs.filter((k) => k.kode_kabupaten === region)
            : kabs;
        for (const kab of target) {
            if (await captureKabupaten(page, token, kab, force)) {
                snapshotsWritten++;
            }
        }
    }
    if (cacheDirty) writeJson(CACHE_FILE, cache);

    await browser.close();

    if (snapshotsWritten === 0) {
        throw new Error(
            'no kabupaten snapshots were written — check the endpoint errors and WAF session output above',
        );
    }

    console.log(`capture done (${snapshotsWritten} snapshot file(s) written)`);
})().catch((e) => {
    console.error('capture aborted:', e.message);
    process.exitCode = 1;
});
