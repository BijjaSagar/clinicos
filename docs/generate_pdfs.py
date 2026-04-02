#!/usr/bin/env python3
"""Generate professional PDFs for ClinicOS marketing, investor, and client documents."""

import markdown2
from weasyprint import HTML, CSS
import os

DOCS_DIR = os.path.dirname(os.path.abspath(__file__))

CSS_STYLE = """
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

@page {
    size: A4;
    margin: 18mm 20mm 18mm 20mm;
    @bottom-right {
        content: "Page " counter(page) " of " counter(pages);
        font-size: 9pt;
        color: #9ca3af;
        font-family: 'Inter', Arial, sans-serif;
    }
    @bottom-left {
        content: "ClinicOS — Confidential";
        font-size: 9pt;
        color: #9ca3af;
        font-family: 'Inter', Arial, sans-serif;
    }
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Inter', Arial, sans-serif;
    font-size: 10pt;
    line-height: 1.65;
    color: #1a1f2e;
    background: white;
}

/* ── HEADINGS ─────────────────────────────────────────── */
h1 {
    font-size: 26pt;
    font-weight: 900;
    color: #0d1117;
    margin-bottom: 6pt;
    line-height: 1.2;
    border-bottom: 3pt solid #1447e6;
    padding-bottom: 10pt;
    margin-bottom: 14pt;
}
h2 {
    font-size: 16pt;
    font-weight: 800;
    color: #1447e6;
    margin-top: 22pt;
    margin-bottom: 8pt;
    padding-bottom: 4pt;
    border-bottom: 1pt solid #e5e7eb;
    page-break-after: avoid;
}
h3 {
    font-size: 12pt;
    font-weight: 700;
    color: #0d1117;
    margin-top: 14pt;
    margin-bottom: 6pt;
    page-break-after: avoid;
}
h4 {
    font-size: 10.5pt;
    font-weight: 700;
    color: #374151;
    margin-top: 10pt;
    margin-bottom: 4pt;
    page-break-after: avoid;
}

/* ── BLOCKQUOTE (callout) ─────────────────────────────── */
blockquote {
    background: #eff3ff;
    border-left: 4pt solid #1447e6;
    border-radius: 4pt;
    padding: 10pt 14pt;
    margin: 12pt 0;
    color: #1447e6;
    font-style: normal;
    font-weight: 600;
    font-size: 11pt;
}

/* ── PARAGRAPHS & LISTS ───────────────────────────────── */
p { margin-bottom: 8pt; }

ul, ol {
    padding-left: 18pt;
    margin-bottom: 8pt;
}
li { margin-bottom: 3pt; }
li > ul, li > ol { margin-top: 3pt; margin-bottom: 3pt; }

/* ── TABLES ───────────────────────────────────────────── */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 10pt 0 14pt 0;
    font-size: 9.5pt;
    page-break-inside: avoid;
}
thead tr {
    background: #1447e6;
    color: white;
}
thead th {
    padding: 7pt 10pt;
    text-align: left;
    font-weight: 700;
    font-size: 9pt;
    letter-spacing: 0.02em;
}
tbody tr:nth-child(even) { background: #f8faff; }
tbody tr:nth-child(odd)  { background: white; }
tbody td {
    padding: 6pt 10pt;
    border-bottom: 1pt solid #e5e7eb;
    vertical-align: top;
}
tbody tr:last-child td { border-bottom: none; }

/* ── CODE / INLINE CODE ───────────────────────────────── */
code {
    background: #f3f4f6;
    border-radius: 3pt;
    padding: 1pt 4pt;
    font-size: 8.5pt;
    font-family: 'Courier New', monospace;
    color: #1447e6;
}

/* ── HORIZONTAL RULE ──────────────────────────────────── */
hr {
    border: none;
    border-top: 1pt solid #e5e7eb;
    margin: 18pt 0;
}

/* ── COVER STRIPE ─────────────────────────────────────── */
.cover-stripe {
    background: linear-gradient(135deg, #1447e6, #0891b2);
    color: white;
    padding: 28pt 24pt;
    border-radius: 8pt;
    margin-bottom: 24pt;
}
.cover-stripe h1 {
    border: none;
    color: white;
    font-size: 28pt;
    margin-bottom: 6pt;
    padding-bottom: 0;
}
.cover-stripe .tagline {
    font-size: 12pt;
    opacity: 0.88;
    font-weight: 500;
}
.cover-stripe .meta {
    margin-top: 14pt;
    font-size: 9.5pt;
    opacity: 0.75;
}

/* ── STRONG ───────────────────────────────────────────── */
strong { font-weight: 700; color: #0d1117; }
em     { color: #6b7280; }

/* ── KEEP SECTION TOGETHER ────────────────────────────── */
h2 + *, h3 + *, h4 + * { page-break-before: avoid; }
"""

def md_to_pdf(md_path, pdf_path, title, subtitle, contact_name=None, contact_phone=None):
    print(f"Generating: {pdf_path}")
    with open(md_path, 'r') as f:
        raw = f.read()

    # Replace placeholder contact info if provided
    if contact_name:
        raw = raw.replace('[Founder Name]', contact_name)
        raw = raw.replace('[Client Clinic Name]', contact_name)
    if contact_phone:
        raw = raw.replace('+91 XXXXX XXXXX', contact_phone)
        raw = raw.replace('XXXXX XXXXX', contact_phone.replace('+91 ', '').replace('+91', ''))

    # Convert markdown → HTML
    body_html = markdown2.markdown(
        raw,
        extras=['tables', 'fenced-code-blocks', 'header-ids', 'strike', 'task_list']
    )

    cover = f"""
    <div class="cover-stripe">
      <h1>{title}</h1>
      <div class="tagline">{subtitle}</div>
      <div class="meta">ClinicOS &nbsp;·&nbsp; clinicos.in &nbsp;·&nbsp; hello@clinicos.in</div>
    </div>
    """

    full_html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{title}</title>
</head>
<body>
  {cover}
  {body_html}
</body>
</html>"""

    HTML(string=full_html, base_url=DOCS_DIR).write_pdf(
        pdf_path,
        stylesheets=[CSS(string=CSS_STYLE)]
    )
    size_kb = os.path.getsize(pdf_path) // 1024
    print(f"  ✓  {pdf_path}  ({size_kb} KB)")


if __name__ == '__main__':
    contact_name  = 'Sagar Bijja'
    contact_phone = '+91 8983839143'

    docs = [
        (
            os.path.join(DOCS_DIR, 'MARKETING_BROCHURE.md'),
            os.path.join(DOCS_DIR, 'ClinicOS_Marketing_Brochure.pdf'),
            'ClinicOS — Marketing Brochure',
            'The Smart EMR Built for Indian Specialty Clinics',
        ),
        (
            os.path.join(DOCS_DIR, 'INVESTOR_PITCH.md'),
            os.path.join(DOCS_DIR, 'ClinicOS_Investor_Pitch.pdf'),
            'ClinicOS — Investor Pitch',
            'The Operating System for India\'s 1.3 Million Specialty Clinics',
        ),
        (
            os.path.join(DOCS_DIR, 'CLIENT_PROPOSAL.md'),
            os.path.join(DOCS_DIR, 'ClinicOS_Client_Proposal.pdf'),
            'ClinicOS — Client Proposal',
            'Prepared by Sagar Bijja  ·  +91 8983839143',
        ),
    ]

    for md, pdf, title, subtitle in docs:
        md_to_pdf(md, pdf, title, subtitle,
                  contact_name=contact_name,
                  contact_phone=contact_phone)

    print('\nAll PDFs generated successfully in docs/')
