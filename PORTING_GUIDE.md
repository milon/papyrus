# Porting Guide: ibis-next PHP to Rust (Papyrus)

This document outlines the porting strategy and implementation details for converting ibis-next from PHP to Rust.

## Architecture Overview

### Original (ibis-next PHP)
- PHP CLI tool using Symfony Console
- Configuration in PHP arrays (v2) or Config class (v3)
- Markdown parsing with frontmatter
- HTML generation with templates
- EPUB packaging
- PDF generation via external tools

### New (Papyrus Rust)
- Rust CLI tool using `clap`
- Configuration in TOML format
- Markdown parsing with `pulldown-cmark` and `gray_matter`
- HTML generation with `tera` templates
- EPUB packaging with `zip` crate
- PDF generation via external tools (wkhtmltopdf, weasyprint, or Chrome)

## Key Design Decisions

### 1. Configuration Format
**Decision**: Use TOML instead of PHP arrays
- **Rationale**: TOML is more readable, widely supported, and type-safe
- **Migration**: `config:migrate` command helps convert old configs

### 2. Markdown Parsing
**Decision**: Use `pulldown-cmark` for CommonMark parsing
- **Rationale**: Fast, well-maintained, and follows CommonMark spec
- **Frontmatter**: Use `gray_matter` for YAML frontmatter extraction

### 3. Template Engine
**Decision**: Use `tera` (Jinja2-like) instead of PHP templates
- **Rationale**: Similar syntax to original, Rust-native, performant
- **Compatibility**: Templates need minor syntax adjustments

### 4. PDF Generation
**Decision**: Support multiple backends (wkhtmltopdf, weasyprint, Chrome)
- **Rationale**: Different tools have different strengths; fallback options
- **Implementation**: Try each backend in order until one succeeds

### 5. EPUB Generation
**Decision**: Use `zip` crate to build EPUB manually
- **Rationale**: Full control over EPUB structure, no external dependencies
- **Structure**: Follows EPUB 3.0 specification

## Module Structure

```
src/
├── main.rs          # Entry point, CLI routing
├── cli.rs           # CLI command definitions and handlers
├── config.rs        # Configuration loading/saving (TOML)
├── markdown.rs      # Markdown parsing and frontmatter extraction
├── html.rs          # HTML generation and syntax highlighting
├── epub.rs          # EPUB packaging
├── pdf.rs           # PDF generation (via external tools)
├── assets.rs        # Asset management utilities
└── error.rs         # Error types and handling
```

## Feature Parity

### ✅ Implemented
- [x] CLI commands (init, pdf, epub, html, sample, config:migrate)
- [x] Configuration management (TOML)
- [x] Markdown parsing with frontmatter
- [x] HTML generation with themes
- [x] EPUB generation
- [x] PDF generation (via external tools)
- [x] Syntax highlighting
- [x] Asset management
- [x] Custom fonts support
- [x] Cover image support
- [x] File list configuration

### ⚠️ Partially Implemented
- [ ] Sample generation (basic structure, needs page range logic)
- [ ] Config migration (detection only, manual conversion needed)

### ❌ Not Yet Implemented
- [ ] Advanced theme customization
- [ ] Custom CSS injection points
- [ ] Image optimization
- [ ] Table of contents generation for HTML
- [ ] Page numbering in PDF
- [ ] Header/footer customization

## Performance Improvements

Rust port provides several performance benefits:

1. **Compilation**: Single binary, no PHP runtime needed
2. **Startup**: Faster startup time (no PHP interpreter)
3. **Memory**: Lower memory footprint
4. **Concurrency**: Can leverage Rust's async capabilities for parallel processing

## Migration Path

### For Users

1. **Install Papyrus**: Build from source or install via cargo
2. **Initialize Project**: Run `papyrus init` in your book directory
3. **Migrate Config**: Run `papyrus config:migrate` (may need manual review)
4. **Update Themes**: Convert PHP templates to Tera templates
5. **Test**: Generate outputs and verify they match expectations

### For Developers

1. **Study Original**: Review ibis-next PHP source code
2. **Understand Structure**: Map PHP classes to Rust modules
3. **Port Features**: Implement feature by feature
4. **Test**: Compare outputs between PHP and Rust versions
5. **Optimize**: Leverage Rust's performance characteristics

## Dependencies

### Core Dependencies
- `clap` - CLI argument parsing
- `serde` + `toml` - Configuration serialization
- `pulldown-cmark` - Markdown parsing
- `gray_matter` - Frontmatter extraction
- `tera` - Template engine
- `syntect` - Syntax highlighting
- `zip` - EPUB packaging

### External Tools (for PDF)
- `wkhtmltopdf` (recommended)
- `weasyprint` (alternative)
- `chrome`/`chromium` (fallback)

## Testing Strategy

1. **Unit Tests**: Test individual modules (markdown parsing, config loading)
2. **Integration Tests**: Test full workflows (init → generate)
3. **Comparison Tests**: Compare outputs with original PHP version
4. **Regression Tests**: Ensure feature parity

## Future Enhancements

1. **Pure Rust PDF**: Investigate pure Rust PDF generation (no external tools)
2. **Better Error Messages**: More helpful error messages for common issues
3. **Plugin System**: Allow custom processors/transformers
4. **Watch Mode**: Auto-regenerate on file changes
5. **Live Preview**: HTML preview server with auto-reload

## Notes

- The Rust version maintains API compatibility where possible
- Some PHP-specific features may not translate directly
- Performance improvements are expected but not guaranteed for all use cases
- The codebase is designed to be extensible and maintainable
