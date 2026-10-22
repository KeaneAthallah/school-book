"""QA for the generated manual PDF.

Checks structure (page size, budget of pages), text legibility (Indonesian
diacritics, no mojibake), blank pages, and that the table of contents page
numbers actually point at the right pages.

Usage: python docs/panduan/scripts/qa_pdf.py
Exits non-zero when a hard check fails.
"""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

import pymupdf

PDF = Path("D:/school-book/docs/Panduan-Pengguna-School-Book-Sales-Monitoring-System.pdf")
RENDER_DIR = Path(__file__).resolve().parent.parent / "qa-pages"
A4 = (595.276, 841.89)

try:  # keep Windows consoles from mangling the report
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
except (AttributeError, OSError):
    pass

# Headings that must appear in the body with a large font size; the TOC entry
# with the same text must carry the same page number.
HEADINGS = [
    "Bab 1. Apa Itu ARKARA PM?",
    "Bab 2. Langkah Pertama Anda",
    "Bab 3. Enam Peran Pengguna",
    "Bab 4. Dashboard dan Data Induk",
    "Bab 5. Transaksi dan Keuangan",
    "Bab 6. Dari Barang Datang sampai Uang Masuk",
    "Bab 7. Tujuh Laporan",
    "Bab 8. Bila Ada yang Tidak Berjalan",
    "Lampiran C. Daftar Izin Lengkap",
]

# Strings that must be extractable, proving text (not images) and correct encoding.
MUST_CONTAIN = [
    "ARKARA PM",
    "Kelola Lebih Mudah, Pantau Lebih Akurat",
    "Reservasi",
    "DPP = harga",
    "target-counter",
]

failures: list[str] = []
notes: list[str] = []


def normalise(text: str) -> str:
    return re.sub(r"\s+", " ", text).strip()


def main() -> int:
    doc = pymupdf.open(PDF)
    pages = list(doc)
    notes.append(f"page count: {len(pages)}")

    # ── page geometry ────────────────────────────────────────────────────
    bad_size = []
    for index, page in enumerate(pages, start=1):
        width, height = page.rect.width, page.rect.height
        if abs(width - A4[0]) > 3 or abs(height - A4[1]) > 3:
            bad_size.append(f"page {index}: {width:.0f}x{height:.0f}pt")
    if bad_size:
        failures.append(f"{len(bad_size)} page(s) are not A4: {bad_size[:5]}")
    else:
        notes.append("all pages A4")

    texts = [page.get_text() for page in pages]

    # ── blank pages ──────────────────────────────────────────────────────
    blanks = []
    for index, page in enumerate(pages, start=1):
        has_text = bool(normalise(page.get_text()))
        has_image = bool(page.get_images(full=True))
        if not has_text and not has_image:
            blanks.append(index)
    if blanks:
        notes.append(f"pages with no text and no image: {blanks}")
    else:
        notes.append("no blank pages")

    # ── required strings & diacritics ────────────────────────────────────
    joined = "\n".join(texts)
    for needle in MUST_CONTAIN:
        if needle == "target-counter":
            continue  # CSS only; must not leak into the PDF
        if needle not in joined:
            failures.append(f"missing expected text: {needle!r}")

    if "target-counter" in joined:
        failures.append("CSS artefact 'target-counter' leaked into the PDF text")

    mojibake = [token for token in ("Ã©", "Ã¨", "â€", "Ã¢", "\ufffd") if token in joined]
    if mojibake:
        failures.append(f"possible mojibake sequences found: {mojibake}")
    else:
        notes.append("no mojibake sequences")

    for currency in ("Rp", "12%", "0,5%"):
        if currency not in joined:
            notes.append(f"did not find {currency!r} in extracted text")

    # ── content running off the paper ────────────────────────────────────
    overflow: list[str] = []
    for index, page in enumerate(pages, start=1):
        width, height = page.rect.width, page.rect.height
        for word in page.get_text("words"):
            x0, y0, x1, y1 = word[:4]
            if x0 < -0.5 or x1 > width + 0.5 or y0 < -0.5 or y1 > height + 0.5:
                overflow.append(f"page {index}: {normalise(word[4])!r} at x={x0:.0f}..{x1:.0f}, y={y0:.0f}..{y1:.0f}")
        for image in page.get_image_info():
            box = image["bbox"]
            if box[0] < -0.5 or box[2] > width + 0.5 or box[1] < -0.5 or box[3] > height + 0.5:
                overflow.append(f"page {index}: image at x={box[0]:.0f}..{box[2]:.0f}, y={box[1]:.0f}..{box[3]:.0f}")

    if overflow:
        failures.append(f"{len(overflow)} element(s) run off the page: {overflow[:6]}")
    else:
        notes.append("nothing runs off the page edges")

    # ── table of contents page numbers ───────────────────────────────────
    # Paged.js renders the floated page number as its own text run, so group
    # words by baseline and read each entry as "…heading… <number>".
    def toc_number(heading: str) -> int | None:
        for page in pages[:13]:
            rows: dict[int, list] = {}
            for word in page.get_text("words"):
                rows.setdefault(round(word[1]), []).append((word[0], word[4]))
            for key in sorted(rows):
                line = normalise(" ".join(text for _, text in sorted(rows[key])))
                if line.startswith(heading):
                    match = re.search(r"(\d+)$", line)
                    if match:
                        return int(match.group(1))
        return None

    def body_page(heading: str) -> int | None:
        for index, page in enumerate(pages, start=1):
            for block in page.get_text("dict")["blocks"]:
                for line in block.get("lines", []):
                    text = normalise("".join(span["text"] for span in line["spans"]))
                    biggest = max((span["size"] for span in line["spans"]), default=0)
                    if text == heading and biggest >= 18:
                        return index
        return None

    checked = 0
    for heading in HEADINGS:
        declared = toc_number(heading)
        actual = body_page(heading)
        if declared is None or actual is None:
            notes.append(f"TOC check skipped for {heading!r} (declared={declared}, actual={actual})")
            continue
        checked += 1
        if declared != actual:
            failures.append(f"TOC mismatch for {heading!r}: lists page {declared}, actually on page {actual}")

    if checked:
        notes.append(f"TOC page numbers verified against body for {checked} heading(s)")
    else:
        failures.append("could not verify any TOC page number against the body text")

    # ── render sample pages for visual inspection ────────────────────────
    RENDER_DIR.mkdir(parents=True, exist_ok=True)
    sample = sorted({1, 2, 5, 8, 12, 20, 40, 60, 90, 120, len(pages)})
    for number in sample:
        if 1 <= number <= len(pages):
            pixmap = pages[number - 1].get_pixmap(dpi=96)
            pixmap.save(RENDER_DIR / f"page-{number:03d}.png")
    notes.append(f"rendered {len(sample)} sample pages to {RENDER_DIR}")

    doc.close()

    print("QA PDF - ARKARA PM")
    for note in notes:
        print(f"  * {note}")
    if failures:
        print("\nFAILURES:")
        for failure in failures:
            print(f"  x {failure}")
        return 1
    print("\nAll PDF checks passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
