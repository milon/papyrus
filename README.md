# Papyrus

PHP CLI for Markdown book projects — PDF, EPUB, HTML, and KDP exports.

Built from scratch with heavy influence from [ibis-next](https://github.com/Hi-Folks/ibis-next). Book projects use `papyrus.php`, `content/`, and `assets/`.

## Requirements

- PHP 8.2+
- Composer
- PHP extensions: `dom`, `gd`, `mbstring`, `zlib` (PDF export via mPDF)

## Install (development)

```bash
composer install
```

Run the CLI:

```bash
./bin/papyrus --version
./bin/papyrus list
```

## Quick start

Scaffold a new book in the current directory:

```bash
./bin/papyrus init
./bin/papyrus doctor
```

Or in a new folder:

```bash
mkdir my-book && ./bin/papyrus init -d my-book
./bin/papyrus doctor -d my-book
```

## Commands

| Command | Status |
|---------|--------|
| `init` | Scaffold `papyrus.php`, `content/`, `assets/` |
| `doctor` | Validate config and project paths |
| `pdf` | Build PDF themes (`--theme light,dark`) |
| `build`, `epub`, `html`, `sample`, `sort`, `sizes`, `watch`, `migrate-ibis`, `kdp …` | Not yet implemented |

Convert Markdown chapters programmatically:

```php
$project = Milon\Papyrus\Config\Project::load($bookDir);
$book = $project->bookConverter()->convertDirectory($project->contentDir);
```

## Tests

```bash
composer test
composer lint   # Pint
composer format # Pint --write
```

## License

MIT — see [LICENSE](LICENSE).
