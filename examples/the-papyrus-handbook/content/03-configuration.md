---
title: Configuration
---

# Configuration

Book settings live in **one** of:

- `papyrus.php` — PHP array (default; supports callables like `configure_commonmark`)
- `papyrus.yml` / `papyrus.yaml`
- `papyrus.json`

Papyrus auto-discovers which file is present and errors if more than one exists.
Examples below use PHP; the same keys work in YAML/JSON.

## Identity and paths

```php
return [
    'title' => 'My Book',
    'subtitle' => 'A short subtitle',
    'author' => 'Author Name',
    'language' => 'en',
    'themes' => ['light', 'dark'],

    'content_dir' => 'content',
    'assets_dir' => 'assets',
    'export_dir' => 'export',
];
```

| Key           | Default             | Notes                                 |
|---------------|---------------------|---------------------------------------|
| `title`       | `Untitled`          | Drives the export filename slug       |
| `subtitle`    | `''`                |                                       |
| `author`      | `''`                |                                       |
| `language`    | `en`                | EPUB / KDP metadata fallback          |
| `themes`      | `['light', 'dark']` | Each needs `assets/theme-{name}.html` |
| `content_dir` | `content`           | Relative to the book root             |
| `assets_dir`  | `assets`            |                                       |
| `export_dir`  | `export`            | Overridden by `-e` / `--export`       |

The slug is the title lowercased with non-alphanumeric runs turned into `-`.
This handbook is *The Papyrus Handbook*, so outputs look like
`the-papyrus-handbook-light.pdf`.
## Page size and margins

```php
'document' => [
    'size' => 'crown-quarto',
    'margin_left' => 27,
    'margin_right' => 27,
    'margin_top' => 14,
    'margin_bottom' => 14,
],
```

List presets with `papyrus sizes`. Custom trim:


```php
'document' => [
    'format' => [152.4, 228.6], // width_mm, height_mm
],
```

## Table of contents

```php
'toc' => [
    'h1' => 0,
    'h2' => 0,
    'h3' => 1,
],
```

Values follow mPDF `h2toc` conventions used by Papyrus (`0` include, `1`
exclude).

## Cover images

```php
'cover' => [
    'image' => 'cover.png',
    'light' => 'cover-light.png',
    'dark' => 'cover-dark.png',
    // 'width' => 188.976,  // mm; defaults to page width
    // 'height' => 246.126, // mm; defaults to page height
],
```

Paths are relative to `assets/`. Missing per-theme covers fall back to
`cover.image`. KDP print PDFs omit the cover page entirely.
## Running header

```php
'header' => [
    'style' => 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;',
],
```

Pretoc chapters suppress folios differently from body chapters.

## Fonts

Declare faces under `assets/fonts/` and optional **script routing** (PDF only).
First matching `script` rule wins.

```php
'fonts' => [
    'default' => 'librelibertine', // optional; else first face, else mPDF default
    'faces' => [
        [
            'name' => 'librelibertine',
            'regular' => 'LinLibertine_R.ttf',
            'bold' => 'LinLibertine_RB.ttf',
            'italic' => 'LinLibertine_RI.ttf',
            'bold_italic' => 'LinLibertine_RBI.ttf',
        ],
        [
            'name' => 'notosansbengali',
            'regular' => 'NotoSansBengali-Regular.ttf',
            'otl' => true, // OpenType layout — needed for complex scripts
        ],
    ],
    'script' => [
        // When mPDF tags a run as Bengali, use this face instead of the default
        ['match' => ['bn', 'ben', 'bengali'], 'face' => 'notosansbengali'],
    ],
],
```

### Faces

Each face needs a `name` and a `regular` file that exists under `assets/fonts/`.
Optional keys: `bold`, `italic`, `bold_italic`, and `otl` (sets mPDF
`useOTL` when true — common for code fonts and Indic / Arabic scripts).

Papyrus merges these into mPDF’s `fontdata` when building PDF
(`FontRegistry` → `MpdfFactory`).

### Script rules

`fonts.script` is a list of `{ match, face }` rules used only for **PDF**.
They do not change EPUB or HTML typography.

When any applicable rule exists, Papyrus turns on mPDF’s
`autoScriptToLang` and `autoLangToFont`, and installs a custom
`languageToFont` resolver (`ScriptLanguageToFont`). mPDF then:

1. Detects script runs in the HTML (e.g. Bengali glyphs).
2. Tags them with a language / script code (e.g. `bn`, or `*-beng`).
3. Asks Papyrus which face to use; the first rule whose `match` list hits
   that code wins.

`match` aliases are lowercased. Built-in aliases include:

| Aliases you can list                       | Resolves as       |
|--------------------------------------------|-------------------|
| `bn`, `ben`, `beng`, `bengali`             | Bengali           |
| `ar`, `arab`, `arabic`                     | Arabic            |
| `hi`, `hin`, `deva`, `devanagari`, `hindi` | Devanagari        |
| any 4-letter ISO script code               | that script as-is |

The `face` string must be a name from `fonts.faces` **and** that face must
actually load (regular file present). Rules pointing at a missing face are
skipped (`FontRegistry::applicableScriptRules()`). Unmatched languages fall
through to mPDF’s built-in `LanguageToFont`.

Example: body text in Latin uses `librelibertine`; a paragraph containing
`বন্ধন` is auto-tagged Bengali and rendered with `notosansbengali` if that
face is registered.

> {notice} Without both a registered face and a matching `script` rule,
> non-Latin runs often show as tofu (missing glyphs) in the default font.

## Site home (`build:site`)

```php
'site' => [
    'banner' => 'banner.jpg',
    'lead' => 'A one-line pitch for the home page.',
    'cname' => 'docs.example.com',
    'base_path' => '/my-repo', // project Pages path; omit for custom domains at /
    'repository' => 'https://github.com/you/your-book',
    'edit' => [
        'link' => true, // default false — set true to show Edit this page
        'path' => 'content',
        'branch' => 'main',
    ],
    'links' => [
        ['label' => 'Downloads', 'chapter' => '20-downloads.md'],
        ['label' => 'Source on GitHub', 'url' => 'https://github.com/you/your-book'],
        ['label' => 'Issues', 'url' => 'https://github.com/you/your-book/issues'],
    ],
    'nav' => [
        [
            'group' => 'Start here',
            'chapters' => ['00-welcome.md', '01-introduction.md'],
        ],
        [
            'group' => 'Build',
            'chapters' => ['10-build-site.md'],
        ],
    ],
],
```

| Key         | Default                                     | Notes                                                                                                                     |
|-------------|---------------------------------------------|---------------------------------------------------------------------------------------------------------------------------|
| `banner`       | auto `banner.jpg` / `banner.png` if present | Under `assets/`                                                                                                           |
| `lead`         | unset                                       | Home page pitch                                                                                                           |
| `cname`        | unset                                       | Custom domain; writes a `CNAME` file in the site root for GitHub Pages; absolute `sitemap.xml` / `robots.txt` Sitemap URL |
| `base_path`    | unset (site at `/`)                         | Path prefix for project GitHub Pages (e.g. `/my-repo`); injects `<base href>`; also prefixes sitemap locs                 |
| `links`        | unset                                       | Explicit home-page links; each item needs `label` plus either `url` or `chapter`                                          |
| `nav`          | unset                                       | Grouped sidebar: list of `{ group, chapters: [...] }`; orders Prev/Next too                                               |
| `repository`   | unset                                       | GitHub repo URL; used for **Edit this page** and legacy home links when `links` is unset                                  |
| `edit`         | unset                                       | Nested edit-link settings; see below                                                                                      |
| `copy_code`    | `true`                                      | Set `false` to disable the Copy control on code fences                                                                    |
| `copy_markdown`| `true`                                      | Set `false` to hide **Copy as Markdown** on chapter pages                                                                 |
| `print_page`   | `true`                                      | Set `false` to hide **Print this page** on chapter pages                                                                  |
| `page_toc`     | `true`                                      | Set `false` to hide the **On this page** rail                                                                             |
| `version`      | unset                                       | Current version label for the switcher (e.g. `v12`)                                                                       |
| `versions`     | unset                                       | Peer builds for the version switcher; see below                                                                           |

`site.edit` (optional):

| Key      | Default   | Notes                                                                                          |
|----------|-----------|------------------------------------------------------------------------------------------------|
| `link`   | `false`   | Set `true` to show **Edit this page** (also accepts `enabled`)                                 |
| `path`   | `content` | Path from repo root to `content/` (e.g. `docs/content`)                                        |
| `branch` | `main`    | Branch used in edit URLs                                                                       |

`chapter` matches a chapter source name like `20-downloads.md`, `20-downloads`,
or a full relative source path, and links to that generated page.
`repository` still works as a legacy fallback that auto-adds GitHub, Packagist,
and Issues links when `links` is not set. Chapters are never linked
automatically; add them explicitly through `links`. Older `site.mode` values
(`book` / `docs`) are ignored — every site home uses **Get started**.

```php
'site' => [
    'base_path' => '/barcode/v12',
    'version' => 'v12',
    'versions' => [
        ['label' => 'v12', 'path' => '/barcode/v12'],
        ['label' => 'v11', 'path' => '/barcode/v11'],
    ],
],
```

`versions` drives the sidebar version switcher (two or more entries). Each item
needs a `label` (or `version`) plus either `path` (like `base_path`) or absolute
`url`. Mark the current build with `site.version` and/or a matching `base_path`.
Each listed build is a separate `build:site` deploy under that path prefix.
See the `build:site` chapter for navigation behaviour and filename conventions.

## Mermaid

```php
'mermaid' => [
    'enabled' => true,
    'format' => 'svg',          // svg | png
    'theme' => 'auto',          // auto = book colours; or default|dark|forest|neutral
    'max_width_mm' => 130,
    // 'command' => '/usr/local/bin/mmdc', // optional override
],
```

| Key            | Default             | Notes                                                       |
|----------------|---------------------|-------------------------------------------------------------|
| `enabled`      | `false`             | Requires `mmdc` on `PATH` (or `command`)                    |
| `format`       | `svg`               | Anything other than `png` becomes `svg`                     |
| `theme`        | `auto`              | Book-matched palettes for PDF; HTML/site embed light + dark |
| `max_width_mm` | `130`               | PDF figure width cap                                        |
| `command`      | auto-resolve `mmdc` | Explicit CLI path                                           |

## Sample PDF

```php
'sample' => [
    'ranges' => [
        ['from' => 1, 'to' => 3],
    ],
    'chapters' => [
        '01-introduction.md',
    ],
],
'sample_notice' => 'This is a sample from My Book.',
```

Ranges are 1-based inclusive pages of the finished theme PDF. `chapters` lists
content filenames (or stems) to include in full. Notice text also accepts
`sample.notice` / `sample.text` as fallbacks. See the `build:sample` chapter.

## KDP

```php
'kdp' => [
    'ebook' => [
        'enabled' => true,
        'cover' => 'cover-ebook.jpg',
    ],
    'print' => [
        'enabled' => true,
        'bleed_mm' => 3,
        'margin_preset' => 'recommended', // or minimum
        'paper' => 'white',               // or cream — spine width
        'back_cover' => 'back-cover.png', // optional wrap artwork
        'spine_color' => '#1a1a1a',       // optional wrap spine fill
    ],
    'metadata' => [
        'description' => 'Bookstore blurb…',
        'keywords' => ['laravel', 'filament'],
        'language' => 'en',
    ],
],
```

Full option tables live in the `kdp` and `kdp:*` chapters. `papyrus build`
only runs KDP when ebook or print is enabled; `papyrus kdp` errors if neither
is.

## Break level

```php
'break_level' => 2,
```

Automatic page breaks before headings (`1` = H1, `2` = H1 and H2). Explicit
`[break]` markers always insert a break.

## CommonMark hook

```php
'configure_commonmark' => function (
    \League\CommonMark\Environment\Environment $environment
): void {
    // register custom extensions
},
```
