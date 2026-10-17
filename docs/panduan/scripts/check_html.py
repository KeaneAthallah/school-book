"""Check the merged book HTML for unbalanced tags and other structural slips."""
import sys
from html.parser import HTMLParser
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"}


class Checker(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.stack: list[tuple[str, int]] = []
        self.errors: list[str] = []

    def handle_starttag(self, tag: str, attrs) -> None:
        if tag not in VOID:
            self.stack.append((tag, self.getpos()[0]))

    def handle_endtag(self, tag: str) -> None:
        if tag in VOID:
            return
        if not self.stack:
            self.errors.append(f"line {self.getpos()[0]}: closing </{tag}> with nothing open")
            return
        open_tag, line = self.stack.pop()
        if open_tag != tag:
            self.errors.append(f"line {self.getpos()[0]}: </{tag}> closes <{open_tag}> opened on line {line}")


path = Path("D:/school-book/docs/panduan/book-rendered.html")
checker = Checker()
checker.feed(path.read_text(encoding="utf-8"))

for open_tag, line in checker.stack:
    checker.errors.append(f"line {line}: <{open_tag}> never closed")

if checker.errors:
    print(f"{len(checker.errors)} structural problem(s):")
    for error in checker.errors:
        print("  x", error)
    sys.exit(1)

print("book HTML is structurally balanced")
