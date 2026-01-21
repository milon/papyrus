use std::path::Path;
use std::fs::{File, create_dir_all};
use std::io::Write;
use zip::{ZipWriter, CompressionMethod};
use zip::write::FileOptions;
use crate::error::Result;
use crate::markdown::MarkdownFile;
use crate::config::Config;
use crate::html::highlight_code_blocks;
use std::fs;

pub async fn generate_epub<P: AsRef<Path>>(book_dir: P, content_dir: P) -> Result<()> {
    let book_dir = book_dir.as_ref();
    let content_dir = content_dir.as_ref();
    
    // Load config
    let config = Config::load(book_dir)?;
    
    // Collect and parse markdown files
    let md_files = crate::markdown::collect_markdown_files(content_dir, config.md_file_list.as_deref())?;
    let parsed_files = crate::markdown::parse_markdown_files(&md_files)?;
    
    // Process markdown with syntax highlighting
    let processed_files: Vec<MarkdownFile> = parsed_files
        .into_iter()
        .map(|mut file| {
            file.html = highlight_code_blocks(&file.html);
            file
        })
        .collect();
    
    // Create EPUB structure
    let export_dir = book_dir.join("export");
    create_dir_all(&export_dir)?;
    
    let epub_path = export_dir.join(format!("{}.epub", sanitize_filename(&config.title)));
    let file = File::create(&epub_path)?;
    let mut zip = ZipWriter::new(file);
    
    let options = FileOptions::default()
        .compression_method(CompressionMethod::Deflated)
        .unix_permissions(0o644);
    
    // Write mimetype (must be first, uncompressed)
    zip.start_file("mimetype", FileOptions::default().compression_method(CompressionMethod::Stored))?;
    zip.write_all(b"application/epub+zip")?;
    
    // Create META-INF directory
    zip.start_file("META-INF/container.xml", options)?;
    zip.write_all(include_bytes!("../templates/epub_container.xml"))?;
    
    // Create OPF file
    let opf_content = generate_opf(&config, &processed_files)?;
    zip.start_file("OEBPS/content.opf", options)?;
    zip.write_all(opf_content.as_bytes())?;
    
    // Create NCX file (table of contents)
    let ncx_content = generate_ncx(&config, &processed_files)?;
    zip.start_file("OEBPS/toc.ncx", options)?;
    zip.write_all(ncx_content.as_bytes())?;
    
    // Write HTML files
    for (index, file) in processed_files.iter().enumerate() {
        let html_content = format!(
            r#"<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{}</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
{}
</body>
</html>"#,
            file.frontmatter.as_ref()
                .and_then(|fm| fm.title.as_ref())
                .map(|s| html_escape(s))
                .unwrap_or_else(|| format!("Chapter {}", index + 1)),
            file.html
        );
        
        let filename = format!("OEBPS/chapter{:03}.xhtml", index + 1);
        zip.start_file(&filename, options)?;
        zip.write_all(html_content.as_bytes())?;
    }
    
    // Copy CSS file
    let css_path = book_dir.join("assets").join("style.css");
    if css_path.exists() {
        let css_content = fs::read_to_string(&css_path)?;
        zip.start_file("OEBPS/style.css", options)?;
        zip.write_all(css_content.as_bytes())?;
    }
    
    // Copy cover image if exists (only image formats supported)
    if let Some(cover_path) = &config.cover {
        let cover_file_path = book_dir.join("assets").join("images").join(cover_path);
        if cover_file_path.exists() {
            let ext = cover_file_path.extension()
                .and_then(|s| s.to_str())
                .map(|s| s.to_lowercase())
                .unwrap_or_else(|| "jpg".to_string());
            
            // EPUB supports PNG, JPG, GIF, SVG, WEBP - only image formats
            match ext.as_str() {
                "png" | "jpg" | "jpeg" | "gif" | "webp" | "svg" => {
                    let cover_data = fs::read(&cover_file_path)?;
                    zip.start_file(format!("OEBPS/cover.{}", ext), options)?;
                    zip.write_all(&cover_data)?;
                }
                _ => {
                    // Skip unsupported formats (like PDF)
                }
            }
        }
    }
    
    zip.finish()?;
    
    Ok(())
}

fn generate_opf(config: &Config, files: &[MarkdownFile]) -> Result<String> {
    let mut opf = String::from(r#"<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="bookid">
    <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
        <dc:title>"#);
    opf.push_str(&xml_escape(&config.title));
    opf.push_str(r#"</dc:title>
        <dc:creator>"#);
    opf.push_str(&xml_escape(&config.author));
    opf.push_str(r#"</dc:creator>
        <dc:language>"#);
    opf.push_str(&config.language.as_deref().unwrap_or("en"));
    opf.push_str(r#"</dc:language>
        <dc:identifier id="bookid">urn:uuid:");
    opf.push_str(&uuid::Uuid::new_v4().to_string());
    opf.push_str(r#"</dc:identifier>
    </metadata>
    <manifest>
        <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
        <item id="style" href="style.css" media-type="text/css"/>
"#);
    
    for (index, _) in files.iter().enumerate() {
        opf.push_str(&format!(
            "        <item id=\"chapter{}\" href=\"chapter{:03}.xhtml\" media-type=\"application/xhtml+xml\"/>\n",
            index + 1,
            index + 1
        ));
    }
    
    opf.push_str(r#"    </manifest>
    <spine toc="ncx">
"#);
    
    for (index, _) in files.iter().enumerate() {
        opf.push_str(&format!("        <itemref idref=\"chapter{}\"/>\n", index + 1));
    }
    
    opf.push_str(r#"    </spine>
</package>"#);
    
    Ok(opf)
}

fn generate_ncx(config: &Config, files: &[MarkdownFile]) -> Result<String> {
    let mut ncx = String::from(r#"<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
    <head>
        <meta name="dtb:uid" content="urn:uuid:");
    ncx.push_str(&uuid::Uuid::new_v4().to_string());
    ncx.push_str(r#""/>
        <meta name="dtb:depth" content="1"/>
        <meta name="dtb:totalPageCount" content="0"/>
        <meta name="dtb:maxPageNumber" content="0"/>
    </head>
    <docTitle>
        <text>"#);
    ncx.push_str(&xml_escape(&config.title));
    ncx.push_str(r#"</text>
    </docTitle>
    <navMap>
"#);
    
    for (index, file) in files.iter().enumerate() {
        let title = file.frontmatter
            .as_ref()
            .and_then(|fm| fm.title.as_ref())
            .map(|s| xml_escape(s))
            .unwrap_or_else(|| format!("Chapter {}", index + 1));
        
        ncx.push_str(&format!(
            r#"        <navPoint id="navpoint-{}" playOrder="{}">
            <navLabel>
                <text>{}</text>
            </navLabel>
            <content src="chapter{:03}.xhtml"/>
        </navPoint>
"#,
            index + 1,
            index + 1,
            title,
            index + 1
        ));
    }
    
    ncx.push_str(r#"    </navMap>
</ncx>"#);
    
    Ok(ncx)
}

fn xml_escape(s: &str) -> String {
    s.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace("\"", "&quot;")
        .replace("'", "&apos;")
}

fn html_escape(s: &str) -> String {
    s.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace("\"", "&quot;")
        .replace("'", "&#x27;")
}

fn sanitize_filename(name: &str) -> String {
    name.chars()
        .map(|c| if c.is_alphanumeric() || c == '-' || c == '_' { c } else { '-' })
        .collect()
}
