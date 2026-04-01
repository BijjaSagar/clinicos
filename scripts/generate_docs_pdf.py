#!/usr/bin/env python3
"""
Convert ClinicOS markdown brochures in docs/ to full-colour PDFs.

Primary: Markdown -> styled HTML -> Google Chrome / Chromium headless --print-to-pdf
Fallback: fpdf2 (plain) if no browser is available.

Setup:
  python3 -m venv scripts/.venv_docs
  scripts/.venv_docs/bin/pip install fpdf2 markdown
"""
from __future__ import annotations

import html as html_module
import logging
import os
import re
import shutil
import subprocess
import sys
import tempfile
import urllib.request
from pathlib import Path

logging.basicConfig(level=logging.INFO, format="%(levelname)s %(message)s")
log = logging.getLogger("clinicos_docs_pdf")

try:
    import markdown
except ImportError:
    log.error("Install markdown: scripts/.venv_docs/bin/pip install markdown")
    sys.exit(1)

try:
    from fpdf import FPDF
except ImportError:
    FPDF = None  # type: ignore[misc, assignment]

# Noto Sans — SIL OFL (fallback PDF path)
_NOTO_SANS_URL = (
    "https://github.com/googlefonts/noto-fonts/raw/main/"
    "hinted/ttf/NotoSans/NotoSans-Regular.ttf"
)

# ClinicOS brand (matches backend/resources/views/layouts/app.blade.php tailwind config)
BRAND = {
    "blue": "#1447E6",
    "blue_dark": "#0f35b8",
    "blue_light": "#eff3ff",
    "teal": "#0891B2",
    "green": "#059669",
    "sidebar": "#0D1117",
    "paper": "#f8fafc",
    "ink": "#0f172a",
    "muted": "#64748b",
}


def ensure_unicode_font() -> Path:
    fonts_dir = Path(__file__).resolve().parent / "fonts"
    fonts_dir.mkdir(parents=True, exist_ok=True)
    ttf = fonts_dir / "NotoSans-Regular.ttf"
    if not ttf.is_file():
        log.info("Downloading Noto Sans to %s", ttf)
        req = urllib.request.Request(
            _NOTO_SANS_URL,
            headers={"User-Agent": "ClinicOS-docs-pdf/1.0"},
        )
        with urllib.request.urlopen(req, timeout=120) as resp, ttf.open("wb") as f:  # nosec B310
            f.write(resp.read())
    return ttf


def find_chrome_executable() -> str | None:
    candidates = [
        os.environ.get("CHROME_PATH", "").strip(),
        "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
        "/Applications/Chromium.app/Contents/MacOS/Chromium",
        "/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge",
        shutil.which("google-chrome"),
        shutil.which("google-chrome-stable"),
        shutil.which("chromium"),
        shutil.which("chromium-browser"),
        shutil.which("msedge"),
    ]
    for c in candidates:
        if c and os.path.isfile(c) and os.access(c, os.X_OK):
            log.info("Using browser for PDF: %s", c)
            return c
    log.warning("No Chrome/Chromium/Edge found; set CHROME_PATH or install Chrome.")
    return None


def md_to_html_fragment(md_text: str) -> str:
    return markdown.markdown(
        md_text,
        extensions=[
            "markdown.extensions.tables",
            "markdown.extensions.fenced_code",
            "markdown.extensions.nl2br",
            "markdown.extensions.sane_lists",
        ],
    )


def strip_first_h1(html: str) -> tuple[str, str | None]:
    """Return (html_without_first_h1, first_h1_inner_text_or_none)."""
    m = re.match(r"^\s*<h1[^>]*>\s*(.*?)\s*</h1>\s*", html, re.DOTALL | re.IGNORECASE)
    if not m:
        return html, None
    inner = re.sub(r"<[^>]+>", "", m.group(1))
    inner = re.sub(r"\s+", " ", inner).strip()
    return html[m.end() :], inner


def build_html_document(*, cover_title: str, cover_tagline: str, body_html: str) -> str:
    b = BRAND
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>{html_module.escape(cover_title)} — ClinicOS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet"/>
  <style>
    @page {{ size: A4; margin: 14mm 16mm; }}
    * {{ box-sizing: border-box; }}
    html {{ font-size: 11pt; }}
    body {{
      font-family: 'Inter', system-ui, sans-serif;
      color: {b['ink']};
      line-height: 1.55;
      margin: 0;
      background: #fff;
    }}
    .cover {{
      page-break-after: always;
      min-height: 246mm;
      margin: -14mm -16mm 0 -16mm;
      padding: 22mm 20mm 28mm 20mm;
      background: linear-gradient(145deg, {b['blue_dark']} 0%, {b['blue']} 38%, {b['teal']} 72%, {b['green']} 100%);
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      position: relative;
      overflow: hidden;
    }}
    .cover::before {{
      content: '';
      position: absolute;
      top: -40%;
      right: -20%;
      width: 70%;
      height: 120%;
      background: radial-gradient(circle, rgba(255,255,255,0.14) 0%, transparent 65%);
      pointer-events: none;
    }}
    .cover-badge {{
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      opacity: 0.92;
      margin-bottom: 10px;
    }}
    .cover h1 {{
      font-family: 'Sora', sans-serif;
      font-weight: 800;
      font-size: 2.65rem;
      margin: 0 0 8px 0;
      line-height: 1.1;
      letter-spacing: -0.02em;
      text-shadow: 0 2px 24px rgba(0,0,0,0.15);
    }}
    .cover .tagline {{
      font-size: 1.05rem;
      font-weight: 500;
      max-width: 36ch;
      opacity: 0.95;
      line-height: 1.45;
    }}
    .cover-strip {{
      margin-top: 28px;
      height: 5px;
      width: 72px;
      border-radius: 3px;
      background: linear-gradient(90deg, #fff, rgba(255,255,255,0.45));
    }}
    .content {{ padding-top: 2mm; }}
    .content h1 {{
      font-family: 'Sora', sans-serif;
      font-size: 1.55rem;
      color: {b['blue']};
      margin: 1.1em 0 0.45em 0;
      padding-bottom: 6px;
      border-bottom: 3px solid {b['teal']};
    }}
    .content h2 {{
      font-family: 'Sora', sans-serif;
      font-size: 1.22rem;
      color: {b['blue_dark']};
      margin: 1.15em 0 0.4em 0;
      padding-left: 10px;
      border-left: 4px solid {b['green']};
    }}
    .content h3 {{
      font-size: 1.02rem;
      color: {b['teal']};
      margin: 0.95em 0 0.35em 0;
      font-weight: 700;
    }}
    .content p {{ margin: 0.5em 0; }}
    .content hr {{
      border: none;
      height: 4px;
      margin: 1.4em 0;
      border-radius: 2px;
      background: linear-gradient(90deg, {b['blue']}, {b['teal']}, {b['green']});
      opacity: 0.85;
    }}
    .content blockquote {{
      margin: 1em 0;
      padding: 12px 16px;
      background: linear-gradient(90deg, {b['blue_light']}, #ecfdf5);
      border-left: 4px solid {b['green']};
      border-radius: 0 10px 10px 0;
      color: {b['ink']};
    }}
    .content blockquote p {{ margin: 0.35em 0; }}
    .content ul, .content ol {{ margin: 0.45em 0 0.6em 1.1em; padding-left: 0.2em; }}
    .content li {{ margin: 0.25em 0; }}
    .content li::marker {{ color: {b['teal']}; font-weight: 700; }}
    .content strong {{ color: {b['blue_dark']}; font-weight: 600; }}
    .content code {{
      font-family: ui-monospace, monospace;
      font-size: 0.88em;
      background: {b['paper']};
      padding: 2px 6px;
      border-radius: 4px;
      border: 1px solid #e2e8f0;
    }}
    .content pre {{
      background: {b['sidebar']};
      color: #e2e8f0;
      padding: 14px 16px;
      border-radius: 10px;
      border-left: 5px solid {b['blue']};
      overflow-x: auto;
      font-size: 0.82rem;
      line-height: 1.45;
    }}
    .content pre code {{ background: none; border: none; color: inherit; padding: 0; }}
    .content table {{
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin: 1em 0;
      font-size: 0.88rem;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(20, 71, 230, 0.08);
    }}
    .content thead th {{
      background: linear-gradient(135deg, {b['blue']} 0%, {b['teal']} 100%);
      color: #fff;
      font-weight: 600;
      text-align: left;
      padding: 10px 12px;
      font-family: 'Sora', sans-serif;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }}
    .content tbody td {{
      padding: 9px 12px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: top;
    }}
    .content tbody tr:nth-child(even) td {{ background: {b['paper']}; }}
    .content tbody tr:last-child td {{ border-bottom: none; }}
    .footer-note {{
      margin-top: 2em;
      padding-top: 12px;
      border-top: 2px solid #e2e8f0;
      font-size: 0.78rem;
      color: {b['muted']};
    }}
  </style>
</head>
<body>
  <div class="cover">
    <div class="cover-badge">RH Technology · India</div>
    <h1>ClinicOS</h1>
    <div class="tagline">{html_module.escape(cover_tagline)}</div>
    <div class="cover-strip"></div>
  </div>
  <div class="content">
    {body_html}
    <p class="footer-note">ClinicOS — Smart EMR &amp; practice platform for Indian specialty clinics. https://clinic0s.com/</p>
  </div>
</body>
</html>
"""


def html_to_pdf_chrome(html_path: Path, pdf_path: Path, chrome: str) -> bool:
    html_uri = html_path.resolve().as_uri()
    pdf_path.parent.mkdir(parents=True, exist_ok=True)
    if pdf_path.is_file():
        pdf_path.unlink()

    cmd = [
        chrome,
        "--headless=new",
        "--disable-gpu",
        "--no-first-run",
        "--no-default-browser-check",
        "--disable-dev-shm-usage",
        "--no-pdf-header-footer",
        "--virtual-time-budget=25000",
        f"--print-to-pdf={pdf_path.resolve()}",
        html_uri,
    ]
    log.info("Printing PDF via headless browser…")
    r = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
    if r.returncode != 0 or not pdf_path.is_file() or pdf_path.stat().st_size < 500:
        log.warning("Chrome print failed (code %s). stderr: %s", r.returncode, (r.stderr or "")[:500])
        cmd2 = [
            chrome,
            "--headless",
            "--disable-gpu",
            "--no-pdf-header-footer",
            f"--print-to-pdf={pdf_path.resolve()}",
            html_uri,
        ]
        r2 = subprocess.run(cmd2, capture_output=True, text=True, timeout=120)
        if r2.returncode != 0 or not pdf_path.is_file() or pdf_path.stat().st_size < 500:
            log.warning("Chrome retry failed: %s", (r2.stderr or "")[:500])
            return False
    log.info("Wrote %s (%s bytes)", pdf_path, pdf_path.stat().st_size)
    return True


def strip_inline_md(s: str) -> str:
    s = re.sub(r"\*\*(.+?)\*\*", r"\1", s)
    s = re.sub(r"`([^`]+)`", r"\1", s)
    s = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", s)
    return s


def pdf_safe(s: str) -> str:
    s = re.sub(r"[\U0001F000-\U0001FFFF]", "", s)
    s = re.sub(r"[\u2600-\u27BF]", "", s)
    s = s.replace("\ufe0f", "")
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


def render_markdown_pdf_fallback(md_path: Path, pdf_path: Path, ttf: Path) -> None:
    if FPDF is None:
        raise RuntimeError("fpdf2 not installed")
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
    log.info("Wrote (fallback) %s", pdf_path)


def render_one(md_path: Path, pdf_path: Path, ttf: Path, chrome: str | None) -> None:
    raw = md_path.read_text(encoding="utf-8")
    fragment = md_to_html_fragment(raw)
    fragment, h1_text = strip_first_h1(fragment)
    stem_title = md_path.stem.replace("_", " ")
    cover_tagline = h1_text or stem_title

    html_full = build_html_document(
        cover_title=stem_title,
        cover_tagline=cover_tagline,
        body_html=fragment,
    )

    with tempfile.NamedTemporaryFile(
        mode="w",
        encoding="utf-8",
        suffix=".html",
        delete=False,
        dir=md_path.parent,
    ) as tmp:
        tmp.write(html_full)
        tmp_path = Path(tmp.name)

    try:
        if chrome and html_to_pdf_chrome(tmp_path, pdf_path, chrome):
            return
        log.warning("Falling back to fpdf2 for %s", md_path.name)
        if FPDF is None:
            raise RuntimeError("Chrome unavailable and fpdf2 not installed.")
        render_markdown_pdf_fallback(md_path, pdf_path, ttf)
    finally:
        try:
            tmp_path.unlink(missing_ok=True)
        except OSError:
            pass


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    docs = root / "docs"
    ttf = ensure_unicode_font()
    chrome = find_chrome_executable()
    files = [
        docs / "MARKETING_BROCHURE.md",
        docs / "INVESTOR_PITCH.md",
        docs / "CLIENT_PROPOSAL.md",
    ]
    for md in files:
        if not md.is_file():
            log.error("Missing %s", md)
            sys.exit(1)
        out = md.with_suffix(".pdf")
        log.info("Rendering %s -> %s", md.name, out.name)
        render_one(md, out, ttf, chrome)


if __name__ == "__main__":
    main()
