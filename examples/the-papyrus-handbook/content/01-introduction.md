---
title: Introduction
---

# Introduction

Papyrus is a Composer package and CLI for authors who write books in Markdown
and need more than one delivery format from a single tree of chapters.

One project layout yields:

- **PDF** — print interiors and shareable digital books
- **EPUB** — stores, e-readers, and Kindle upload
- **HTML** — a single file with light / dark mode
- **Site** — a multi-page static website with popup search (this handbook)
- **KDP helpers** — Kindle EPUB, print PDF, covers, wrap estimates, metadata, package zip

This handbook is itself a Papyrus book. The Markdown source lives in
[`examples/the-papyrus-handbook/`](https://github.com/milon/papyrus/tree/master/examples/the-papyrus-handbook)
on GitHub. Prebuilt PDF, HTML, and site outputs are published from that
example so you can read without installing anything.

## Who it is for

- Authors shipping a technical or long-form book from Markdown
- Teams migrating off [ibis-next](https://github.com/Hi-Folks/ibis-next)
- Anyone who wants PDF and a hosted docs site from the same chapters

## Project shape

Every book is a directory with:

| Path | Role |
|------|------|
| `papyrus.php` | Title, trim, themes, Mermaid, samples, KDP |
| `content/` | Markdown chapters (natural filename order) |
| `assets/` | Your overrides (covers, banner, custom themes/fonts); bundled defaults used when absent |
| `export/` | Build outputs (usually gitignored) |

## Next steps

Install Papyrus (next chapter), run `papyrus doctor`, then `papyrus build` or
`papyrus build:site`. Later chapters document every build and KDP command and
the full `papyrus.php` option set.

Source and releases: [github.com/milon/papyrus](https://github.com/milon/papyrus).
