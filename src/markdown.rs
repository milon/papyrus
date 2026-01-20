use pulldown_cmark::{Parser, Options, html};
use gray_matter::Matter;
use serde::{Deserialize, Serialize};
use std::path::{Path, PathBuf};
use crate::error::{PapyrusError, Result};
use std::fs;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct FrontMatter {
    pub title: Option<String>,
    pub author: Option<String>,
    pub date: Option<String>,
    #[serde(flatten)]
    pub extra: serde_yaml::Value,
}

#[derive(Debug, Clone)]
pub struct MarkdownFile {
    #[allow(dead_code)]
    pub path: PathBuf,
    pub frontmatter: Option<FrontMatter>,
    #[allow(dead_code)]
    pub content: String,
    pub html: String,
}

impl MarkdownFile {
    pub fn parse<P: AsRef<Path>>(path: P) -> Result<Self> {
        let path = path.as_ref();
        let content = fs::read_to_string(path)?;
        
        // Parse frontmatter
        let matter = Matter::<gray_matter::engine::YAML>::new();
        let parsed = matter.parse(&content);
        
        let frontmatter = if parsed.data.is_some() {
            let fm: FrontMatter = parsed.data.unwrap().deserialize()
                .map_err(|e| PapyrusError::Markdown(format!("Failed to parse frontmatter: {}", e)))?;
            Some(fm)
        } else {
            None
        };
        
        let markdown_content = parsed.content;
        
        // Parse markdown to HTML
        let mut options = Options::empty();
        options.insert(Options::ENABLE_STRIKETHROUGH);
        options.insert(Options::ENABLE_TABLES);
        options.insert(Options::ENABLE_FOOTNOTES);
        options.insert(Options::ENABLE_TASKLISTS);
        
        let parser = Parser::new_ext(&markdown_content, options);
        let mut html_output = String::new();
        html::push_html(&mut html_output, parser);
        
        Ok(MarkdownFile {
            path: path.to_path_buf(),
            frontmatter,
            content: markdown_content,
            html: html_output,
        })
    }
}

pub fn collect_markdown_files<P: AsRef<Path>>(content_dir: P, md_file_list: Option<&[String]>) -> Result<Vec<PathBuf>> {
    let content_dir = content_dir.as_ref();
    
    if !content_dir.exists() {
        return Err(PapyrusError::Asset(format!(
            "Content directory does not exist: {}",
            content_dir.display()
        )));
    }
    
    let mut files = Vec::new();
    
    if let Some(file_list) = md_file_list {
        // Use specified file list
        for filename in file_list {
            let file_path = content_dir.join(filename);
            if file_path.exists() {
                files.push(file_path);
            }
        }
    } else {
        // Collect all markdown files
        use walkdir::WalkDir;
        for entry in WalkDir::new(content_dir) {
            let entry = entry?;
            let path = entry.path();
            if path.is_file() && path.extension().and_then(|s| s.to_str()) == Some("md") {
                files.push(path.to_path_buf());
            }
        }
        
        // Sort alphabetically
        files.sort();
    }
    
    Ok(files)
}

pub fn parse_markdown_files(files: &[PathBuf]) -> Result<Vec<MarkdownFile>> {
    files.iter()
        .map(MarkdownFile::parse)
        .collect()
}
