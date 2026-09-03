---
title: Downloads
---

# Downloads

Preview this handbook without building locally. Files live in the Papyrus
repository under [`docs/`](https://github.com/milon/papyrus/tree/master/docs)
and update when the handbook is rebuilt.

## Full PDF

| Theme | Download |
|-------|----------|
| Light | [the-papyrus-handbook-light.pdf](https://github.com/milon/papyrus/raw/master/docs/the-papyrus-handbook-light.pdf) |
| Dark | [the-papyrus-handbook-dark.pdf](https://github.com/milon/papyrus/raw/master/docs/the-papyrus-handbook-dark.pdf) |

On GitHub: [light](https://github.com/milon/papyrus/blob/master/docs/the-papyrus-handbook-light.pdf) ·
[dark](https://github.com/milon/papyrus/blob/master/docs/the-papyrus-handbook-dark.pdf)

## Sample PDF

A shorter preview built with `build:sample`: cover through Welcome (before the
table of contents), then the **build:html** and **kdp:cover** chapters.

| Theme | Download |
|-------|----------|
| Light | [sample-the-papyrus-handbook-light.pdf](https://github.com/milon/papyrus/raw/master/docs/sample-the-papyrus-handbook-light.pdf) |
| Dark | [sample-the-papyrus-handbook-dark.pdf](https://github.com/milon/papyrus/raw/master/docs/sample-the-papyrus-handbook-dark.pdf) |

Configured in this book’s `papyrus.php` as:

```php
'sample' => [
    'ranges' => [
        ['from' => 1, 'to' => 5], // cover … Welcome (before TOC)
    ],
    'chapters' => [
        '09-build-html.md',
        '15-kdp-cover.md',
    ],
],
```

## Other previews

| Format | Link |
|--------|------|
| This site | [papyrus.milon.im](https://papyrus.milon.im/) |
| Single-file HTML | [the-papyrus-handbook.html](https://github.com/milon/papyrus/raw/master/docs/the-papyrus-handbook.html) |

Rebuild everything from the Papyrus repo with:

```bash
composer build:handbook
```
