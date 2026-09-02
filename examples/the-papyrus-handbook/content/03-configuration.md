---
title: Configuration
---

# Configuration

All settings live in `papyrus.php`, which returns a PHP array.

## Identity

```php
return [
    'title' => 'My Book',
    'subtitle' => 'A short subtitle',
    'author' => 'Author Name',
    'themes' => ['light', 'dark'],
];
```

`themes` lists PDF theme names. Each needs `assets/theme-{name}.html`.

The export filename slug comes from the title (lowercased, hyphenated). This
handbook is *The Papyrus Handbook*, so outputs look like
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
],
```

Paths are relative to `assets/`. Missing per-theme covers fall back to
`cover.image`.

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

| Aliases you can list | Resolves as |
|----------------------|-------------|
| `bn`, `ben`, `beng`, `bengali` | Bengali |
| `ar`, `arab`, `arabic` | Arabic |
| `hi`, `hin`, `deva`, `devanagari`, `hindi` | Devanagari |
| any 4-letter ISO script code | that script as-is |

The `face` string must be a name from `fonts.faces` **and** that face must
actually load (regular file present). Rules pointing at a missing face are
skipped (`FontRegistry::applicableScriptRules()`). Unmatched languages fall
through to mPDF’s built-in `LanguageToFont`.

Example: body text in Latin uses `librelibertine`; a paragraph containing
`বন্ধন` is auto-tagged Bengali and rendered with `notosansbengali` if that
face is registered.

> {notice} Without both a registered face and a matching `script` rule,
> non-Latin runs often show as tofu (missing glyphs) in the default font.

## Break level

```php
'breakLevel' => 2,
```

Automatic page breaks before headings (`1` = H1, `2` = H1 and H2). Explicit
`[break]` markers always insert a break.

## CommonMark hook

```php
'configure_commonmark' => [
    // callable(s) receiving the Environment
],
```
