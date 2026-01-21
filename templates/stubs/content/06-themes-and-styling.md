---
title: "Themes and Styling"
---

# Themes and Styling

Customize the appearance of your eBooks with themes and styles.

## PDF Themes

Papyrus includes two built-in PDF themes:

- **`theme-light.html`** - Light theme for PDF (default)
- **`theme-dark.html`** - Dark theme for PDF

Generate PDFs with different themes:

```bash
papyrus pdf light   # Light theme
papyrus pdf dark    # Dark theme
```

## HTML Theme

The `theme-html.html` template is used for HTML output. Edit this file to customize the HTML structure.

## CSS Styling

The `style.css` file controls styling for EPUB and HTML outputs. You can customize:

- Fonts and typography
- Colors and backgrounds
- Spacing and layout
- Code block styling

## Customization

All theme files are in the `assets/` directory. Edit them directly to match your preferences. The templates use Tera templating syntax, similar to Jinja2.

## Best Practices

- Keep themes consistent across formats
- Test your themes with actual content
- Consider readability for your target audience
- Use web-safe fonts or embed custom fonts
