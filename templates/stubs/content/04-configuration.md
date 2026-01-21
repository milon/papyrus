---
title: "Configuration"
---

# Configuration

The `papyrus.toml` file controls how your book is generated. Let's explore the configuration options.

## Basic Configuration

```toml
title = "My Book"
author = "Author Name"
language = "en"
version = "1.0.0"
cover = "cover.png"  # Optional: path relative to assets/images/
```

## File Selection

By default, all Markdown files in the `content/` directory are included. You can specify which files to include:

```toml
md_file_list = [
    "01-introduction.md",
    "02-chapter-one.md",
    "03-chapter-two.md",
]
```

Files are processed in the order specified in this list.

## Sample Configuration

If you want to generate a sample PDF with specific pages:

```toml
[sample]
start_page = 1
end_page = 10
```

## Custom Fonts

Add custom fonts to your book:

```toml
[[fonts]]
name = "CustomFont"
path = "fonts/custom-font.ttf"
```

Custom fonts can then be used in your theme files.
