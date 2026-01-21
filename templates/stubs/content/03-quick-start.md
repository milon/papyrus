---
title: "Quick Start"
---

Let's create your first eBook in just a few steps!

## Initialize a New Book

Start by creating a new book project:

```bash
papyrus init
```

This creates a complete project structure with:
- Configuration file (`papyrus.toml`)
- Content directory with sample chapters
- Assets directory with themes and styles
- Default cover image

## Generate Your First eBook

Once you've written some content, generate your eBook:

```bash
# Generate PDF
papyrus pdf

# Generate EPUB
papyrus epub

# Generate HTML
papyrus html
```

All generated files will be saved in the `export/` directory.

## Project Structure

Your book project will have this structure:

```
.
├── papyrus.toml      # Configuration file
├── content/          # Your markdown files
├── assets/           # Themes, styles, images
└── export/           # Generated eBooks
```

That's it! You're ready to start writing.
