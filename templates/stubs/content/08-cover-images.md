---
title: "Cover Images"
---

Add a professional cover to your eBook!

## Supported Formats

Papyrus supports cover images in these formats:

- **PNG** (recommended)
- **JPG/JPEG**
- **GIF**
- **WEBP**
- **SVG**

## Adding a Cover

1. Place your cover image in `assets/images/`
2. Update `papyrus.toml`:

```toml
cover = "my-cover.png"  # Relative to assets/images/
```

3. Generate your eBook - the cover will be included automatically!

## Default Cover

When you run `papyrus init`, a default `cover.png` is automatically created with Papyrus branding. You can replace it with your own cover image.

## Best Practices

- Use high-resolution images (at least 1200x1600 pixels)
- Keep file sizes reasonable for EPUB compatibility
- Ensure your cover looks good at different sizes
- Test how the cover appears in different readers

## Note

PDF files are not supported as cover images. Convert PDF covers to PNG or JPG before using them.
