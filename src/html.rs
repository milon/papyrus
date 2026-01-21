use tera::{Tera, Context};
use std::path::Path;
use crate::error::{PapyrusError, Result};
use crate::markdown::MarkdownFile;
use crate::config::Config;
use std::fs;
use syntect::easy::HighlightLines;
use syntect::parsing::SyntaxSet;
use syntect::highlighting::{ThemeSet, Style};
use syntect::util::{as_24_bit_terminal_escaped, LinesWithEndings};

pub async fn generate_html<P: AsRef<Path>>(book_dir: P, content_dir: P) -> Result<()> {
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
    
    // Load theme template
    let theme_path = book_dir.join("assets").join("theme-html.html");
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
    let mut tera = Tera::default();
    tera.add_raw_template("theme", &theme_content)?;
    
    let mut context = Context::new();
    context.insert("title", &config.title);
    context.insert("content", &combined_html);
    
    let rendered = tera.render("theme", &context)?;
    
    // Write output
    let export_dir = book_dir.join("export");
    fs::create_dir_all(&export_dir)?;
    
    let output_path = export_dir.join(format!("{}.html", sanitize_filename(&config.title)));
    fs::write(&output_path, rendered)?;
    
    // Copy CSS file if it exists
    let css_path = book_dir.join("assets").join("style.css");
    if css_path.exists() {
        let css_content = fs::read_to_string(&css_path)?;
        let css_output = export_dir.join("style.css");
        fs::write(&css_output, css_content)?;
    }
    
    Ok(())
}

pub fn highlight_code_blocks(html: &str) -> String {
    let ps = SyntaxSet::load_defaults_newlines();
    let ts = ThemeSet::load_defaults();
    let theme = &ts.themes["base16-ocean.dark"];
    
    // Simple regex-based approach to find and highlight code blocks
    use regex::Regex;
    let code_block_re = Regex::new(r#"(?s)<pre><code(?: class="language-(\w+)")?>([^<]+)</code></pre>"#).unwrap();
    
    code_block_re.replace_all(html, |caps: &regex::Captures| {
        let language = caps.get(1).map(|m| m.as_str()).unwrap_or("text");
        let code = caps.get(2).map(|m| m.as_str()).unwrap_or("");
        
        if let Some(syntax) = ps.find_syntax_by_token(language) {
            let mut highlighter = HighlightLines::new(syntax, theme);
            let mut highlighted = String::new();
            
            for line in LinesWithEndings::from(code) {
                let ranges: Vec<(Style, &str)> = highlighter.highlight_line(line, &ps).unwrap();
                highlighted.push_str(&as_24_bit_terminal_escaped(&ranges[..], false));
            }
            
            format!("<pre><code class=\"language-{}\">{}</code></pre>", language, code)
        } else {
            format!("<pre><code>{}</code></pre>", html_escape(code))
        }
    }).to_string()
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
