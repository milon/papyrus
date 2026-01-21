---
title: "Writing Content"
---

# Writing Content

Papyrus uses Markdown for content, making it easy to write and format your book.

## Frontmatter

Each chapter can have frontmatter at the top:

```markdown
---
title: "Chapter Title"
author: "Author Name"
date: "2024-01-01"
---

# Chapter Content

Your markdown content here...
```

The frontmatter is optional but useful for metadata.

## File Organization

- Files are processed alphabetically by default
- Use `md_file_list` in config to specify a custom order
- You can organize files in subdirectories within `content/`

## Markdown Features

Papyrus supports standard Markdown features:

- Headers (`#`, `##`, `###`)
- **Bold** and *italic* text
- Lists (ordered and unordered)
- Links and images
- Code blocks with syntax highlighting
- Tables
- Blockquotes

## Code Blocks

Code blocks are automatically syntax highlighted:

```rust
fn main() {
    println!("Hello, world!");
}
```

Just specify the language after the opening triple backticks.
