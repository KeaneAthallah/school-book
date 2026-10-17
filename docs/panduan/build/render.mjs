/**
 * render.mjs — build the ARKARA PM user manual PDF.
 *
 *  1. Merge docs/panduan/book/*.html (in filename order) into one document.
 *  2. Number every figure in document order and generate the list of figures.
 *  3. Verify every referenced screenshot exists on disk.
 *  4. Serve docs/panduan over HTTP and print with headless Edge + Paged.js,
 *     so the table of contents and figure list get real page numbers.
 *
 * Usage: node docs/panduan/build/render.mjs
 */
import { chromium } from 'file:///D:/school-book/node_modules/playwright-core/index.mjs';
import { createServer } from 'node:http';
import { existsSync, mkdirSync, readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { extname, join, normalize, resolve } from 'node:path';

const ROOT = resolve('D:/school-book/docs/panduan');
const BOOK = join(ROOT, 'book');
const RENDERED = join(ROOT, 'book-rendered.html');
const PDF = resolve('D:/school-book/docs/Panduan-Pengguna-School-Book-Sales-Monitoring-System.pdf');
const POLYFILL = join(ROOT, 'node_modules/pagedjs/dist/paged.polyfill.js');

const problems = [];

// ── 1. merge chapters ────────────────────────────────────────────────────
const chapters = readdirSync(BOOK)
  .filter((name) => name.endsWith('.html'))
  .sort();

let body = chapters
  .map((name) => {
    const html = readFileSync(join(BOOK, name), 'utf8');
    return `<!-- ==== ${name} ==== -->\n${html}`;
  })
  .join('\n\n');

// Screens live one level up from book/, so rewrite for the merged location.
body = body.replaceAll('src="../screens/', 'src="screens/');

// ── 2. figure numbering + list of figures ────────────────────────────────
const stripTags = (html) => html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

let figureNumber = 0;
const listItems = [];

body = body.replace(/<figure class="fig[^"]*"([^>]*)>([\s\S]*?)<\/figure>/g, (match, attrs, inner) => {
  figureNumber += 1;

  const idMatch = attrs.match(/id="([^"]+)"/);
  const id = idMatch ? idMatch[1] : '';
  if (!id) problems.push(`Figure #${figureNumber} has no id (cannot be referenced).`);

  const captionMatch = inner.match(/<figcaption>([\s\S]*?)<\/figcaption>/);
  if (!captionMatch) {
    problems.push(`Figure #${figureNumber} (${id}) has no caption.`);
  }

  const captionText = captionMatch ? stripTags(captionMatch[1]) : '';

  const withNumber = captionMatch
    ? inner.replace(
        '<figcaption>',
        `<figcaption><span class="fig-num">Gambar ${figureNumber}.</span> `,
      )
    : inner;

  if (id) {
    listItems.push(
      `<li><a href="#${id}"><span class="nomor">Gambar ${figureNumber}</span> — ${captionText}</a></li>`,
    );
  }

  return `<figure class="fig"${attrs}>${withNumber}</figure>`;
});

// ── 3. verify every screenshot referenced actually exists ────────────────
const images = [...body.matchAll(/<img[^>]+src="([^"]+)"/g)].map((m) => m[1]);
const missing = new Set();
for (const src of images) {
  if (!existsSync(join(ROOT, src))) missing.add(src);
}
if (missing.size) {
  problems.push(`Missing image files: ${[...missing].join(', ')}`);
}

const daftarGambar = [
  '<nav class="dafgam"><ul>',
  ...(listItems.length ? listItems : ['<li>Tidak ada gambar.</li>']),
  '</ul></nav>',
].join('\n');

body = body.replace('<!--DAFTAR-GAMBAR-->', daftarGambar);

const document_html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Panduan Pengguna ARKARA PM</title>
<link rel="stylesheet" href="book/css/book.css">
</head>
<body>
${body}
</body>
</html>
`;

writeFileSync(RENDERED, document_html, 'utf8');
console.log(`Merged ${chapters.length} chapters → ${RENDERED}`);
console.log(`Figure count: ${figureNumber}; images referenced: ${images.length}`);

// ── 4. serve docs/panduan and print ──────────────────────────────────────
const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
};

const server = createServer((request, response) => {
  const urlPath = decodeURIComponent((request.url || '/').split('?')[0]);
  const target = normalize(join(ROOT, urlPath));

  if (!target.startsWith(ROOT) || !existsSync(target) || statSync(target).isDirectory()) {
    response.writeHead(404).end('not found');
    return;
  }

  response.writeHead(200, { 'Content-Type': MIME[extname(target)] || 'application/octet-stream' });
  response.end(readFileSync(target));
});

await new Promise((done) => server.listen(0, '127.0.0.1', done));
const port = server.address().port;
const base = `http://127.0.0.1:${port}`;
console.log(`Serving ${ROOT} at ${base}`);

mkdirSync(resolve('D:/school-book/docs'), { recursive: true });

const browser = await chromium.launch({ channel: 'msedge', headless: true });
const page = await browser.newPage();

page.on('console', (message) => {
  if (message.type() === 'error') console.log('[browser error]', message.text());
});
page.on('pageerror', (error) => problems.push(`Browser error: ${error.message}`));

await page.addInitScript(() => {
  window.PagedConfig = { auto: false };
});

await page.goto(`${base}/book-rendered.html`, { waitUntil: 'load', timeout: 60000 });

// Fonts and images must be settled before pagination, otherwise page breaks drift.
await page.evaluate(async () => {
  await document.fonts.ready;
  await Promise.all(
    [...document.images]
      .filter((image) => !image.complete)
      .map((image) => new Promise((done) => { image.onload = image.onerror = done; })),
  );
});

await page.addScriptTag({ path: POLYFILL });
await page.evaluate(async () => {
  await window.PagedPolyfill.preview();
});

const summary = await page.evaluate(() => ({
  pages: document.querySelectorAll('.pagedjs_page').length,
  toc: document.querySelectorAll('.toc a').length,
  figures: document.querySelectorAll('.pagedjs_page figure.fig').length,
}));

await page.pdf({
  path: PDF,
  preferCSSPageSize: true,
  printBackground: true,
  displayHeaderFooter: false,
});

await browser.close();
server.close();

console.log(`Paged.js rendered ${summary.pages} pages, ${summary.figures} figures on pages.`);
console.log(`PDF → ${PDF}`);

if (problems.length) {
  console.log('\nBuild warnings:');
  problems.forEach((problem) => console.log(`  - ${problem}`));
} else {
  console.log('\nNo build warnings.');
}

// Machine-readable build report for QA.
writeFileSync(
  join(ROOT, 'build-report.json'),
  JSON.stringify({ chapters, figureNumber, images: images.length, missing: [...missing], pages: summary.pages, problems, pdf: PDF }, null, 2),
);

process.exit(problems.some((p) => p.startsWith('Missing image')) ? 1 : 0);
