#!/usr/bin/env python3
"""
Convert ClinicOS markdown brochures in docs/ to PDF (no LaTeX/Chromium).
Requires: pip install fpdf2

On first run, downloads Noto Sans Regular (OFL) into scripts/fonts/ if missing.
"""
from __future__ import annotations

import re
import sys
import urllib.request
from pathlib import Path

try:
    import fpdf
    from fpdf import FPDF
except ImportError:
    print("Install fpdf2: python3 -m pip install fpdf2", file=sys.stderr)
    sys.exit(1)

# Noto Sans — SIL OFL (https://github.com/googlefonts/noto-fonts)
_NOTO_SANS_URL = (
    "https://github.com/googlefonts/noto-fonts/raw/main/"
    "hinted/ttf/NotoSans/NotoSans-Regular.ttf"
)


def ensure_unicode_font() -> Path:
    fonts_dir = Path(__file__).resolve().parent / "fonts"
    fonts_dir.mkdir(parents=True, exist_ok=True)
    ttf = fonts_dir / "NotoSans-Regular.ttf"
    if not ttf.is_file():
        print(f"Downloading Noto Sans to {ttf} ...", file=sys.stderr)
        req = urllib.request.Request(
            _NOTO_SANS_URL,
            headers={"User-Agent": "ClinicOS-docs-pdf/1.0"},
        )
        with urllib.request.urlopen(req, timeout=120) as resp, ttf.open("wb") as f:  # nosec B310
            f.write(resp.read())
    return ttf


def strip_inline_md(s: str) -> str:
    s = re.sub(r"\*\*(.+?)\*\*", r"\1", s)
    s = re.sub(r"`([^`]+)`", r"\1", s)
    s = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", s)
    return s


def pdf_safe(s: str) -> str:
    """Strip emoji / pictographs Noto Sans Regular does not cover; normalize symbols."""
    s = re.sub(r"[\U0001F000-\U0001FFFF]", "", s)  # supplementary-plane pictographs
    s = re.sub(r"[\u2600-\u27BF]", "", s)  # misc symbols (checkmarks, warnings, etc.)
    s = s.replace("\ufe0f", "")  # VS16
    s = s.replace("\u2192", "->")
    s = s.replace("\u2705", "[OK]")
    s = s.replace("\u274c", "[--]")
    s = s.replace("\u26a0", "[!]")
    return s.strip()


class DocPDF(FPDF):
    def __init__(self, title: str):
        super().__init__(format="A4")
        self.doc_title = title
        self.set_margins(18, 18, 18)
        self.set_auto_page_break(auto=True, margin=18)

    def header(self) -> None:
        if self.page_no() == 1:
            return
        self.set_font("Noto", size=9)
        self.set_text_color(100, 100, 100)
        self.cell(0, 8, self.doc_title, new_x="LMARGIN", new_y="NEXT", align="R")
        self.set_text_color(0, 0, 0)
        self.ln(2)

    def footer(self) -> None:
        self.set_y(-14)
        self.set_font("Noto", size=8)
        self.set_text_color(120, 120, 120)
        self.cell(0, 10, f"Page {self.page_no()}", align="C")


def render_markdown_pdf(md_path: Path, pdf_path: Path, ttf: Path) -> None:
    raw = md_path.read_text(encoding="utf-8")
    title = md_path.stem.replace("_", " ")

    pdf = DocPDF(title)
    pdf.add_font("Noto", fname=str(ttf))
    pdf.set_font("Noto", size=11)
    pdf.add_page()
    col_w = pdf.epw

    pdf.set_font("Noto", size=20)
    pdf.multi_cell(col_w, 10, "ClinicOS")
    pdf.set_font("Noto", size=12)
    pdf.multi_cell(col_w, 8, title)
    pdf.ln(6)
    pdf.set_font("Noto", size=11)

    in_code = False
    for line in raw.splitlines():
        line = line.rstrip("\n")
        stripped = line.strip()

        if stripped.startswith("```"):
            in_code = not in_code
            pdf.ln(2)
            continue
        if in_code:
            pdf.set_font("Noto", size=9)
            pdf.multi_cell(col_w, 5, pdf_safe(line if line else " "))
            pdf.set_font("Noto", size=11)
            continue

        if stripped == "---":
            pdf.ln(4)
            continue

        if not stripped:
            pdf.ln(3)
            continue

        if stripped.startswith("# "):
            pdf.set_font("Noto", size=16)
            pdf.multi_cell(col_w, 8, pdf_safe(strip_inline_md(stripped[2:])))
            pdf.set_font("Noto", size=11)
            continue
        if stripped.startswith("## "):
            pdf.ln(2)
            pdf.set_font("Noto", size=13)
            pdf.multi_cell(col_w, 7, pdf_safe(strip_inline_md(stripped[3:])))
            pdf.set_font("Noto", size=11)
            continue
        if stripped.startswith("### "):
            pdf.ln(1)
            pdf.set_font("Noto", size=11)
            pdf.multi_cell(col_w, 6, pdf_safe(strip_inline_md(stripped[4:])))
            pdf.set_font("Noto", size=11)
            continue

        if stripped.startswith(">"):
            pdf.set_font("Noto", size=10)
            pdf.multi_cell(
                col_w,
                6,
                pdf_safe(strip_inline_md(stripped.lstrip("> ").strip())),
            )
            pdf.set_font("Noto", size=11)
            continue

        if stripped.startswith("|") and "|" in stripped[1:]:
            pdf.set_font("Noto", size=8)
            pdf.multi_cell(col_w, 5, pdf_safe(strip_inline_md(stripped)))
            pdf.set_font("Noto", size=11)
            continue

        if re.match(r"^[-*]\s+", stripped):
            body = pdf_safe(strip_inline_md(re.sub(r"^[-*]\s+", "", stripped)))
            pdf.multi_cell(col_w, 6, "    \u2022 " + body)
            continue
        if re.match(r"^\d+\.\s+", stripped):
            pdf.multi_cell(col_w, 6, "    " + pdf_safe(strip_inline_md(stripped)))
            continue

        pdf.multi_cell(col_w, 6, pdf_safe(strip_inline_md(stripped)))

    pdf.output(str(pdf_path))
    print(f"Wrote {pdf_path}", file=sys.stderr)


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    docs = root / "docs"
    ttf = ensure_unicode_font()
    files = [
        docs / "MARKETING_BROCHURE.md",
        docs / "INVESTOR_PITCH.md",
        docs / "CLIENT_PROPOSAL.md",
    ]
    for md in files:
        if not md.is_file():
            print(f"Missing {md}", file=sys.stderr)
            sys.exit(1)
        out = md.with_suffix(".pdf")
        render_markdown_pdf(md, out, ttf)


if __name__ == "__main__":
    main()
