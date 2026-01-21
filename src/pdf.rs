use std::path::Path;
use crate::error::{PapyrusError, Result};
use crate::config::Config;
use crate::html;
use crate::markdown;
use std::fs;
use tokio::process::Command;

pub async fn generate_pdf<P: AsRef<Path>>(book_dir: P, content_dir: P, theme: &str) -> Result<()> {
    let book_dir = book_dir.as_ref();
    let content_dir = content_dir.as_ref();
    
    // Load config
    let config = Config::load(book_dir)?;
    
    // Collect and parse markdown files
    let md_files = markdown::collect_markdown_files(content_dir, config.md_file_list.as_deref())?;
    let parsed_files = markdown::parse_markdown_files(&md_files)?;
    
    // Process markdown with syntax highlighting
    let processed_files: Vec<markdown::MarkdownFile> = parsed_files
        .into_iter()
        .map(|mut file| {
            file.html = html::highlight_code_blocks(&file.html);
            file
        })
        .collect();
    
    // Load theme template
    let theme_file = match theme {
        "dark" => "theme-dark.html",
        _ => "theme-light.html",
    };
    let theme_path = book_dir.join("assets").join(theme_file);
    
    if !theme_path.exists() {
        return Err(PapyrusError::Asset(format!(
            "Theme file not found: {}",
            theme_path.display()
        )));
    }
    
    let theme_content = fs::read_to_string(&theme_path)?;
    
    // Generate cover page HTML if cover exists
    let cover_html = if let Some(cover_file) = &config.cover {
        let cover_path = book_dir.join("assets").join("images").join(cover_file);
        if cover_path.exists() {
            let cover_url = cover_path.canonicalize()?
                .to_string_lossy()
                .replace('\\', "/");
            
            // Check file extension - only support image formats
            let ext = cover_path.extension()
                .and_then(|s| s.to_str())
                .map(|s| s.to_lowercase())
                .unwrap_or_default();
            
            match ext.as_str() {
                "png" | "jpg" | "jpeg" | "gif" | "webp" | "svg" => {
                    // For image covers, use img tag
                    format!(
                        r#"<div class="cover-page" style="page-break-after: always; text-align: center; padding: 2cm; min-height: 100vh; display: flex; align-items: center; justify-content: center; page: cover;">
                            <img src="file://{}" alt="Cover" style="max-width: 100%; max-height: 80vh; object-fit: contain;" />
                        </div>"#,
                        cover_url
                    )
                }
                _ => {
                    // Skip unsupported formats (like PDF)
                    String::new()
                }
            }
        } else {
            String::new()
        }
    } else {
        String::new()
    };
    
    // Generate table of contents
    let toc_html = generate_toc(&processed_files);
    
    // Combine all HTML content with page breaks between chapters
    let combined_html: String = processed_files
        .iter()
        .enumerate()
        .map(|(index, file)| {
            let title = file.frontmatter
                .as_ref()
                .and_then(|fm| fm.title.as_ref())
                .map(|t| format!("<h1 id=\"chapter-{}\">{}</h1>", index + 1, html_escape(t)))
                .unwrap_or_else(|| format!("<h1 id=\"chapter-{}\">Chapter {}</h1>", index + 1, index + 1));
            
            // Add page break before each chapter (except the first one)
            let page_break = if index > 0 {
                r#"<div style="page-break-before: always;"></div>"#
            } else {
                ""
            };
            
            format!("{}\n{}\n{}", page_break, title, file.html)
        })
        .collect::<Vec<_>>()
        .join("\n");
    
    // Render template
    use tera::{Tera, Context};
    let mut tera = Tera::default();
    tera.add_raw_template("theme", &theme_content)?;
    
    let mut context = Context::new();
    context.insert("title", &config.title);
    context.insert("content", &format!("{}\n{}\n{}", cover_html, toc_html, combined_html));
    
    let rendered = tera.render("theme", &context)?;
    
    // Write temporary HTML file
    let export_dir = book_dir.join("export");
    fs::create_dir_all(&export_dir)?;
    
    let temp_html = export_dir.join("temp_pdf.html");
    fs::write(&temp_html, rendered)?;
    
    // Generate PDF using external tool
    // Try wkhtmltopdf first, then weasyprint, then chrome/chromium
    let pdf_path = export_dir.join(format!("{}.pdf", sanitize_filename(&config.title)));
    
    if let Ok(_) = generate_with_wkhtmltopdf(&temp_html, &pdf_path).await {
        fs::remove_file(&temp_html)?;
        return Ok(());
    }
    
    if let Ok(_) = generate_with_weasyprint(&temp_html, &pdf_path).await {
        fs::remove_file(&temp_html)?;
        return Ok(());
    }
    
    if let Ok(_) = generate_with_chrome(&temp_html, &pdf_path).await {
        fs::remove_file(&temp_html)?;
        return Ok(());
    }
    
    fs::remove_file(&temp_html)?;
    
    Err(PapyrusError::Pdf(
        "No PDF generator found. Please install one of: wkhtmltopdf, weasyprint, or Chrome/Chromium".to_string()
    ))
}

async fn generate_with_wkhtmltopdf(html_path: &Path, pdf_path: &Path) -> Result<()> {
    // Load footer HTML template
    const FOOTER_HTML: &str = include_str!("../templates/pdf_footer.html");
    
    // Write footer HTML to a temporary file
    let footer_path = html_path.parent().unwrap().join("footer.html");
    fs::write(&footer_path, FOOTER_HTML)?;
    
    let output = Command::new("wkhtmltopdf")
        .arg("--enable-local-file-access")
        .arg("--page-size")
        .arg("A4")
        .arg("--footer-html")
        .arg(&footer_path)
        .arg("--footer-spacing")
        .arg("10")
        .arg("--no-footer-line")
        .arg("--disable-smart-shrinking")
        .arg(html_path)
        .arg(pdf_path)
        .output()
        .await?;
    
    // Clean up footer file
    let _ = fs::remove_file(&footer_path);
    
    if output.status.success() {
        Ok(())
    } else {
        Err(PapyrusError::Pdf(format!(
            "wkhtmltopdf failed: {}",
            String::from_utf8_lossy(&output.stderr)
        )))
    }
}

async fn generate_with_weasyprint(html_path: &Path, pdf_path: &Path) -> Result<()> {
    // Inject CSS page number rules for weasyprint
    const PAGE_CSS: &str = include_str!("../templates/pdf_page.css");
    let html_content = fs::read_to_string(html_path)?;
    
    // Add TOC page number CSS using target-counter (supported by weasyprint)
    let toc_css = r#"
        .toc-page a::after {
            content: leader('.') target-counter(attr(href), page);
            float: right;
            margin-left: 1em;
        }
    "#;
    
    let page_css = format!(r#"
        <style>
            {}
            {}
        </style>
    "#, PAGE_CSS, toc_css);
    
    // Insert CSS before closing </head> tag
    let modified_html = html_content.replace("</head>", &format!("{}</head>", page_css));
    fs::write(html_path, modified_html)?;
    
    let output = Command::new("weasyprint")
        .arg(html_path)
        .arg(pdf_path)
        .output()
        .await?;
    
    if output.status.success() {
        Ok(())
    } else {
        Err(PapyrusError::Pdf(format!(
            "weasyprint failed: {}",
            String::from_utf8_lossy(&output.stderr)
        )))
    }
}

async fn generate_with_chrome(html_path: &Path, pdf_path: &Path) -> Result<()> {
    // Inject CSS page number rules for Chrome
    const PAGE_CSS: &str = include_str!("../templates/pdf_page.css");
    let html_content = fs::read_to_string(html_path)?;
    
    // Add TOC page number CSS using target-counter (supported by Chrome)
    let toc_css = r#"
        .toc-page a::after {
            content: leader('.') target-counter(attr(href), page);
            float: right;
        }
    "#;
    
    let page_css = format!(r#"
        <style>
            {}
            {}
        </style>
    "#, PAGE_CSS, toc_css);
    
    // Insert CSS before closing </head> tag
    let modified_html = html_content.replace("</head>", &format!("{}</head>", page_css));
    fs::write(html_path, modified_html)?;
    
    // Try Chrome first, then Chromium
    let chrome_cmd = if Command::new("google-chrome")
        .arg("--version")
        .output()
        .await
        .is_ok()
    {
        "google-chrome"
    } else if Command::new("chromium")
        .arg("--version")
        .output()
        .await
        .is_ok()
    {
        "chromium"
    } else if Command::new("chromium-browser")
        .arg("--version")
        .output()
        .await
        .is_ok()
    {
        "chromium-browser"
    } else {
        return Err(PapyrusError::Pdf("Chrome/Chromium not found".to_string()));
    };
    
    let html_url = format!("file://{}", html_path.canonicalize()?.display());
    
    let output = Command::new(chrome_cmd)
        .arg("--headless")
        .arg("--disable-gpu")
        .arg("--print-to-pdf")
        .arg(pdf_path)
        .arg(&html_url)
        .output()
        .await?;
    
    if output.status.success() {
        Ok(())
    } else {
        Err(PapyrusError::Pdf(format!(
            "Chrome PDF generation failed: {}",
            String::from_utf8_lossy(&output.stderr)
        )))
    }
}

fn generate_toc(files: &[markdown::MarkdownFile]) -> String {
    let toc_items: Vec<String> = files
        .iter()
        .enumerate()
        .map(|(index, file)| {
            let title = file.frontmatter
                .as_ref()
                .and_then(|fm| fm.title.as_ref())
                .map(|t| html_escape(t))
                .unwrap_or_else(|| format!("Chapter {}", index + 1));
            
            format!(
                "        <li style=\"margin-bottom: 0.5em;\">\n            <a href=\"#chapter-{}\" style=\"text-decoration: none; color: inherit;\">{}</a>\n        </li>",
                index + 1,
                title
            )
        })
        .collect();
    
    format!(
        "<div class=\"toc-page\" style=\"page-break-after: always; padding: 2cm;\">\n    <h1 style=\"text-align: center; margin-bottom: 2cm; font-size: 2em;\">Table of Contents</h1>\n    <ul style=\"list-style: none; padding: 0; font-size: 1.1em; line-height: 1.8;\">\n{}\n    </ul>\n</div>",
        toc_items.join("\n")
    )
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
