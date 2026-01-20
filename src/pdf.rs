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
    
    // Combine all HTML content
    let combined_html: String = processed_files
        .iter()
        .map(|file| {
            let title = file.frontmatter
                .as_ref()
                .and_then(|fm| fm.title.as_ref())
                .map(|t| format!("<h1>{}</h1>", html_escape(t)))
                .unwrap_or_default();
            format!("{}\n{}", title, file.html)
        })
        .collect::<Vec<_>>()
        .join("\n<hr>\n");
    
    // Render template
    use tera::{Tera, Context};
    let mut tera = Tera::new("dummy")?;
    tera.add_raw_template("theme", &theme_content)?;
    
    let mut context = Context::new();
    context.insert("title", &config.title);
    context.insert("content", &combined_html);
    
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
    let output = Command::new("wkhtmltopdf")
        .arg("--enable-local-file-access")
        .arg("--page-size")
        .arg("A4")
        .arg(html_path)
        .arg(pdf_path)
        .output()
        .await?;
    
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
