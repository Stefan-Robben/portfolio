#!/usr/bin/env python3
"""Build a Pages document (.pages) from the simple letter HTML in assets/brief.html.

Pages cannot reliably open converted files (.docx/.rtf from textutil), so this script
creates a new, empty Pages document over AppleScript, types the letter in, styles each
paragraph and saves it. It also exports a temporary PDF to check the letter fits on one page.

Supported HTML (all that the template uses):
  <p style="font-size: 20pt; color: #002152; margin: 0;">  -> size, colour, no space after
  <p> without margin: 0                                    -> followed by an empty line
  <b>…</b> wrapping the whole paragraph                    -> bold
  <br>                                                     -> line break inside the paragraph
  &nbsp; on its own                                        -> empty line

Usage: build_pages.py <brief.html> <output.pages>
"""
import html
import os
import re
import shutil
import subprocess
import sys
import tempfile
from html.parser import HTMLParser

BODY_FONT = "HelveticaNeue"
BOLD_FONT = "HelveticaNeue-Bold"
DEFAULT_SIZE = 10.5
DEFAULT_COLOR = "#1a1f27"
LINE_SEPARATOR = " "  # soft line break inside a Pages paragraph


class LetterParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.paragraphs = []
        self.current = None
        self.bold_chars = 0

    def handle_starttag(self, tag, attrs):
        if tag == "p":
            style = dict(attrs).get("style", "") or ""
            self.current = {"text": "", "style": style, "bold_text": ""}
        elif tag == "br" and self.current is not None:
            self.current["text"] += LINE_SEPARATOR
        elif tag == "b" and self.current is not None:
            self.bold_chars += 1

    def handle_endtag(self, tag):
        if tag == "b":
            self.bold_chars = max(0, self.bold_chars - 1)
        elif tag == "p" and self.current is not None:
            self.paragraphs.append(self.current)
            self.current = None

    def handle_data(self, data):
        if self.current is None:
            return
        text = re.sub(r"\s+", " ", data)
        self.current["text"] += text
        if self.bold_chars:
            self.current["bold_text"] += text


def css(style, prop):
    match = re.search(rf"{prop}\s*:\s*([^;]+)", style)
    return match.group(1).strip() if match else None


def to_pages_color(hex_color):
    hex_color = hex_color.lstrip("#")
    return [int(hex_color[i:i + 2], 16) * 257 for i in (0, 2, 4)]


def applescript_string(text):
    return '"' + text.replace("\\", "\\\\").replace('"', '\\"') + '"'


def build_paragraphs(path):
    parser = LetterParser()
    with open(path, encoding="utf-8") as handle:
        parser.feed(handle.read())

    result = []
    for p in parser.paragraphs:
        text = p["text"].replace(" ", " ")
        text = LINE_SEPARATOR.join(part.strip() for part in text.split(LINE_SEPARATOR)).strip()
        style = p["style"]
        size = css(style, "font-size")
        color = css(style, "color")
        bold = bool(text) and p["bold_text"].strip() == text
        result.append({
            "text": text,
            "size": float(size.replace("pt", "")) if size else DEFAULT_SIZE,
            "color": color or DEFAULT_COLOR,
            "bold": bold,
        })
        if css(style, "margin") != "0":
            result.append({"text": "", "size": DEFAULT_SIZE, "color": DEFAULT_COLOR, "bold": False})

    while result and not result[-1]["text"]:
        result.pop()
    return result


def build_script(paragraphs, pages_path, pdf_path):
    body = " & return & ".join(applescript_string(p["text"]) for p in paragraphs)
    styling = []
    for index, p in enumerate(paragraphs, start=1):
        if not p["text"]:
            continue
        r, g, b = to_pages_color(p["color"])
        font = BOLD_FONT if p["bold"] else BODY_FONT
        styling.append(
            f'      set font of paragraph {index} to "{font}"\n'
            f"      set size of paragraph {index} to {p['size']}\n"
            f"      set color of paragraph {index} to {{{r}, {g}, {b}}}"
        )
    return f"""with timeout of 120 seconds
  tell application "Pages"
    set d to make new document
    set body text of d to {body}
    tell body text of d
{chr(10).join(styling)}
    end tell
    save d in POSIX file {applescript_string(pages_path)}
    export d to POSIX file {applescript_string(pdf_path)} as PDF
    close d saving no
  end tell
end timeout
"""


def page_count(pdf_path):
    if shutil.which("pdfinfo"):
        out = subprocess.run(["pdfinfo", pdf_path], capture_output=True, text=True).stdout
        match = re.search(r"^Pages:\s+(\d+)", out, re.M)
        return match.group(1) if match else "?"
    out = subprocess.run(["mdls", "-raw", "-name", "kMDItemNumberOfPages", pdf_path],
                         capture_output=True, text=True).stdout
    return out.strip() or "?"


def main():
    if len(sys.argv) != 3:
        sys.exit("Gebruik: build_pages.py <brief.html> <uitvoer.pages>")
    html_path = os.path.abspath(sys.argv[1])
    out_path = os.path.abspath(sys.argv[2])
    if not os.path.isdir("/Applications/Pages.app"):
        sys.exit("Pages is niet geïnstalleerd; installeer Pages uit de App Store.")

    paragraphs = build_paragraphs(html_path)

    # Work in /tmp: Pages is sandboxed and hangs on some other locations (e.g. $TMPDIR, Desktop).
    tmp = tempfile.mkdtemp(prefix="motivatiebrief.", dir="/tmp")
    try:
        tmp_pages = os.path.join(tmp, "brief.pages")
        tmp_pdf = os.path.join(tmp, "brief.pdf")
        script = build_script(paragraphs, tmp_pages, tmp_pdf)
        run = subprocess.run(["osascript", "-"], input=script, capture_output=True, text=True)
        if run.returncode != 0:
            sys.exit("Pages gaf een fout: " + run.stderr.strip()
                     + "\nStaat er een dialoogvenster open in Pages? Sluit het en probeer opnieuw.")

        os.makedirs(os.path.dirname(out_path), exist_ok=True)
        if os.path.exists(out_path):
            shutil.rmtree(out_path) if os.path.isdir(out_path) else os.remove(out_path)
        shutil.move(tmp_pages, out_path)

        # Preview image next to the HTML, so the result can be checked without opening Pages.
        if shutil.which("pdftoppm"):
            preview = os.path.splitext(html_path)[0] + "-voorbeeld"
            subprocess.run(["pdftoppm", "-r", "70", "-png", "-f", "1", "-l", "1", tmp_pdf, preview],
                           capture_output=True)

        pages = page_count(tmp_pdf)
        print(f"Pages: {out_path} ({pages} pagina)")
        if pages != "1":
            print(f"Let op: de brief beslaat {pages} pagina's. Kort de tekst in tot hij op één A4 past.",
                  file=sys.stderr)
            sys.exit(2)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    main()
