---
title: "Generating eBooks"
---

# Generating eBooks

Papyrus can generate your book in three formats: PDF, EPUB, and HTML.

## PDF Generation

Generate PDF eBooks with:

```bash
papyrus pdf
```

### PDF Backends

Papyrus supports multiple PDF backends (tries them in order):

1. **wkhtmltopdf** - Fast and reliable (recommended)
2. **weasyprint** - Python-based, good CSS support
3. **Chrome/Chromium** - Headless browser rendering

Install at least one:

```bash
# macOS
brew install wkhtmltopdf

# Ubuntu/Debian
sudo apt-get install wkhtmltopdf

# Or use weasyprint
pip install weasyprint
```

### PDF Options

```bash
# Use dark theme
papyrus pdf dark

# Custom content directory
papyrus pdf --content ./my-content

# Custom book directory
papyrus pdf --book-dir ./my-book
```

## EPUB Generation

Generate EPUB files for e-readers:

```bash
papyrus epub
```

EPUB files are compatible with:
- Kindle
- Apple Books
- Google Play Books
- Most e-reader apps

## HTML Generation

Generate standalone HTML files:

```bash
papyrus html
```

Perfect for web publishing or sharing online.

## Output Location

All generated files are saved in the `export/` directory with descriptive filenames based on your book title.
