---
title: Introduction
---

# Introduction

Papyrus is a Composer package and CLI for authors who write books in Markdown
and need print-ready PDF, EPUB, single-file HTML, and Amazon KDP outputs from
one project layout.

This handbook is itself a Papyrus book. The source lives in
`examples/the-papyrus-handbook/`; rendered PDF and HTML are checked into
`docs/` so you can read without building.

## What you get

| Format | Typical use |
|--------|-------------|
| PDF | Print interiors, digital reading, sample PDFs |
| EPUB | Stores, e-readers, KDP Kindle upload |
| HTML | Online reading, sharing a single file |
| KDP | Kindle EPUB, print PDF, cover export, metadata JSON |

## Project shape

Every book is a directory with:

- `papyrus.php` — title, trim, themes, Mermaid, sample ranges, KDP
- `content/` — Markdown chapters (natural filename order)
- `assets/` — PDF themes, HTML theme, EPUB CSS, covers, fonts
- `export/` — build outputs (usually gitignored)

## Next steps

Install Papyrus, scaffold or copy this sample, run `papyrus doctor`, then
`papyrus build`. The chapters that follow cover each step in detail.
